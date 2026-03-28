<?php

declare(strict_types=1);

namespace Reqxide\Transport;

use FFI\CData;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Profile;
use Reqxide\Exception\FfiException;
use Reqxide\Exception\NetworkException;
use Reqxide\Http1\OriginalHeaderMap;
use Reqxide\Http2\Http2Options;
use Reqxide\Http2\SettingId;
use Reqxide\Proxy\Proxy;
use Reqxide\Proxy\ProxyScheme;
use Reqxide\Tls\AlpnProtocol;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsVersion;
use Reqxide\Transport\Ffi\FfiWrapper;

final class FfiTransport implements TransportInterface
{
    private readonly FfiWrapper $wrapper;

    public function __construct(
        ?FfiWrapper $wrapper = null,
        ?string $libraryPath = null,
    ) {
        $this->wrapper = $wrapper ?? new FfiWrapper(
            self::headerPath(),
            $libraryPath ?? self::detectLibraryPath(),
        );
    }

    public function send(
        RequestInterface $request,
        Profile $profile,
        TransportOptions $options,
    ): ResponseInterface {
        $handle = $this->wrapper->easyInit();
        $headerList = null;

        try {
            $this->applyRequest($handle, $request);
            $this->applyTlsOptions($handle, $profile->tlsOptions);
            $this->applyHttp2Options($handle, $profile->http2Options);
            $headerList = $this->applyHeaders($handle, $request, $profile);
            $this->applyTransportOptions($handle, $options);

            // Disable redirect following (middleware handles it)
            $this->wrapper->easySetopt($handle, 52, 0); // CURLOPT_FOLLOWLOCATION

            $result = $this->wrapper->easyPerform($handle);

            if ($result !== 0) { // CURLE_OK = 0
                throw new NetworkException(
                    $request,
                    'curl_impersonate error: '.$this->wrapper->easyStrerror($result),
                    $result,
                );
            }

            // Get status code via CURLINFO_RESPONSE_CODE
            $statusCode = $this->wrapper->easyGetinfo($handle, 2097154);

            return new Response($statusCode, [], '');
        } finally {
            if ($headerList !== null) {
                $this->wrapper->slistFreeAll($headerList);
            }

            $this->wrapper->easyCleanup($handle);
        }
    }

    public function supportsFingerprinting(): bool
    {
        return true;
    }

    public function supportsHttp2Configuration(): bool
    {
        return true;
    }

    public static function headerPath(): string
    {
        return dirname(__DIR__, 2).'/resources/curl_impersonate.h';
    }

    public static function detectLibraryPath(): string
    {
        $envPath = getenv('REQXIDE_CURL_IMPERSONATE_PATH');

        if ($envPath !== false && file_exists($envPath)) {
            return $envPath;
        }

        $paths = [
            '/usr/local/lib/libcurl-impersonate.so',
            '/usr/lib/libcurl-impersonate.so',
            '/usr/lib/x86_64-linux-gnu/libcurl-impersonate.so',
        ];

        if (PHP_OS_FAMILY === 'Darwin') {
            $paths = array_merge([
                '/usr/local/lib/libcurl-impersonate.dylib',
                '/opt/homebrew/lib/libcurl-impersonate.dylib',
            ], $paths);
        }

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new FfiException(
            'libcurl-impersonate library not found. Set REQXIDE_CURL_IMPERSONATE_PATH environment variable.',
        );
    }

    private function applyRequest(CData $handle, RequestInterface $request): void
    {
        $url = (string) $request->getUri();

        if ($url !== '') {
            $this->wrapper->easySetopt($handle, 10002, $url); // CURLOPT_URL
        }

        $method = $request->getMethod();

        if ($method !== '') {
            $this->wrapper->easySetopt($handle, 10036, $method); // CURLOPT_CUSTOMREQUEST
        }

        $body = (string) $request->getBody();

        if ($body !== '') {
            $this->wrapper->easySetopt($handle, 10015, $body); // CURLOPT_POSTFIELDS
            $this->wrapper->easySetopt($handle, 60, strlen($body)); // CURLOPT_POSTFIELDSIZE
        }
    }

