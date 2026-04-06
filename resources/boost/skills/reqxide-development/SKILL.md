---
name: reqxide-development
description: Build and work with Reqxide features, including browser fingerprint profiles, TLS/HTTP2 configuration, middleware, and transport integration.
---

# Reqxide Development

## When to use this skill

Use this skill when:
- Creating or modifying browser fingerprint profiles
- Configuring TLS options, HTTP/2 settings, or header ordering
- Building HTTP clients with browser emulation
- Adding middleware (cookies, redirects, retries, compression)
- Integrating with frameworks (Guzzle, Laravel, Symfony)
- Working with proxy configuration
- Adding new browser versions from curl_impersonate

## Project Structure

```
src/
├── Emulation/
│   ├── Browser.php              # String-backed enum with all 44 browser presets
│   ├── Catalog.php              # Maps Browser enum → Profile via match expression
│   ├── Profile.php              # Readonly value object: TLS + HTTP2 + headers
│   └── Catalog/
│       ├── Chrome.php           # Chrome profiles (wreq + curl_impersonate eras)
│       ├── Firefox.php          # Firefox profiles
│       ├── Safari.php           # Safari profiles (4 eras: 15.x, 17.x, 18.x, 26.x)
│       ├── Edge.php             # Edge profiles (thin wrappers over Chrome)
│       ├── OkHttp.php           # OkHttp profiles
│       └── Tor.php              # Tor Browser profile
├── Tls/
│   ├── TlsOptions.php           # Readonly TLS config (ciphers, curves, ALPN, etc.)
│   ├── TlsOptionsBuilder.php    # Builder for TlsOptions
│   ├── KeyShare.php             # Enum: X25519, MLKEM768, Kyber768Draft00, P-256, etc.
│   ├── CipherSuite.php          # Enum: all supported cipher suites
│   └── ...                      # TlsVersion, AlpnProtocol, CertificateCompressor, etc.
├── Http2/
│   ├── Http2Options.php          # Readonly HTTP/2 config (window size, settings, etc.)
│   ├── Http2OptionsBuilder.php   # Builder for Http2Options
│   ├── PseudoHeaderOrder.php     # Pseudo-header ordering (e.g., mspa, mpas)
│   ├── SettingsOrder.php         # HTTP/2 SETTINGS frame ordering
│   └── StreamDependency.php      # Stream weight and exclusivity
├── Http1/
│   └── OriginalHeaderMap.php     # Header ordering for HTTP/1.1
├── Middleware/                    # Cookie, Redirect, Retry, Compression
├── Transport/                    # FfiTransport, CurlTransport, ProcessTransport
├── Proxy/                        # Proxy (HTTP, HTTPS, SOCKS4, SOCKS5)
├── Client.php                    # PSR-18 HTTP client
├── ClientBuilder.php             # Fluent builder for Client
└── RequestBuilder.php            # Fluent builder for individual requests
```

## Creating a Browser Profile

Every browser profile is a `Profile` object containing TLS options, HTTP/2 options, default headers, and header ordering. Profiles live in `src/Emulation/Catalog/`.

### Step 1: Add the profile method to the catalog class

Each browser family has a catalog class with static factory methods. The pattern is: private base helpers build the shared config, public methods call them with version-specific values.

```php
// src/Emulation/Catalog/Chrome.php
public static function v140(): Profile
{
    return self::ciProfile(
        version: '140.0.0.0',
        secChUa: '"Chromium";v="140", "Google Chrome";v="140", "Not:A-Brand";v="99"',
        platform: 'macOS',
        tls: self::ciTls(
            permute: true,
            ech: true,
            alpsNew: true,
            curves: 'X25519MLKEM768:X25519:P-256:P-384',
            keyShares: [KeyShare::X25519MLKEM768, KeyShare::X25519],
        ),
        http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: false),
        acceptQuality: '0.7',
        encoding: 'gzip, deflate, br, zstd',
        priority: true,
    );
}
```

