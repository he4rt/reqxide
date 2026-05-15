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
use Reqxide\Http2\PseudoHeader;
use Reqxide\Http2\SettingId;
use Reqxide\Proxy\Proxy;
use Reqxide\Proxy\ProxyScheme;
use Reqxide\Tls\AlpnProtocol;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsVersion;
use Reqxide\Transport\Ffi\FfiWrapper;

/**
 * FFI transport using libcurl-impersonate for full TLS/HTTP2 fingerprint control.
 *
 * Response capture uses temp files (CURLOPT_WRITEDATA/CURLOPT_HEADERDATA) since
 * PHP FFI cannot register C function pointer callbacks (CURLOPT_WRITEFUNCTION).
 * Temp files are cleaned up automatically in the finally block.
 *
 * @todo NOT PRODUCTION READY — Known issues:
 *   1. curl_easy_getinfo returns the CURLcode (0=ok), not the actual status code.
 *      Needs pointer-based extraction: FFI::new("long") + FFI::addr().
 *   2. CURLOPT_ENCODING (auto-decompression) is not set, so brotli/gzip responses
 *      come back compressed. CurlTransport handles this with CURLOPT_ENCODING=''.
 *   3. TLS fingerprint differs from CurlTransport because curl_easy_setopt via FFI
 *      may encode cipher/curve strings differently than ext/curl.
 *   Use CurlTransport (default) or ProcessTransport until these are resolved.
 */
final class FfiTransport implements TransportInterface
{
    // Standard CURLOPT constants
    private const int CURLOPT_URL = 10002;

    private const int CURLOPT_CUSTOMREQUEST = 10036;

    private const int CURLOPT_POSTFIELDS = 10015;

    private const int CURLOPT_POSTFIELDSIZE = 60;

    private const int CURLOPT_HTTPHEADER = 10023;

    private const int CURLOPT_FOLLOWLOCATION = 52;

    private const int CURLOPT_TIMEOUT_MS = 155;

    private const int CURLOPT_CONNECTTIMEOUT_MS = 156;

    private const int CURLOPT_SSL_VERIFYPEER = 64;

    private const int CURLOPT_SSL_VERIFYHOST = 81;

    private const int CURLOPT_PROXY = 10004;

    private const int CURLOPT_PROXYTYPE = 101;

    private const int CURLOPT_PROXYUSERPWD = 10006;

    private const int CURLOPT_CAINFO = 10065;

    private const int CURLOPT_SSLVERSION = 32;

    private const int CURLOPT_HTTP_VERSION = 84;

    private const int CURLOPT_SSL_CIPHER_LIST = 10083;

    // curl-impersonate specific options
    private const int CURLOPT_SSL_EC_CURVES = 10306;

    private const int CURLOPT_SSL_SIG_HASH_ALGS = 10307;

    private const int CURLOPT_SSL_ENABLE_TICKET = 313;

    private const int CURLOPT_TLS_GREASE = 314;

    private const int CURLOPT_TLS_PERMUTE_EXTENSIONS = 315;

    private const int CURLOPT_SSL_ECH = 10316;

    private const int CURLOPT_HTTP2_PSEUDO_HEADERS_ORDER = 10318;

    private const int CURLOPT_HTTP2_SETTINGS = 10319;

    private const int CURLOPT_HTTP2_WINDOW_UPDATE = 320;

    // Response capture via temp files
    private const int CURLOPT_WRITEDATA = 10001;

    private const int CURLOPT_HEADERDATA = 10029;

    // Info and version constants
    private const int CURLINFO_RESPONSE_CODE = 2097154;

    private const int CURL_HTTP_VERSION_2_0 = 3;

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
        $bodyFile = tempnam(sys_get_temp_dir(), 'reqxide_body_');
        $headerFile = tempnam(sys_get_temp_dir(), 'reqxide_hdr_');

        if ($bodyFile === false || $headerFile === false) {
            throw new FfiException('Failed to create temp files for response capture.'); // @codeCoverageIgnore
        }

        $bodyFp = $this->wrapper->fopen($bodyFile, 'wb');
        $headerFp = $this->wrapper->fopen($headerFile, 'wb');