    private function applyTlsOptions(CData $handle, ?TlsOptions $tls): void
    {
        if (! $tls instanceof TlsOptions) {
            return;
        }

        if ($tls->cipherList !== null && $tls->cipherList !== '') {
            $this->wrapper->easySetopt($handle, 10083, $tls->cipherList); // CURLOPT_SSL_CIPHER_LIST
        }

        if ($tls->curvesList !== null && $tls->curvesList !== '') {
            $this->wrapper->easySetopt($handle, 10306, $tls->curvesList); // CURLOPT_SSL_EC_CURVES
        }

        if ($tls->sigalgsList !== null && $tls->sigalgsList !== '') {
            $this->wrapper->easySetopt($handle, 10307, $tls->sigalgsList); // CURLOPT_SSL_SIG_HASH_ALGS
        }

        $sslVersion = $this->mapTlsVersion($tls->minTlsVersion, $tls->maxTlsVersion);

        if ($sslVersion !== null) {
            $this->wrapper->easySetopt($handle, 32, $sslVersion); // CURLOPT_SSLVERSION
        }

        if ($tls->alpnProtocols !== null) {
            foreach ($tls->alpnProtocols as $proto) {
                if ($proto === AlpnProtocol::Http2) {
                    $this->wrapper->easySetopt($handle, 84, 3); // CURL_HTTP_VERSION_2_0

                    break;
                }
            }
        }

        // curl_impersonate-specific TLS options
        if ($tls->greaseEnabled !== null) {
            $this->wrapper->easySetopt($handle, 314, $tls->greaseEnabled ? 1 : 0); // CURLOPT_TLS_GREASE
        }

        if ($tls->permuteExtensions !== null) {
            $this->wrapper->easySetopt($handle, 315, $tls->permuteExtensions ? 1 : 0); // CURLOPT_TLS_PERMUTE_EXTENSIONS
        }

        if ($tls->enableEchGrease) {
            $this->wrapper->easySetopt($handle, 10316, 'GREASE'); // CURLOPT_SSL_ECH
        }

        $this->wrapper->easySetopt($handle, 313, $tls->sessionTicket ? 1 : 0); // CURLOPT_SSL_ENABLE_TICKET
    }

    private function applyHttp2Options(CData $handle, ?Http2Options $http2): void
    {
        if (! $http2 instanceof Http2Options) {
            return;
        }

        // Ensure HTTP/2
        $this->wrapper->easySetopt($handle, 84, 3); // CURL_HTTP_VERSION_2_0

        // Pseudo header order
        if ($http2->headersPseudoOrder !== null) {
            $order = implode(',', array_map(
                static fn ($h): string => $h->value,
                $http2->headersPseudoOrder->headers,
            ));
            $this->wrapper->easySetopt($handle, 10318, $order); // CURLOPT_HTTP2_PSEUDO_HEADERS_ORDER
        }

        // Settings order and values
        if ($http2->settingsOrder !== null) {
            $settings = [];

            foreach ($http2->settingsOrder->settings as $settingId) {
                $value = match ($settingId) {
                    SettingId::HeaderTableSize => $http2->headerTableSize,
                    SettingId::EnablePush => $http2->enablePush !== null ? ($http2->enablePush ? 1 : 0) : null,
                    SettingId::MaxConcurrentStreams => $http2->maxConcurrentStreams,
                    SettingId::InitialWindowSize => $http2->initialWindowSize !== 65535 ? $http2->initialWindowSize : null,
                    SettingId::MaxFrameSize => $http2->maxFrameSize,
                    SettingId::MaxHeaderListSize => $http2->maxHeaderListSize,
                    SettingId::EnableConnectProtocol, SettingId::NoRfc7540Priorities => null,
                };

                if ($value !== null) {
                    $settings[] = $settingId->value.':'.$value;
                }
            }

            if ($settings !== []) {
                $this->wrapper->easySetopt($handle, 10319, implode(',', $settings)); // CURLOPT_HTTP2_SETTINGS
            }
        }

        // Connection window update
        if ($http2->initialConnWindowSize !== 65535) {
            $this->wrapper->easySetopt($handle, 320, $http2->initialConnWindowSize); // CURLOPT_HTTP2_WINDOW_UPDATE
        }
    }