### Step 2: Add the enum case to Browser.php

```php
// src/Emulation/Browser.php
case Chrome140 = 'chrome_140';
```

### Step 3: Wire it in Catalog.php

```php
// src/Emulation/Catalog.php
Browser::Chrome140 => Chrome::v140(),
```

### Step 4: Add tests

```php
// tests/Unit/Emulation/Catalog/ChromeTest.php
// Add 'v140' to the parametric dataset that verifies Profile construction
```

Update the case count in `BrowserTest.php` and add the new case to the backed-values dataset.

## Chrome Profile Eras (curl_impersonate)

Chrome profiles are organized by "era" — groups of versions sharing the same TLS/HTTP2 base:

| Era | Versions | Platform | Key Flags |
|-----|----------|----------|-----------|
| Era 1 | 99-107 | Windows | !permute, !ech, extended ciphers |
| Era 2 | 110-116 | Windows | +permute, !ech |
| Era 3 | 119-123 | macOS | +permute, +ech, no maxConcurrentStreams |
| Era 4 | 124 | macOS | +Kyber curves, +Priority header |
| Era 5 | 131+ | macOS | +MLKEM curves, +alpsNew (133a+) |

Existing wreq profiles (v128-v131) use a separate base with a shorter cipher list and Windows platform. Do not modify them.

## Building a TLS Profile

Use the builder pattern — all options classes are `readonly`:

```php
$tls = TlsOptions::builder()
    ->minTlsVersion(TlsVersion::TLS_1_2)
    ->maxTlsVersion(TlsVersion::TLS_1_3)
    ->cipherList('TLS_AES_128_GCM_SHA256:...')
    ->sigalgsList('ecdsa_secp256r1_sha256:...')
    ->curvesList('X25519MLKEM768:X25519:P-256:P-384')
    ->alpnProtocols([AlpnProtocol::Http2, AlpnProtocol::Http1])
    ->keyShares([KeyShare::X25519MLKEM768, KeyShare::X25519])
    ->keySharesLimit(3)                                    // Firefox/Tor only
    ->greaseEnabled(true)
    ->permuteExtensions(true)
    ->enableEchGrease(true)
    ->enableOcspStapling(true)
    ->enableSignedCertTimestamps(true)
    ->sessionTicket(true)
    ->alpsUseNewCodepoint(true)                            // Chrome 133a+
    ->certificateCompressors([CertificateCompressor::Brotli])
    ->delegatedCredentials('ecdsa_secp256r1_sha256:...')    // Firefox/Tor
    ->recordSizeLimit(4001)                                // Firefox/Tor
    ->extensionPermutation([0, 23, 65281, 10, 11, ...])    // Firefox/Tor
    ->build();
```

## Building HTTP/2 Options

```php
$http2 = Http2Options::builder()
    ->headerTableSize(65536)
    ->enablePush(false)
    ->maxConcurrentStreams(1000)
    ->initialWindowSize(6291456)
    ->maxFrameSize(16384)
    ->maxHeaderListSize(262144)
    ->initialConnWindowSize(15663105)
    ->headersStreamDependency(new StreamDependency(0, 256, true))
    ->headersPseudoOrder(new PseudoHeaderOrder([
        PseudoHeader::Method,
        PseudoHeader::Authority,
        PseudoHeader::Scheme,
        PseudoHeader::Path,
    ]))
    ->settingsOrder(new SettingsOrder([
        SettingId::HeaderTableSize,
        SettingId::EnablePush,
        SettingId::MaxConcurrentStreams,
        SettingId::InitialWindowSize,
        SettingId::MaxFrameSize,
        SettingId::MaxHeaderListSize,
    ]))
    ->noRfc7540Priorities(true)      // Safari 26.x
    ->enableConnectProtocol(true)    // Safari 18.0
    ->build();
```

## Using the Client

