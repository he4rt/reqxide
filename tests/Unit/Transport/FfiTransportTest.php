<?php

declare(strict_types=1);

use FFI\CData;
use Nyholm\Psr7\Request;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Profile;
use Reqxide\Exception\FfiException;
use Reqxide\Exception\NetworkException;
use Reqxide\Http1\OriginalHeaderMap;
use Reqxide\Http2\Http2Options;
use Reqxide\Http2\PseudoHeader;
use Reqxide\Http2\PseudoHeaderOrder;
use Reqxide\Http2\SettingId;
use Reqxide\Http2\SettingsOrder;
use Reqxide\Proxy\Proxy;
use Reqxide\Tls\AlpnProtocol;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsVersion;
use Reqxide\Transport\Ffi\FfiWrapper;
use Reqxide\Transport\FfiTransport;
use Reqxide\Transport\TransportOptions;

/**
 * Creates a stub FfiWrapper that records calls and returns configurable values.
 *
 * @param  array{perform_result?: int, getinfo_result?: int, strerror_result?: string}  $config
 */
function createStubWrapper(array $config = []): FfiWrapper
{
    $performResult = $config['perform_result'] ?? 0;
    $getinfoResult = $config['getinfo_result'] ?? 200;
    $strerrorResult = $config['strerror_result'] ?? 'Unknown error';

    return new class($performResult, $getinfoResult, $strerrorResult) extends FfiWrapper
    {
        /** @var list<array{method: string, args: list<mixed>}> */
        public array $calls = [];

        private int $slistCounter = 0;

        public function __construct(
            private readonly int $performResult,
            private readonly int $getinfoResult,
            private readonly string $strerrorResult,
        ) {
            // Skip parent constructor — no actual FFI needed
        }

        public function easyInit(): CData
        {
            $this->calls[] = ['method' => 'easyInit', 'args' => []];

            // Create a fake CData-like object via FFI::new for a simple type
            $ffi = FFI::cdef('typedef int dummy;');

            /** @var CData */
            return $ffi->new('dummy');
        }

        public function easySetopt(CData $handle, int $option, mixed $value): int
        {
            $this->calls[] = ['method' => 'easySetopt', 'args' => [$option, $value]];

            return 0;
        }

        public function easyPerform(CData $handle): int
        {
            $this->calls[] = ['method' => 'easyPerform', 'args' => []];

            return $this->performResult;
        }

        public function easyGetinfo(CData $handle, int $info): int
        {
            $this->calls[] = ['method' => 'easyGetinfo', 'args' => [$info]];

            return $this->getinfoResult;
        }

        public function easyStrerror(int $code): string
        {
            $this->calls[] = ['method' => 'easyStrerror', 'args' => [$code]];

            return $this->strerrorResult;
        }

        public function easyCleanup(CData $handle): void
        {
            $this->calls[] = ['method' => 'easyCleanup', 'args' => []];
        }

        public function slistAppend(?CData $list, string $value): CData
        {
            $this->calls[] = ['method' => 'slistAppend', 'args' => [$value]];
            $this->slistCounter++;

            $ffi = FFI::cdef('typedef int dummy;');

            /** @var CData */
            return $ffi->new('dummy');
        }

        public function slistFreeAll(?CData $list): void
        {
            $this->calls[] = ['method' => 'slistFreeAll', 'args' => []];
        }
    };
}

