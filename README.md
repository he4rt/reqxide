# Reqxide

PHP TLS/HTTP fingerprinting library for browser emulation.

[![Tests](https://github.com/he4rt/reqxide/actions/workflows/tests.yml/badge.svg)](https://github.com/he4rt/reqxide/actions)
[![Latest Version](https://img.shields.io/packagist/v/he4rt/reqxide)](https://packagist.org/packages/he4rt/reqxide)
[![License](https://img.shields.io/packagist/l/he4rt/reqxide)](https://packagist.org/packages/he4rt/reqxide)

---

Reqxide brings TLS and HTTP/2 fingerprinting to PHP. It emulates real browser fingerprints (Chrome, Firefox, Safari, Edge, OkHttp) so your HTTP requests look identical to traffic from actual browsers. Built on top of `curl_impersonate`, it provides a PSR-18 compatible client with transparent browser emulation.

Ported from Rust's [wreq](https://github.com/nickel-org/wreq).

> **Requires [PHP 8.4+](https://php.net/releases/)**

## Why?

Anti-bot systems like Cloudflare, Akamai, and DataDome fingerprint TLS handshakes and HTTP/2 frames to distinguish bots from real browsers. Standard PHP HTTP clients (Guzzle, Symfony HttpClient) produce machine-like fingerprints that get blocked immediately.

Reqxide solves this by controlling:

- **TLS ClientHello** &mdash; cipher suites, elliptic curves, signature algorithms, extensions, ALPN, GREASE, ECH
- **HTTP/2 SETTINGS** &mdash; frame order, window sizes, pseudo-header order, stream priorities
- **Header order and casing** &mdash; browsers send headers in specific order with specific casing

The result: your requests produce the same JA3/JA4 fingerprints as real browsers.

## Installation

```bash
composer require he4rt/reqxide
```

## Quick Start

```php
use Reqxide\Client;
use Reqxide\Emulation\Browser;

$client = Client::builder()
    ->emulation(Browser::Chrome131)
    ->build();

$response = $client->get('https://example.com')->send();

echo $response->getStatusCode(); // 200
echo (string) $response->getBody();
```

## Usage

### Browser Emulation

Pick a browser profile. Each profile bundles the exact TLS configuration, HTTP/2 settings, default headers, and header ordering that the real browser uses.

```php
use Reqxide\Client;
use Reqxide\Emulation\Browser;

// Chrome (latest)
$client = Client::builder()->emulation(Browser::Chrome131)->build();

// Firefox
$client = Client::builder()->emulation(Browser::Firefox136)->build();

// Safari (macOS, iOS, iPad)
$client = Client::builder()->emulation(Browser::Safari18)->build();
$client = Client::builder()->emulation(Browser::SafariIOS18)->build();

// Edge (Chromium-based, different sec-ch-ua)
$client = Client::builder()->emulation(Browser::Edge131)->build();

// OkHttp (Android HTTP client)
$client = Client::builder()->emulation(Browser::OkHttp5)->build();
```

### Making Requests

Reqxide implements PSR-18 (`ClientInterface`) and provides convenience methods:

```php
// Convenience API
$response = $client->get('https://api.example.com/users')->send();
$response = $client->post('https://api.example.com/users')
    ->json(['name' => 'Daniel', 'email' => 'daniel@he4rt.com'])
    ->send();

// With headers and auth
$response = $client->get('https://api.example.com/me')
    ->bearerToken('your-token')
    ->header('X-Custom', 'value')
    ->send();

// Form data
$response = $client->post('https://example.com/login')
    ->form(['username' => 'admin', 'password' => 'secret'])
    ->send();

// Query parameters
$response = $client->get('https://api.example.com/search')
    ->query(['q' => 'reqxide', 'page' => '1'])
    ->send();

// PSR-18 standard (drop-in for any PSR-18 consumer)
$response = $client->sendRequest($psrRequest);
```

### Proxy Support

HTTP, HTTPS, SOCKS4, and SOCKS5 with optional authentication:

```php
use Reqxide\Proxy\Proxy;

$client = Client::builder()
    ->emulation(Browser::Chrome131)
    ->proxy(Proxy::http('45.38.89.88:6023'))
    ->build();

// SOCKS5
$client = Client::builder()
    ->emulation(Browser::Chrome131)
    ->proxy(Proxy::socks5('127.0.0.1:1080'))
    ->build();

// With authentication
$proxy = new Proxy(
    scheme: ProxyScheme::Http,
    host: 'proxy.example.com',
    port: 8080,
    username: 'user',
    password: 'pass',
);
```

### Cookie Persistence

```php
use Reqxide\Cookie\CookieJar;

// Automatic cookie jar
$client = Client::builder()
    ->emulation(Browser::Chrome131)
    ->cookieStore(true)
    ->build();

// Shared cookie jar across clients
$jar = new CookieJar();

$client = Client::builder()
    ->emulation(Browser::Chrome131)
    ->cookieStore($jar)
    ->build();

// Cookies persist across requests automatically
$client->get('https://example.com/login')->form([...])->send();
$client->get('https://example.com/dashboard')->send(); // sends session cookie
```

### Redirect & Retry

```php
use Reqxide\Redirect\RedirectPolicy;
use Reqxide\Retry\RetryPolicy;

$client = Client::builder()
    ->emulation(Browser::Chrome131)
    ->redirect(RedirectPolicy::limited(10))  // follow up to 10 redirects
    ->retry(RetryPolicy::default())          // retry 5xx, max 2 attempts, 20% budget
    ->build();

// Disable redirects
$client = Client::builder()
    ->emulation(Browser::Chrome131)
    ->redirect(RedirectPolicy::none())
    ->build();

// Custom retry logic
$client = Client::builder()
    ->emulation(Browser::Chrome131)
    ->retry(RetryPolicy::custom(
        classifier: fn($req, $res, $err) => $res?->getStatusCode() === 429,
        maxRetries: 5,
        baseDelayMs: 1000,
    ))
    ->build();
```

### Client Configuration

```php
$client = Client::builder()
    ->emulation(Browser::Chrome131)
    ->proxy(Proxy::socks5('127.0.0.1:1080'))
    ->timeout(30)                              // seconds
    ->connectTimeout(10)                       // seconds
    ->cookieStore(true)                        // enable cookie jar
    ->redirect(RedirectPolicy::limited(10))    // follow redirects
    ->retry(RetryPolicy::default())            // retry on failure
    ->verify(false)                            // disable SSL verification
    ->defaultHeaders(['X-App' => 'myapp'])     // extra headers on every request
    ->build();
```

## Architecture

```
User Code
    |
    v
+---------------------------------+
|  PSR-18 Client (Reqxide\Client) |
|  sendRequest(RequestInterface)  |
+---------------------------------+
    |
    v
+---------------------------------+
|     Middleware Pipeline          |
|  Cookie -> Redirect -> Retry -> |
|  Compression -> Transport       |
+---------------------------------+
    |
    +--------+---------+
    v        v         v
  FFI     ext/curl   Process
  (full)  (partial)  (binary)
    |        |         |
    v        v         v
  libcurl-impersonate
```

**Three transport backends:**

| Transport | Fingerprint Control | Requires |
|-----------|-------------------|----------|
| `FfiTransport` | Full (TLS + HTTP/2 + headers) | PHP FFI + libcurl-impersonate |
| `CurlTransport` | Partial (TLS ciphers, curves, ALPN) | ext-curl |
| `ProcessTransport` | Full (via binary) | curl-impersonate binary |

`TransportFactory::create()` auto-detects the best available transport.

## Framework Integration

### Guzzle

```php
use Reqxide\Adapter\Guzzle\GuzzleHandlerAdapter;
use Reqxide\Emulation\Browser;

$handler = new GuzzleHandlerAdapter(Browser::Chrome131);
$guzzle = new \GuzzleHttp\Client([
    'handler' => \GuzzleHttp\HandlerStack::create($handler),
]);

$response = $guzzle->get('https://example.com');
```

### Laravel

```php
// In a ServiceProvider
use Reqxide\Client;
use Reqxide\Emulation\Browser;

$this->app->singleton(Client::class, fn () =>
    Client::builder()->emulation(Browser::Chrome131)->build()
);

// Usage
$client = app(Client::class);
$response = $client->get('https://api.example.com/data')->send();
```

### Symfony

```php
use Reqxide\Adapter\Symfony\SymfonyClientAdapter;
use Reqxide\Emulation\Browser;

$client = new SymfonyClientAdapter(Browser::Chrome131);
$response = $client->request('GET', 'https://example.com', [
    'headers' => ['Accept' => 'application/json'],
]);
```

## Supported Browsers

| Browser | Versions | Key Differences |
|---------|----------|-----------------|
| Chrome | 128, 129, 130, 131 | 130+ has X25519MLKEM768 (post-quantum), GREASE enabled |
| Firefox | 135, 136 | No GREASE, FFDHE curves, Sec-Fetch-\* headers, different pseudo-header order |
| Safari | 18 (macOS, iPad, iOS) | No GREASE, no ECH, minimal headers, different HTTP/2 window sizes |
| Edge | 131 | Chromium base + Edge-specific User-Agent and sec-ch-ua |
| OkHttp | 4, 5 | HTTP/1.1 only, no browser headers, Android client fingerprint |

### Fingerprint Accuracy

Each profile controls the full TLS/HTTP/2 fingerprint:

```
Chrome 131:
  JA3:  9e2da15d3e1b6931c6fbaa3f9ac9cd89
  JA4:  t13d913h2_f91f431d341e_882d495ac381
  HTTP: h2  |  Ciphers: 9

Firefox 136:
  JA3:  bdc242f0548bc1fcc45d12edc713b8e1
  JA4:  t13d1513h2_8daaf6152771_882d495ac381
  HTTP: h2  |  Ciphers: 15
```

## How It Works

When you select `Browser::Chrome131`, reqxide configures:

**TLS layer:**
- Cipher suites in Chrome's exact order (9 ciphers)
- Elliptic curves: X25519MLKEM768, X25519, P-256, P-384
- Signature algorithms: 8 algorithms in Chrome's order
- Key shares: X25519MLKEM768 + X25519
- Extensions: GREASE, ECH GREASE, permuted extensions, OCSP stapling, SCT

**HTTP/2 layer:**
- SETTINGS frame: header table 65536, push disabled, max streams 1000, window 6MB, max frame 16384, max header list 262144
- Pseudo-header order: `:method`, `:authority`, `:scheme`, `:path`
- Connection window: 15663105 bytes

**Headers:**
- Default browser headers (sec-ch-ua, User-Agent, Accept, Accept-Encoding, Accept-Language)
- Header ordering matching Chrome's exact send order
- Case preservation (sec-ch-ua, not Sec-Ch-Ua)

## Testing

```bash
# Full quality suite (lint + phpstan + pest + rector)
composer test

# Individual checks
composer test:lint           # Laravel Pint formatting
composer test:types          # PHPStan level max
composer test:unit           # Pest with 100% coverage
composer test:type-coverage  # 100% type coverage
composer test:refactor       # Rector dry-run

# Run a specific test
./vendor/bin/pest tests/Unit/Tls/TlsOptionsTest.php
./vendor/bin/pest --filter="Chrome 131"

# Integration tests (requires network)
./vendor/bin/pest --group=integration
```

## Project Structure

```
src/
  Client.php                    # PSR-18 ClientInterface
  ClientBuilder.php             # Fluent builder
  RequestBuilder.php            # Per-request builder (get, post, json, form...)
  Contract/                     # Interfaces (Transport, CookieStore, Profile, Redirect, Retry)
  Emulation/                    # Browser enum, Profile, Catalog (Chrome, Firefox, Safari, Edge, OkHttp)
  Tls/                          # TLS options, enums (ciphers, curves, sigalgs, versions)
  Http2/                        # HTTP/2 options (settings, pseudo-headers, priorities)
  Http1/                        # HTTP/1 options (header ordering)
  Transport/                    # CurlTransport, FfiTransport, ProcessTransport, TransportFactory
  Middleware/                   # Pipeline, Cookie, Redirect, Retry, Compression
  Cookie/                       # CookieJar (RFC 6265), Cookie value object
  Proxy/                        # Proxy value object, ProxyScheme enum
  Redirect/                     # RedirectPolicy (limited, none, custom)
  Retry/                        # RetryPolicy, RetryBudget (token bucket)
  Adapter/                      # Guzzle, Laravel, Symfony integrations
  Exception/                    # PSR-18 compliant exception hierarchy
```

## Credits

- Ported from [wreq](https://github.com/nickel-org/wreq) (Rust)
- Powered by [curl-impersonate](https://github.com/lwthiker/curl-impersonate)
- Built by [He4rt Developers](https://github.com/he4rt)

## License

Reqxide is open-sourced software licensed under the **[MIT license](https://opensource.org/licenses/MIT)**.