```php
use Reqxide\Client;
use Reqxide\Emulation\Browser;
use Reqxide\Proxy\Proxy;

// Simple GET
$client = Client::builder()
    ->emulation(Browser::Chrome136)
    ->build();

$response = $client->get('https://example.com')->send();

// POST with JSON, auth, proxy, cookies
$client = Client::builder()
    ->emulation(Browser::Firefox135)
    ->proxy(Proxy::socks5('user:pass@127.0.0.1:1080'))
    ->cookieStore(true)
    ->timeout(60)
    ->build();

$response = $client->post('https://api.example.com/data')
    ->bearerToken('my-token')
    ->json(['key' => 'value'])
    ->query(['page' => '1'])
    ->send();
```

## Framework Adapters

### Guzzle

```php
use Reqxide\Adapter\Guzzle\GuzzleHandlerAdapter;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\HandlerStack;

$handler = new GuzzleHandlerAdapter(Browser::Chrome131);
$guzzle = new Guzzle(['handler' => HandlerStack::create($handler)]);
```

### Laravel

```php
use Reqxide\Adapter\Laravel\ReqxideServiceProvider;

$provider = new ReqxideServiceProvider(defaultBrowser: 'chrome_131');
$client = $provider->createClient();
```

## Edge Profiles Pattern

Edge profiles are thin wrappers over Chrome — they reuse the Chrome TLS/HTTP2 and override `sec-ch-ua` and `User-Agent`:

```php
public static function v101(): Profile
{
    $chrome = Chrome::v101();
    $headers = $chrome->defaultHeaders;
    $headers['sec-ch-ua'] = '" Not A;Brand";v="99", "Chromium";v="101", "Microsoft Edge";v="101"';
    $headers['User-Agent'] = '...Edg/101.0.1210.47';

    return new Profile(
        tlsOptions: $chrome->tlsOptions,
        http2Options: $chrome->http2Options,
        defaultHeaders: $headers,
        originalHeaderMap: $chrome->originalHeaderMap,
    );
}
```

## Quality Requirements

- PHPStan level max, zero errors
- Pest type-coverage: exactly 100%
- Pest unit coverage: exactly 100%
- All files must have `declare(strict_types=1)`
- Run `composer test` before committing (lint + types + unit + phpstan + rector)

## Adding Profiles from curl_impersonate

When porting a new browser version from [curl_impersonate](https://github.com/lexiforest/curl-impersonate):

1. Read the wrapper script in `bin/curl_<browser>` to extract TLS flags, HTTP/2 settings, headers
2. Convert NSS cipher names to OpenSSL format (e.g., `TLS_ECDHE_ECDSA_WITH_AES_128_GCM_SHA256` → `ECDHE-ECDSA-AES128-GCM-SHA256`)
3. Map curl flags to TlsOptions fields:
   - `--tls-grease` → `greaseEnabled(true)`
   - `--tls-permute-extensions` → `permuteExtensions(true)`
   - `--ech true` → `enableEchGrease(true)`
   - `--tls-use-new-alps-codepoint` → `alpsUseNewCodepoint(true)`
   - `--cert-compression brotli` → `certificateCompressors([CertificateCompressor::Brotli])`
   - `--no-tls-session-ticket` → `sessionTicket(false)`
   - `--tls-key-shares-limit N` → `keySharesLimit(N)`
   - `--tls-record-size-limit N` → `recordSizeLimit(N)`
   - `--tls-extension-order "0-23-..."` → `extensionPermutation([0, 23, ...])`
   - `--tls-delegated-credentials "..."` → `delegatedCredentials('...')`
4. Map HTTP/2 settings string (e.g., `1:65536;2:0;4:6291456;6:262144`) to builder calls
5. Map pseudo-header order string (e.g., `mspa` → Method, Scheme, Path, Authority)
6. For `--impersonate`-only presets (no explicit flags), extrapolate from the nearest known version