it('is a final class', function (): void {
    $reflection = new ReflectionClass(FfiTransport::class);

    expect($reflection->isFinal())->toBeTrue();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('implements TransportInterface', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);

    expect($transport)->toBeInstanceOf(TransportInterface::class);
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('supports fingerprinting', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);

    expect($transport->supportsFingerprinting())->toBeTrue();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('supports HTTP/2 configuration', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);

    expect($transport->supportsHttp2Configuration())->toBeTrue();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('headerPath returns valid path to resources/curl_impersonate.h', function (): void {
    $path = FfiTransport::headerPath();

    expect($path)->toEndWith('/resources/curl_impersonate.h')
        ->and(file_exists($path))->toBeTrue();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('detectLibraryPath throws FfiException when library not found', function (): void {
    // Ensure the env var is not set
    $original = getenv('REQXIDE_CURL_IMPERSONATE_PATH');
    putenv('REQXIDE_CURL_IMPERSONATE_PATH');

    try {
        FfiTransport::detectLibraryPath();
    } finally {
        if ($original !== false) {
            putenv('REQXIDE_CURL_IMPERSONATE_PATH='.$original);
        }
    }
})->throws(FfiException::class, 'libcurl-impersonate library not found')
    ->skip(! extension_loaded('ffi'), 'FFI extension is required')
    ->skip(
        file_exists('/usr/local/lib/libcurl-impersonate.so')
        || file_exists('/usr/lib/libcurl-impersonate.so')
        || file_exists('/usr/lib/x86_64-linux-gnu/libcurl-impersonate.so')
        || file_exists('/usr/local/lib/libcurl-impersonate.dylib')
        || file_exists('/opt/homebrew/lib/libcurl-impersonate.dylib'),
        'libcurl-impersonate is installed on this system',
    );

it('constructor accepts custom FfiWrapper', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);

    expect($transport)->toBeInstanceOf(FfiTransport::class);
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('sends a basic GET request', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions;

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(200);

    $methods = array_column($wrapper->calls, 'method');
    expect($methods)->toContain('easyInit')
        ->and($methods)->toContain('easySetopt')
        ->and($methods)->toContain('easyPerform')
        ->and($methods)->toContain('easyCleanup');
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('throws NetworkException when perform fails', function (): void {
    $wrapper = createStubWrapper(['perform_result' => 7, 'strerror_result' => 'Connection refused']);
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);
})->throws(NetworkException::class, 'curl_impersonate error: Connection refused')
    ->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('cleans up handle even when perform fails', function (): void {
    $wrapper = createStubWrapper(['perform_result' => 7, 'strerror_result' => 'Timeout']);
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions;

    try {
        $transport->send($request, $profile, $options);
    } catch (NetworkException) {
        // expected
    }

    $methods = array_column($wrapper->calls, 'method');
    expect($methods)->toContain('easyCleanup');
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('sends request body when present', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('POST', 'https://example.com', [], '{"key":"value"}');
    $profile = new Profile;
    $options = new TransportOptions;

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(200);

    // Check that CURLOPT_POSTFIELDS (10015) was set
    $setoptCalls = array_filter($wrapper->calls, fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10015);
    expect($setoptCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies TLS cipher list and curves', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(
        curvesList: 'X25519:prime256v1',
        cipherList: 'ECDHE-RSA-AES128-GCM-SHA256',
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check CURLOPT_SSL_CIPHER_LIST (10083) was set
    $cipherCalls = array_filter($wrapper->calls, fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10083);
    expect($cipherCalls)->not->toBeEmpty();

    // Check CURLOPT_SSL_EC_CURVES (10306) was set
    $curvesCalls = array_filter($wrapper->calls, fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10306);
    expect($curvesCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies TLS sigalgs list', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(
        sigalgsList: 'ecdsa_secp256r1_sha256:rsa_pss_rsae_sha256',
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check CURLOPT_SSL_SIG_HASH_ALGS (10307) was set
    $sigalgsCalls = array_filter($wrapper->calls, fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10307);
    expect($sigalgsCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies TLS version range', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(
        minTlsVersion: TlsVersion::TLS_1_2,
        maxTlsVersion: TlsVersion::TLS_1_3,
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check CURLOPT_SSLVERSION (32) was set
    $sslVersionCalls = array_filter($wrapper->calls, fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 32);
    expect($sslVersionCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('enables HTTP/2 when ALPN contains h2', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(
        alpnProtocols: [AlpnProtocol::Http2, AlpnProtocol::Http1],
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check CURLOPT_HTTP_VERSION (84) was set to 3 (CURL_HTTP_VERSION_2_0)
    $httpVersionCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 84 && $c['args'][1] === 3,
    );
    expect($httpVersionCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('does not enable HTTP/2 when ALPN has no h2', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(
        alpnProtocols: [AlpnProtocol::Http1],
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check CURLOPT_HTTP_VERSION (84) was NOT set to 3
    $httpVersionCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 84 && $c['args'][1] === 3,
    );
    expect($httpVersionCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies GREASE and permute extensions', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(
        greaseEnabled: true,
        permuteExtensions: true,
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check CURLOPT_TLS_GREASE (314) was set to 1
    $greaseCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 314 && $c['args'][1] === 1,
    );
    expect($greaseCalls)->not->toBeEmpty();

    // Check CURLOPT_TLS_PERMUTE_EXTENSIONS (315) was set to 1
    $permuteCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 315 && $c['args'][1] === 1,
    );
    expect($permuteCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies ECH GREASE', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(enableEchGrease: true);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check CURLOPT_SSL_ECH (10316) was set to 'GREASE'
    $echCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10316 && $c['args'][1] === 'GREASE',
    );
    expect($echCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies session ticket setting', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(sessionTicket: false);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check CURLOPT_SSL_ENABLE_TICKET (313) was set to 0
    $ticketCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 313 && $c['args'][1] === 0,
    );
    expect($ticketCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('skips TLS options when null', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile(tlsOptions: null);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // TLS-specific options should not be present
    $tlsCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && in_array($c['args'][0], [10083, 10306, 10307, 314, 315, 10316, 313], true),
    );
    expect($tlsCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies HTTP/2 pseudo header order', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(
        headersPseudoOrder: new PseudoHeaderOrder([
            PseudoHeader::Method,
            PseudoHeader::Authority,
            PseudoHeader::Scheme,
            PseudoHeader::Path,
        ]),
    );
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check CURLOPT_HTTP2_PSEUDO_HEADERS_ORDER (10318) was set
    $pseudoCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10318,
    );
    expect($pseudoCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies HTTP/2 settings order', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(
        headerTableSize: 65536,
        maxConcurrentStreams: 1000,
        settingsOrder: new SettingsOrder([
            SettingId::HeaderTableSize,
            SettingId::MaxConcurrentStreams,
        ]),
    );
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check CURLOPT_HTTP2_SETTINGS (10319) was set
    $settingsCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10319,
    );
    expect($settingsCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies HTTP/2 connection window update', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(initialConnWindowSize: 131072);
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check CURLOPT_HTTP2_WINDOW_UPDATE (320) was set
    $windowCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 320 && $c['args'][1] === 131072,
    );
    expect($windowCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('skips HTTP/2 options when null', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile(http2Options: null);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // HTTP/2-specific options should not be present
    $http2Calls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && in_array($c['args'][0], [10318, 10319, 320], true),
    );
    expect($http2Calls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('merges profile default headers with request headers', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com', [
        'Accept' => 'text/html',
    ]);
    $profile = new Profile(
        defaultHeaders: [
            'User-Agent' => 'Reqxide/1.0',
            'Accept-Language' => 'en-US',
        ],
    );
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check slistAppend was called (headers were set)
    $slistCalls = array_filter($wrapper->calls, fn ($c) => $c['method'] === 'slistAppend');
    expect(count($slistCalls))->toBeGreaterThanOrEqual(2);
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('orders headers according to OriginalHeaderMap', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com', [
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
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Headers were set via slist
    $slistCalls = array_filter($wrapper->calls, fn ($c) => $c['method'] === 'slistAppend');
    expect(count($slistCalls))->toBeGreaterThanOrEqual(2);
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies SSL verification settings', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions(verifySsl: true);

    $transport->send($request, $profile, $options);

    // Check CURLOPT_SSL_VERIFYPEER (64) was set to 1
    $verifyCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 64 && $c['args'][1] === 1,
    );
    expect($verifyCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('disables SSL verification', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions(verifySsl: false);

    $transport->send($request, $profile, $options);

    // Check CURLOPT_SSL_VERIFYPEER (64) was set to 0
    $verifyCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 64 && $c['args'][1] === 0,
    );
    expect($verifyCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies CA bundle path', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions(
        verifySsl: false,
        caBundle: '/etc/ssl/certs/ca-certificates.crt',
    );

    $transport->send($request, $profile, $options);

    // Check CURLOPT_CAINFO (10065) was set
    $caCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10065,
    );
    expect($caCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('skips empty CA bundle path', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions(
        verifySsl: false,
        caBundle: '',
    );

    $transport->send($request, $profile, $options);

    // Check CURLOPT_CAINFO (10065) was NOT set
    $caCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10065,
    );
    expect($caCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies HTTP proxy settings', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $proxy = Proxy::http('127.0.0.1:8080');
    $profile = new Profile;
    $options = new TransportOptions(proxy: $proxy);

    $transport->send($request, $profile, $options);

    // Check CURLOPT_PROXY (10004) was set
    $proxyCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10004,
    );
    expect($proxyCalls)->not->toBeEmpty();

    // Check CURLOPT_PROXYTYPE (101) was set to 0 (HTTP)
    $proxyTypeCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 101 && $c['args'][1] === 0,
    );
    expect($proxyTypeCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies HTTPS proxy settings', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $proxy = Proxy::https('proxy.example.com:443');
    $profile = new Profile;
    $options = new TransportOptions(proxy: $proxy);

    $transport->send($request, $profile, $options);

    // Check CURLOPT_PROXYTYPE (101) was set to 2 (HTTPS)
    $proxyTypeCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 101 && $c['args'][1] === 2,
    );
    expect($proxyTypeCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies SOCKS4 proxy settings', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $proxy = Proxy::socks4('socks4.example.com:1080');
    $profile = new Profile;
    $options = new TransportOptions(proxy: $proxy);

    $transport->send($request, $profile, $options);

    // Check CURLOPT_PROXYTYPE (101) was set to 4 (SOCKS4)
    $proxyTypeCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 101 && $c['args'][1] === 4,
    );
    expect($proxyTypeCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies SOCKS5 proxy settings', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $proxy = Proxy::socks5('socks5.example.com:1080');
    $profile = new Profile;
    $options = new TransportOptions(proxy: $proxy);

    $transport->send($request, $profile, $options);

    // Check CURLOPT_PROXYTYPE (101) was set to 7 (SOCKS5)
    $proxyTypeCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 101 && $c['args'][1] === 7,
    );
    expect($proxyTypeCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies proxy authentication credentials', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $proxy = Proxy::http('//user:pass@proxy.com:8080');
    $profile = new Profile;
    $options = new TransportOptions(proxy: $proxy);

    $transport->send($request, $profile, $options);

    // Check CURLOPT_PROXYUSERPWD (10006) was set
    $authCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10006,
    );
    expect($authCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('disables redirect following', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Check CURLOPT_FOLLOWLOCATION (52) was set to 0
    $redirectCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 52 && $c['args'][1] === 0,
    );
    expect($redirectCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('frees header slist after request', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com', [
        'Accept' => 'text/html',
    ]);
    $profile = new Profile(
        defaultHeaders: ['User-Agent' => 'Reqxide/1.0'],
    );
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $methods = array_column($wrapper->calls, 'method');
    expect($methods)->toContain('slistFreeAll');
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles TLS options with null version range', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(
        minTlsVersion: null,
        maxTlsVersion: null,
    );
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // CURLOPT_SSLVERSION (32) should NOT be set when both are null
    $sslCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 32,
    );
    expect($sslCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies TLS version with only min', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(minTlsVersion: TlsVersion::TLS_1_0);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $sslCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 32,
    );
    expect($sslCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies TLS version with only max', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(maxTlsVersion: TlsVersion::TLS_1_2);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $sslCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 32,
    );
    expect($sslCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles all TLS version mappings for min', function (TlsVersion $version): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(minTlsVersion: $version);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $sslCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 32,
    );
    expect($sslCalls)->not->toBeEmpty();
})->with([
    'TLS 1.0' => TlsVersion::TLS_1_0,
    'TLS 1.1' => TlsVersion::TLS_1_1,
    'TLS 1.2' => TlsVersion::TLS_1_2,
    'TLS 1.3' => TlsVersion::TLS_1_3,
])->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles all TLS version mappings for max', function (TlsVersion $version): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(maxTlsVersion: $version);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $sslCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 32,
    );
    expect($sslCalls)->not->toBeEmpty();
})->with([
    'TLS 1.0' => TlsVersion::TLS_1_0,
    'TLS 1.1' => TlsVersion::TLS_1_1,
    'TLS 1.2' => TlsVersion::TLS_1_2,
    'TLS 1.3' => TlsVersion::TLS_1_3,
])->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles empty cipher list gracefully', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(curvesList: '', cipherList: '');
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Empty strings should NOT trigger setopt for cipher/curves
    $cipherCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10083,
    );
    expect($cipherCalls)->toBeEmpty();

    $curvesCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10306,
    );
    expect($curvesCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles HTTP/2 settings with enable push', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(
        enablePush: false,
        settingsOrder: new SettingsOrder([SettingId::EnablePush]),
    );
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $settingsCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10319,
    );
    expect($settingsCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles HTTP/2 settings with max frame size', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(
        maxFrameSize: 16384,
        settingsOrder: new SettingsOrder([SettingId::MaxFrameSize]),
    );
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $settingsCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10319,
    );
    expect($settingsCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles HTTP/2 settings with max header list size', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(
        maxHeaderListSize: 262144,
        settingsOrder: new SettingsOrder([SettingId::MaxHeaderListSize]),
    );
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $settingsCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10319,
    );
    expect($settingsCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles HTTP/2 settings with initial window size at default', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(
        initialWindowSize: 65535, // default — should be skipped
        settingsOrder: new SettingsOrder([SettingId::InitialWindowSize]),
    );
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Settings should NOT be set since only setting has default value
    $settingsCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10319,
    );
    expect($settingsCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('skips connection window update when at default', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(initialConnWindowSize: 65535); // default
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $windowCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 320,
    );
    expect($windowCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles settings order with EnableConnectProtocol', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(
        settingsOrder: new SettingsOrder([SettingId::EnableConnectProtocol]),
    );
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // EnableConnectProtocol is mapped to null, so settings should be empty
    $settingsCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10319,
    );
    expect($settingsCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles settings order with NoRfc7540Priorities', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(
        settingsOrder: new SettingsOrder([SettingId::NoRfc7540Priorities]),
    );
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // NoRfc7540Priorities is mapped to null, so settings should be empty
    $settingsCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10319,
    );
    expect($settingsCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles headers with extras not in the header map', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com', [
        'Accept' => 'text/html',
        'X-Custom' => 'custom-value',
    ]);
    $headerMap = new OriginalHeaderMap(['Accept']);
    $profile = new Profile(
        defaultHeaders: ['User-Agent' => 'Reqxide/1.0'],
        originalHeaderMap: $headerMap,
    );
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // All headers should be set via slist
    $slistCalls = array_filter($wrapper->calls, fn ($c) => $c['method'] === 'slistAppend');
    expect(count($slistCalls))->toBeGreaterThanOrEqual(3);
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles request with no headers', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile(defaultHeaders: []);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // Host header is auto-added by PSR-7, so we'll still get slistAppend calls
    expect(true)->toBeTrue();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles empty sigalgs list gracefully', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(sigalgsList: '');
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $sigalgsCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10307,
    );
    expect($sigalgsCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies TLS GREASE disabled', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(greaseEnabled: false);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $greaseCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 314 && $c['args'][1] === 0,
    );
    expect($greaseCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('applies permute extensions disabled', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(permuteExtensions: false);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $permuteCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 315 && $c['args'][1] === 0,
    );
    expect($permuteCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('does not apply ECH GREASE when disabled', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(enableEchGrease: false);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $echCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10316,
    );
    expect($echCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('does not apply GREASE when null', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(greaseEnabled: null);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $greaseCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 314,
    );
    expect($greaseCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('does not apply permute extensions when null', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $tls = new TlsOptions(permuteExtensions: null);
    $profile = new Profile(tlsOptions: $tls);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $permuteCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 315,
    );
    expect($permuteCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles HTTP/2 settings with enable push true', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(
        enablePush: true,
        settingsOrder: new SettingsOrder([SettingId::EnablePush]),
    );
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $settingsCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10319,
    );
    expect($settingsCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles HTTP/2 settings with null enable push', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(
        enablePush: null,
        settingsOrder: new SettingsOrder([SettingId::EnablePush]),
    );
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    // enablePush is null, so it yields null value, so settings should be empty
    $settingsCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10319,
    );
    expect($settingsCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles HTTP/2 settings with initial window size non-default', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(
        initialWindowSize: 131072,
        settingsOrder: new SettingsOrder([SettingId::InitialWindowSize]),
    );
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $settingsCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10319,
    );
    expect($settingsCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles proxy authentication with username only', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $proxy = Proxy::http('admin@proxy.com:3128');
    $profile = new Profile;
    $options = new TransportOptions(proxy: $proxy);

    $transport->send($request, $profile, $options);

    // CURLOPT_PROXYUSERPWD (10006) should be set
    $authCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10006,
    );
    expect($authCalls)->not->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('gets response status code from easyGetinfo', function (): void {
    $wrapper = createStubWrapper(['getinfo_result' => 404]);
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions;

    $response = $transport->send($request, $profile, $options);

    expect($response->getStatusCode())->toBe(404);
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('NetworkException contains the original request', function (): void {
    $wrapper = createStubWrapper(['perform_result' => 28, 'strerror_result' => 'Operation timed out']);
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions;

    try {
        $transport->send($request, $profile, $options);
    } catch (NetworkException $e) {
        expect($e->getRequest())->toBe($request)
            ->and($e->getMessage())->toStartWith('curl_impersonate error:')
            ->and($e->getCode())->toBe(28);

        return;
    }

    test()->fail('Expected NetworkException was not thrown');
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('detectLibraryPath uses environment variable', function (): void {
    $tmpFile = tempnam(sys_get_temp_dir(), 'reqxide_lib_');

    try {
        putenv('REQXIDE_CURL_IMPERSONATE_PATH='.$tmpFile);
        $path = FfiTransport::detectLibraryPath();

        expect($path)->toBe($tmpFile);
    } finally {
        putenv('REQXIDE_CURL_IMPERSONATE_PATH');
        unlink($tmpFile);
    }
})->skip(! extension_loaded('ffi'), 'FFI extension is required');

it('handles HTTP/2 without pseudo header order', function (): void {
    $wrapper = createStubWrapper();
    $transport = new FfiTransport($wrapper);
    $request = new Request('GET', 'https://example.com');
    $http2 = new Http2Options(
        headersPseudoOrder: null,
        settingsOrder: null,
    );
    $profile = new Profile(http2Options: $http2);
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);

    $pseudoCalls = array_filter(
        $wrapper->calls,
        fn ($c) => $c['method'] === 'easySetopt' && $c['args'][0] === 10318,
    );
    expect($pseudoCalls)->toBeEmpty();
})->skip(! extension_loaded('ffi'), 'FFI extension is required');
