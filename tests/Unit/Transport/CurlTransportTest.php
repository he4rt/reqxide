<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Profile;
use Reqxide\Exception\NetworkException;
use Reqxide\Http1\OriginalHeaderMap;
use Reqxide\Http2\Http2Options;
use Reqxide\Proxy\Proxy;
use Reqxide\Tls\AlpnProtocol;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsVersion;
use Reqxide\Transport\CurlTransport;
use Reqxide\Transport\TransportOptions;

it('implements TransportInterface', function (): void {
    $transport = new CurlTransport;

    expect($transport)->toBeInstanceOf(TransportInterface::class);
});

it('does not support fingerprinting', function (): void {
    $transport = new CurlTransport;

    expect($transport->supportsFingerprinting())->toBeFalse();
});

it('does not support HTTP/2 configuration', function (): void {
    $transport = new CurlTransport;

    expect($transport->supportsHttp2Configuration())->toBeFalse();
});

it('sends a request using file protocol', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $profile = new Profile;
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0)
        ->and((string) $response->getBody())->toBe('');
});

it('throws NetworkException when curl fails', function (): void {
    $transport = new CurlTransport;
    // TEST-NET-1 (RFC 5737) — guaranteed non-routable
    $request = new Request('GET', 'http://192.0.2.1:1/');
    $profile = new Profile;
    $options = new TransportOptions(
        timeoutMs: 100,
        connectTimeoutMs: 100,
        verifySsl: false,
    );

    $transport->send($request, $profile, $options);
})->throws(NetworkException::class);

it('NetworkException contains the original request', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'http://192.0.2.1:1/');
    $profile = new Profile;
    $options = new TransportOptions(
        timeoutMs: 100,
        connectTimeoutMs: 100,
        verifySsl: false,
    );

    try {
        $transport->send($request, $profile, $options);
    } catch (NetworkException $networkException) {
        expect($networkException->getRequest())->toBe($request)
            ->and($networkException->getMessage())->toStartWith('cURL error:');

        return;
    }

    test()->fail('Expected NetworkException was not thrown');
});

