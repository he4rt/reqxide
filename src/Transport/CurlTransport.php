<?php

declare(strict_types=1);

namespace Reqxide\Transport;

use CurlHandle;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Profile;
use Reqxide\Exception\NetworkException;
use Reqxide\Http1\OriginalHeaderMap;
use Reqxide\Http2\Http2Options;
use Reqxide\Proxy\Proxy;
use Reqxide\Proxy\ProxyScheme;
use Reqxide\Tls\AlpnProtocol;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsVersion;

final class CurlTransport implements TransportInterface
{
    public function send(
        RequestInterface $request,
        Profile $profile,
        TransportOptions $options,
    ): ResponseInterface {
        $ch = curl_init();

        try {
            $this->applyRequest($ch, $request);
            $this->applyTlsOptions($ch, $profile->tlsOptions);
            $this->applyHttp2Options($ch, $profile->http2Options);
            $this->applyHeaders($ch, $request, $profile);
            $this->applyTransportOptions($ch, $options);

            /** @var array<string, list<string>> $responseHeaders */
            $responseHeaders = [];
            $responseBody = '';

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            $lastHeaderName = '';
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function (CurlHandle $ch, string $header) use (&$responseHeaders, &$lastHeaderName): int {
                $length = strlen($header);
                $trimmed = trim($header);

                if ($trimmed === '' || str_starts_with($trimmed, 'HTTP/')) {
                    $lastHeaderName = '';

                    return $length;
                }

                // Handle folded headers (continuation lines starting with space/tab)
                if (($header[0] === ' ' || $header[0] === "\t") && $lastHeaderName !== '') {
                    /** @var array<string, list<string>> $responseHeaders */
                    $existing = array_pop($responseHeaders[$lastHeaderName]) ?? '';
                    $responseHeaders[$lastHeaderName][] = $existing.' '.$trimmed;

                    return $length;
                }

                $parts = explode(':', $trimmed, 2);

                if (count($parts) === 2) {
                    $name = trim($parts[0]);
                    /** @var array<string, list<string>> $responseHeaders */
                    $responseHeaders[$name][] = trim($parts[1]);
                    $lastHeaderName = $name;
                }

                return $length;
            });
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function (CurlHandle $ch, string $data) use (&$responseBody): int {
                $responseBody .= $data;

                return strlen($data);
            });

            // Disable curl's built-in redirect handling (middleware handles this)
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

            // Enable curl's built-in decompression (gzip, deflate, br, zstd)
            // The Accept-Encoding header from the profile is sent for fingerprinting,
            // but curl handles the actual decompression transparently.
            curl_setopt($ch, CURLOPT_ENCODING, '');

            $result = curl_exec($ch);