    private function applyHeaders(CData $handle, RequestInterface $request, Profile $profile): ?CData
    {
        /** @var array<string, string> $headers */
        $headers = $profile->defaultHeaders;

        foreach ($request->getHeaders() as $name => $values) {
            $headers[(string) $name] = implode(', ', $values);
        }

        // Order headers according to OriginalHeaderMap if available
        if ($profile->originalHeaderMap instanceof OriginalHeaderMap) {
            $headers = $this->orderHeaders($headers, $profile->originalHeaderMap);
        }

        if ($headers === []) {
            return null;
        }

        $list = null;

        foreach ($headers as $name => $value) {
            $list = $this->wrapper->slistAppend($list, $name.': '.$value);
        }

        $this->wrapper->easySetopt($handle, 10023, $list); // CURLOPT_HTTPHEADER

        return $list;
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function orderHeaders(array $headers, OriginalHeaderMap $headerMap): array
    {
        $ordered = [];

        foreach ($headerMap->headerOrder as $name) {
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, $name) === 0) {
                    $ordered[$key] = $value;
                    unset($headers[$key]);

                    break;
                }
            }
        }

        foreach ($headers as $key => $value) {
            $ordered[$key] = $value;
        }

        return $ordered;
    }

    private function applyTransportOptions(CData $handle, TransportOptions $options): void
    {
        $this->wrapper->easySetopt($handle, 155, $options->timeoutMs); // CURLOPT_TIMEOUT_MS
        $this->wrapper->easySetopt($handle, 156, $options->connectTimeoutMs); // CURLOPT_CONNECTTIMEOUT_MS

        if ($options->verifySsl) {
            $this->wrapper->easySetopt($handle, 64, 1); // CURLOPT_SSL_VERIFYPEER
            $this->wrapper->easySetopt($handle, 81, 2); // CURLOPT_SSL_VERIFYHOST
        } else {
            $this->wrapper->easySetopt($handle, 64, 0); // CURLOPT_SSL_VERIFYPEER
            $this->wrapper->easySetopt($handle, 81, 0); // CURLOPT_SSL_VERIFYHOST
        }

        if ($options->caBundle !== null && $options->caBundle !== '') {
            $this->wrapper->easySetopt($handle, 10065, $options->caBundle); // CURLOPT_CAINFO
        }

        if ($options->proxy instanceof Proxy) {
            $this->wrapper->easySetopt($handle, 10004, $options->proxy->toUrl()); // CURLOPT_PROXY
            $this->wrapper->easySetopt($handle, 101, match ($options->proxy->scheme) {
                ProxyScheme::Http => 0,    // CURLPROXY_HTTP
                ProxyScheme::Https => 2,   // CURLPROXY_HTTPS
                ProxyScheme::Socks4 => 4,  // CURLPROXY_SOCKS4
                ProxyScheme::Socks5 => 7,  // CURLPROXY_SOCKS5
            }); // CURLOPT_PROXYTYPE

            if ($options->proxy->username !== null && $options->proxy->username !== '') {
                $auth = $options->proxy->username;

                if ($options->proxy->password !== null) {
                    $auth .= ':'.$options->proxy->password;
                }

                $this->wrapper->easySetopt($handle, 10006, $auth); // CURLOPT_PROXYUSERPWD
            }
        }
    }

    private function mapTlsVersion(?TlsVersion $min, ?TlsVersion $max): ?int
    {
        if (! $min instanceof TlsVersion && ! $max instanceof TlsVersion) {
            return null;
        }

        $minVersion = match ($min) {
            TlsVersion::TLS_1_0 => 4,  // CURL_SSLVERSION_TLSv1_0
            TlsVersion::TLS_1_1 => 5,  // CURL_SSLVERSION_TLSv1_1
            TlsVersion::TLS_1_2 => 6,  // CURL_SSLVERSION_TLSv1_2
            TlsVersion::TLS_1_3 => 7,  // CURL_SSLVERSION_TLSv1_3
            null => 0,                  // CURL_SSLVERSION_DEFAULT
        };

        $maxVersion = match ($max) {
            TlsVersion::TLS_1_0 => 262144,  // CURL_SSLVERSION_MAX_TLSv1_0
            TlsVersion::TLS_1_1 => 327680,  // CURL_SSLVERSION_MAX_TLSv1_1
            TlsVersion::TLS_1_2 => 393216,  // CURL_SSLVERSION_MAX_TLSv1_2
            TlsVersion::TLS_1_3 => 458752,  // CURL_SSLVERSION_MAX_TLSv1_3
            null => 65536,                   // CURL_SSLVERSION_MAX_DEFAULT
        };

        return $minVersion | $maxVersion;
    }
}