        try {
            $this->applyRequest($handle, $request);
            $this->applyTlsOptions($handle, $profile->tlsOptions);
            $this->applyHttp2Options($handle, $profile->http2Options);
            $headerList = $this->applyHeaders($handle, $request, $profile);
            $this->applyTransportOptions($handle, $options);

            // Redirect response body and headers to temp files
            $this->wrapper->easySetopt($handle, self::CURLOPT_WRITEDATA, $bodyFp);
            $this->wrapper->easySetopt($handle, self::CURLOPT_HEADERDATA, $headerFp);

            // Disable redirect following (middleware handles it)
            $this->wrapper->easySetopt($handle, self::CURLOPT_FOLLOWLOCATION, 0);

            $result = $this->wrapper->easyPerform($handle);

            if ($result !== 0) {
                throw new NetworkException(
                    $request,
                    'curl_impersonate error: '.$this->wrapper->easyStrerror($result),
                    $result,
                );
            }

            // Close file handles so data is flushed
            $this->wrapper->fclose($bodyFp);
            $this->wrapper->fclose($headerFp);
            $bodyFp = null;
            $headerFp = null;

            // Read captured response
            $body = file_get_contents($bodyFile);
            $rawHeaders = file_get_contents($headerFile);

            // Get status code
            $statusCode = $this->wrapper->easyGetinfo($handle, self::CURLINFO_RESPONSE_CODE);

            // Parse response headers
            $headers = $this->parseResponseHeaders($rawHeaders !== false ? $rawHeaders : '');

            return new Response($statusCode, $headers, $body !== false ? $body : '');
        } finally {
            if ($headerList !== null) {
                $this->wrapper->slistFreeAll($headerList);
            }

            if ($bodyFp !== null) {
                $this->wrapper->fclose($bodyFp);
            }

            if ($headerFp !== null) {
                $this->wrapper->fclose($headerFp);
            }

            $this->wrapper->easyCleanup($handle);

            // Clean up temp files
            if (file_exists($bodyFile)) {
                unlink($bodyFile);
            }

            if (file_exists($headerFile)) {
                unlink($headerFile);
            }
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

        // @codeCoverageIgnoreStart
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
        // @codeCoverageIgnoreEnd
    }

    private function applyRequest(CData $handle, RequestInterface $request): void
    {
        $url = (string) $request->getUri();

        if ($url !== '') {
            $this->wrapper->easySetopt($handle, self::CURLOPT_URL, $url); // CURLOPT_URL
        }

        $method = $request->getMethod();

        if ($method !== '') {
            $this->wrapper->easySetopt($handle, self::CURLOPT_CUSTOMREQUEST, $method); // CURLOPT_CUSTOMREQUEST
        }

        $body = (string) $request->getBody();

        if ($body !== '') {
            $this->wrapper->easySetopt($handle, self::CURLOPT_POSTFIELDS, $body); // CURLOPT_POSTFIELDS
            $this->wrapper->easySetopt($handle, self::CURLOPT_POSTFIELDSIZE, strlen($body)); // CURLOPT_POSTFIELDSIZE
        }
    }

    private function applyTlsOptions(CData $handle, ?TlsOptions $tls): void
    {
        if (! $tls instanceof TlsOptions) {
            return;
        }

        if ($tls->cipherList !== null && $tls->cipherList !== '') {
            $this->wrapper->easySetopt($handle, self::CURLOPT_SSL_CIPHER_LIST, $tls->cipherList); // CURLOPT_SSL_CIPHER_LIST
        }

        if ($tls->curvesList !== null && $tls->curvesList !== '') {
            $this->wrapper->easySetopt($handle, self::CURLOPT_SSL_EC_CURVES, $tls->curvesList); // CURLOPT_SSL_EC_CURVES
        }

        if ($tls->sigalgsList !== null && $tls->sigalgsList !== '') {
            $this->wrapper->easySetopt($handle, self::CURLOPT_SSL_SIG_HASH_ALGS, $tls->sigalgsList); // CURLOPT_SSL_SIG_HASH_ALGS
        }

        $sslVersion = $this->mapTlsVersion($tls->minTlsVersion, $tls->maxTlsVersion);

        if ($sslVersion !== null) {
            $this->wrapper->easySetopt($handle, self::CURLOPT_SSLVERSION, $sslVersion); // CURLOPT_SSLVERSION
        }

        if ($tls->alpnProtocols !== null) {
            foreach ($tls->alpnProtocols as $proto) {
                if ($proto === AlpnProtocol::Http2) {
                    $this->wrapper->easySetopt($handle, self::CURLOPT_HTTP_VERSION, self::CURL_HTTP_VERSION_2_0); // CURL_HTTP_VERSION_2_0

                    break;
                }
            }
        }

        // curl_impersonate-specific TLS options
        if ($tls->greaseEnabled !== null) {
            $this->wrapper->easySetopt($handle, self::CURLOPT_TLS_GREASE, $tls->greaseEnabled ? 1 : 0); // CURLOPT_TLS_GREASE
        }

        if ($tls->permuteExtensions !== null) {
            $this->wrapper->easySetopt($handle, self::CURLOPT_TLS_PERMUTE_EXTENSIONS, $tls->permuteExtensions ? 1 : 0); // CURLOPT_TLS_PERMUTE_EXTENSIONS
        }

        if ($tls->enableEchGrease) {
            $this->wrapper->easySetopt($handle, self::CURLOPT_SSL_ECH, 'GREASE'); // CURLOPT_SSL_ECH
        }

        $this->wrapper->easySetopt($handle, self::CURLOPT_SSL_ENABLE_TICKET, $tls->sessionTicket ? 1 : 0); // CURLOPT_SSL_ENABLE_TICKET
    }