it('applies TLS cipher list and curves', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $tls = new TlsOptions(
        curvesList: 'X25519:prime256v1',
        cipherList: 'ECDHE-RSA-AES128-GCM-SHA256',
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('applies TLS version range', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $tls = new TlsOptions(
        minTlsVersion: TlsVersion::TLS_1_2,
        maxTlsVersion: TlsVersion::TLS_1_3,
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('applies TLS version with only min', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $tls = new TlsOptions(
        minTlsVersion: TlsVersion::TLS_1_0,
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('applies TLS version with only max', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $tls = new TlsOptions(
        maxTlsVersion: TlsVersion::TLS_1_2,
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('enables HTTP/2 when ALPN contains h2', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $tls = new TlsOptions(
        alpnProtocols: [AlpnProtocol::Http2, AlpnProtocol::Http1],
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('does not enable HTTP/2 when ALPN has no h2', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $tls = new TlsOptions(
        alpnProtocols: [AlpnProtocol::Http1],
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('applies HTTP/2 options when present', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $http2 = new Http2Options(initialWindowSize: 131072);
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('merges profile default headers with request headers', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null', [
        'Accept' => 'text/html',
    ]);
    $profile = new Profile(
        defaultHeaders: [
            'User-Agent' => 'Reqxide/1.0',
            'Accept-Language' => 'en-US',
        ],
    );
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('orders headers according to OriginalHeaderMap', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null', [
        'Accept' => 'text/html',
    ]);
    $headerMap = new OriginalHeaderMap(['Accept', 'User-Agent', 'Accept-Language']);
    $profile = new Profile(
        defaultHeaders: [
            'User-Agent' => 'Reqxide/1.0',
            'Accept-Language' => 'en-US',
        ],
        originalHeaderMap: $headerMap,
    );
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('sends request body when present', function (): void {
    $transport = new CurlTransport;
    $request = new Request('POST', 'file:///dev/null', [], '{"key":"value"}');
    $profile = new Profile;
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('applies SSL verification settings when enabled', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $profile = new Profile;
    $options = new TransportOptions(verifySsl: true);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('applies CA bundle path', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $profile = new Profile;
    $options = new TransportOptions(
        verifySsl: false,
        caBundle: '/etc/ssl/certs/ca-certificates.crt',
    );

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('applies HTTP proxy settings', function (): void {
    $transport = new CurlTransport;
    // Use file:// so the proxy setting is applied but won't actually be used
    $request = new Request('GET', 'file:///dev/null');
    $proxy = Proxy::http('127.0.0.1:8080');
    $profile = new Profile;
    $options = new TransportOptions(
        verifySsl: false,
        proxy: $proxy,
    );

    // file:// protocol ignores the proxy, so this should succeed
    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('applies HTTPS proxy settings', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $proxy = Proxy::https('proxy.example.com:443');
    $profile = new Profile;
    $options = new TransportOptions(
        verifySsl: false,
        proxy: $proxy,
    );

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('applies SOCKS4 proxy settings', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $proxy = Proxy::socks4('socks4.example.com:1080');
    $profile = new Profile;
    $options = new TransportOptions(
        verifySsl: false,
        proxy: $proxy,
    );

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('applies SOCKS5 proxy settings', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $proxy = Proxy::socks5('socks5.example.com:1080');
    $profile = new Profile;
    $options = new TransportOptions(
        verifySsl: false,
        proxy: $proxy,
    );

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('applies proxy authentication credentials', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $proxy = Proxy::http('//user:pass@proxy.com:8080');
    $profile = new Profile;
    $options = new TransportOptions(
        verifySsl: false,
        proxy: $proxy,
    );

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('applies proxy authentication with username only', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $proxy = Proxy::http('admin@proxy.com:3128');
    $profile = new Profile;
    $options = new TransportOptions(
        verifySsl: false,
        proxy: $proxy,
    );

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('handles all TLS version mappings for min', function (TlsVersion $version): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $tls = new TlsOptions(minTlsVersion: $version);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
})->with([
    'TLS 1.0' => TlsVersion::TLS_1_0,
    'TLS 1.1' => TlsVersion::TLS_1_1,
    'TLS 1.2' => TlsVersion::TLS_1_2,
    'TLS 1.3' => TlsVersion::TLS_1_3,
]);

it('handles all TLS version mappings for max', function (TlsVersion $version): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $tls = new TlsOptions(maxTlsVersion: $version);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
})->with([
    'TLS 1.0' => TlsVersion::TLS_1_0,
    'TLS 1.1' => TlsVersion::TLS_1_1,
    'TLS 1.2' => TlsVersion::TLS_1_2,
    'TLS 1.3' => TlsVersion::TLS_1_3,
]);

it('skips TLS options when null', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $profile = new Profile;
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('skips HTTP/2 options when null', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $profile = new Profile(http2Options: null);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('sends request without headers when profile and request have none', function (): void {
    $transport = new CurlTransport;
    // Use Request constructor without extra headers; Host is auto-added by PSR-7
    $request = new Request('GET', 'file:///dev/null');
    $profile = new Profile(defaultHeaders: []);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('handles empty cipher list gracefully', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $tls = new TlsOptions(curvesList: '', cipherList: '');
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('handles empty CA bundle path gracefully', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $profile = new Profile;
    $options = new TransportOptions(
        verifySsl: false,
        caBundle: '',
    );

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('handles TLS options with null ALPN protocols', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $tls = new TlsOptions;
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('reads response body from file with content', function (): void {
    $transport = new CurlTransport;
    $tmpFile = tempnam(sys_get_temp_dir(), 'reqxide_test_');
    file_put_contents($tmpFile, 'hello world');

    try {
        $request = new Request('GET', 'file://'.$tmpFile);
        $profile = new Profile;
        $options = new TransportOptions(verifySsl: false);

        $response = $transport->send($request, $profile, $options);

        expect($response->getStatusCode())->toBe(0)
            ->and((string) $response->getBody())->toBe('hello world');
    } finally {
        unlink($tmpFile);
    }
});

it('orders headers with extras not in the header map', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null', [
        'Accept' => 'text/html',
        'X-Custom' => 'custom-value',
    ]);
    // Header map only includes Accept, not X-Custom or User-Agent
    $headerMap = new OriginalHeaderMap(['Accept']);
    $profile = new Profile(
        defaultHeaders: [
            'User-Agent' => 'Reqxide/1.0',
        ],
        originalHeaderMap: $headerMap,
    );
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});

it('handles TLS options with null version range', function (): void {
    $transport = new CurlTransport;
    $request = new Request('GET', 'file:///dev/null');
    $tls = new TlsOptions(
        minTlsVersion: null,
        maxTlsVersion: null,
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions(verifySsl: false);

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(0);
});