            if ($result === false) {
                throw new NetworkException($request, 'cURL error: '.curl_error($ch), curl_errno($ch));
            }

            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            /** @var array<string, list<string>> $responseHeaders */
            return new Response($statusCode, $responseHeaders, $responseBody);
        } finally {
            // CurlHandle auto-closes on destruction (PHP 8.0+)
            unset($ch);
        }
    }

    public function supportsFingerprinting(): bool
    {
        return false;
    }

    public function supportsHttp2Configuration(): bool
    {
        return false;
    }

    private function applyRequest(CurlHandle $ch, RequestInterface $request): void
    {
        $url = (string) $request->getUri();

        if ($url !== '') {
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        $method = $request->getMethod();

        if ($method !== '') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $body = (string) $request->getBody();

        if ($body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
    }

    private function applyTlsOptions(CurlHandle $ch, ?TlsOptions $tls): void
    {
        if (! $tls instanceof TlsOptions) {
            return;
        }

        if ($tls->cipherList !== null && $tls->cipherList !== '') {
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, $tls->cipherList);
        }

        if ($tls->curvesList !== null && $tls->curvesList !== '' && defined('CURLOPT_SSL_EC_CURVES')) {
            curl_setopt($ch, CURLOPT_SSL_EC_CURVES, $tls->curvesList);
        }

        $sslVersion = $this->mapTlsVersion($tls->minTlsVersion, $tls->maxTlsVersion);

        if ($sslVersion !== null) {
            curl_setopt($ch, CURLOPT_SSLVERSION, $sslVersion);
        }

        if ($tls->alpnProtocols !== null) {
            foreach ($tls->alpnProtocols as $proto) {
                if ($proto === AlpnProtocol::Http2) {
                    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);

                    break;
                }
            }
        }
    }

    private function applyHttp2Options(CurlHandle $ch, ?Http2Options $http2): void
    {
        if (! $http2 instanceof Http2Options) {
            return;
        }

        // Native ext/curl cannot control HTTP/2 SETTINGS order, pseudo-header
        // order, GREASE, etc. — those need FFI.  We simply ensure HTTP/2 is
        // negotiated so the server sees an h2 connection.
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
    }

    private function applyHeaders(CurlHandle $ch, RequestInterface $request, Profile $profile): void
    {
        // Merge profile default headers with request headers (request takes precedence)
        /** @var array<string, string> $headers */
        $headers = $profile->defaultHeaders;

        foreach ($request->getHeaders() as $name => $values) {
            $headers[(string) $name] = implode(', ', $values);
        }

        // Order headers according to OriginalHeaderMap if available
        if ($profile->originalHeaderMap instanceof OriginalHeaderMap) {
            $headers = $this->orderHeaders($headers, $profile->originalHeaderMap);
        }

        $curlHeaders = [];

        foreach ($headers as $name => $value) {
            $curlHeaders[] = $name.': '.$value;
        }

        if ($curlHeaders !== []) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        }
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function orderHeaders(array $headers, OriginalHeaderMap $headerMap): array
    {
        $ordered = [];

        // First, add headers in the order specified by the header map
        foreach ($headerMap->headerOrder as $name) {
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, $name) === 0) {
                    $ordered[$key] = $value;
                    unset($headers[$key]);

                    break;
                }
            }
        }

        // Then add remaining headers not in the map
        foreach ($headers as $key => $value) {
            $ordered[$key] = $value;
        }

        return $ordered;
    }

    private function applyTransportOptions(CurlHandle $ch, TransportOptions $options): void
    {
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $options->timeoutMs);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $options->connectTimeoutMs);

        if ($options->verifySsl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        if ($options->caBundle !== null && $options->caBundle !== '') {
            curl_setopt($ch, CURLOPT_CAINFO, $options->caBundle);
        }

        if ($options->proxy instanceof Proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $options->proxy->toUrl());
            curl_setopt($ch, CURLOPT_PROXYTYPE, match ($options->proxy->scheme) {
                ProxyScheme::Http => CURLPROXY_HTTP,
                ProxyScheme::Https => CURLPROXY_HTTPS,
                ProxyScheme::Socks4 => CURLPROXY_SOCKS4,
                ProxyScheme::Socks5 => CURLPROXY_SOCKS5,
            });

            if ($options->proxy->username !== null && $options->proxy->username !== '') {
                $auth = $options->proxy->username;

                if ($options->proxy->password !== null) {
                    $auth .= ':'.$options->proxy->password;
                }

                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $auth);
            }
        }
    }

    private function mapTlsVersion(?TlsVersion $min, ?TlsVersion $max): ?int
    {
        if (! $min instanceof TlsVersion && ! $max instanceof TlsVersion) {
            return null;
        }

        $minVersion = match ($min) {
            TlsVersion::TLS_1_0 => CURL_SSLVERSION_TLSv1_0,
            TlsVersion::TLS_1_1 => CURL_SSLVERSION_TLSv1_1,
            TlsVersion::TLS_1_2 => CURL_SSLVERSION_TLSv1_2,
            TlsVersion::TLS_1_3 => CURL_SSLVERSION_TLSv1_3,
            null => CURL_SSLVERSION_DEFAULT,
        };

        $maxVersion = match ($max) {
            TlsVersion::TLS_1_0 => CURL_SSLVERSION_MAX_TLSv1_0,
            TlsVersion::TLS_1_1 => CURL_SSLVERSION_MAX_TLSv1_1,
            TlsVersion::TLS_1_2 => CURL_SSLVERSION_MAX_TLSv1_2,
            TlsVersion::TLS_1_3 => CURL_SSLVERSION_MAX_TLSv1_3,
            null => CURL_SSLVERSION_MAX_DEFAULT,
        };

        return $minVersion | $maxVersion;
    }
}