    private function applyHttp2Options(CData $handle, ?Http2Options $http2): void
    {
        if (! $http2 instanceof Http2Options) {
            return;
        }

        // Ensure HTTP/2
        $this->wrapper->easySetopt($handle, self::CURLOPT_HTTP_VERSION, self::CURL_HTTP_VERSION_2_0); // CURL_HTTP_VERSION_2_0

        // Pseudo header order
        if ($http2->headersPseudoOrder !== null) {
            $order = implode(',', array_map(
                static fn (PseudoHeader $h): string => $h->value,
                $http2->headersPseudoOrder->headers,
            ));
            $this->wrapper->easySetopt($handle, self::CURLOPT_HTTP2_PSEUDO_HEADERS_ORDER, $order); // CURLOPT_HTTP2_PSEUDO_HEADERS_ORDER
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
                $this->wrapper->easySetopt($handle, self::CURLOPT_HTTP2_SETTINGS, implode(',', $settings)); // CURLOPT_HTTP2_SETTINGS
            }
        }

        // Connection window update
        if ($http2->initialConnWindowSize !== 65535) {
            $this->wrapper->easySetopt($handle, self::CURLOPT_HTTP2_WINDOW_UPDATE, $http2->initialConnWindowSize); // CURLOPT_HTTP2_WINDOW_UPDATE
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
            return null; // @codeCoverageIgnore
        }

        $list = null;

        foreach ($headers as $name => $value) {
            $list = $this->wrapper->slistAppend($list, $name.': '.$value);
        }

        $this->wrapper->easySetopt($handle, self::CURLOPT_HTTPHEADER, $list); // CURLOPT_HTTPHEADER

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
        $this->wrapper->easySetopt($handle, self::CURLOPT_TIMEOUT_MS, $options->timeoutMs); // CURLOPT_TIMEOUT_MS
        $this->wrapper->easySetopt($handle, self::CURLOPT_CONNECTTIMEOUT_MS, $options->connectTimeoutMs); // CURLOPT_CONNECTTIMEOUT_MS

        if ($options->verifySsl) {
            $this->wrapper->easySetopt($handle, self::CURLOPT_SSL_VERIFYPEER, 1); // CURLOPT_SSL_VERIFYPEER
            $this->wrapper->easySetopt($handle, self::CURLOPT_SSL_VERIFYHOST, 2); // CURLOPT_SSL_VERIFYHOST
        } else {
            $this->wrapper->easySetopt($handle, self::CURLOPT_SSL_VERIFYPEER, 0); // CURLOPT_SSL_VERIFYPEER
            $this->wrapper->easySetopt($handle, self::CURLOPT_SSL_VERIFYHOST, 0); // CURLOPT_SSL_VERIFYHOST
        }

        if ($options->caBundle !== null && $options->caBundle !== '') {
            $this->wrapper->easySetopt($handle, self::CURLOPT_CAINFO, $options->caBundle); // CURLOPT_CAINFO
        }

        if ($options->proxy instanceof Proxy) {
            $this->wrapper->easySetopt($handle, self::CURLOPT_PROXY, $options->proxy->toUrl()); // CURLOPT_PROXY
            $this->wrapper->easySetopt($handle, self::CURLOPT_PROXYTYPE, match ($options->proxy->scheme) {
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

                $this->wrapper->easySetopt($handle, self::CURLOPT_PROXYUSERPWD, $auth); // CURLOPT_PROXYUSERPWD
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

    /**
     * Parse raw HTTP headers into an associative array.
     *
     * @return array<string, list<string>>
     */
    private function parseResponseHeaders(string $raw): array
    {
        $headers = [];
        $lines = preg_split('/\r?\n/', $raw);

        if ($lines === false) {
            return []; // @codeCoverageIgnore
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, 'HTTP/')) {
                continue;
            }

            $parts = explode(':', $trimmed, 2); // @codeCoverageIgnoreStart

            if (count($parts) === 2) {
                $headers[trim($parts[0])][] = trim($parts[1]);
            } // @codeCoverageIgnoreEnd
        }

        return $headers;
    }
}
