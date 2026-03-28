# Reqxide

PHP TLS/HTTP fingerprinting library for browser emulation.

[![Tests](https://github.com/he4rt/reqxide/actions/workflows/tests.yml/badge.svg)](https://github.com/he4rt/reqxide/actions)
[![Latest Version](https://img.shields.io/packagist/v/he4rt/reqxide)](https://packagist.org/packages/he4rt/reqxide)
[![License](https://img.shields.io/packagist/l/he4rt/reqxide)](https://packagist.org/packages/he4rt/reqxide)

---

Reqxide brings TLS and HTTP/2 fingerprinting capabilities to PHP, allowing you to emulate real browser fingerprints (Chrome, Firefox, Safari, Edge, OkHttp) when making HTTP requests. Built on top of `curl_impersonate`, it provides a PSR-18 compatible client with transparent browser emulation.

> **Requires [PHP 8.5+](https://php.net/releases/)**

## Why?

The PHP ecosystem lacks a native TLS/HTTP fingerprinting library. Developers who need to bypass WAF protections (Cloudflare, Akamai, DataDome) are forced to use fragmented solutions or switch to other languages. Reqxide fills this gap by porting the capabilities of Rust's `wreq` to PHP.

## Installation

```bash
composer require he4rt/reqxide
```

## Quick Start

```php
use Reqxide\Client;
use Reqxide\Emulation\Browser;
use Reqxide\Proxy\Proxy;

// Create a client that emulates Chrome 131
$client = Client::builder()
    ->emulation(Browser::Chrome131)
    ->proxy(Proxy::socks5('127.0.0.1:1080'))
    ->cookieStore(true)
    ->build();

// PSR-18 standard
$response = $client->sendRequest($psrRequest);

// Convenience API
$response = $client->get('https://example.com')
    ->header('X-Custom', 'value')
    ->bearerToken('abc123')
    ->send();
```

## Features

- **Browser Emulation** - Emulate TLS/HTTP/2 fingerprints of Chrome, Firefox, Safari, Edge, and OkHttp
- **PSR-18 Compatible** - Drop-in replacement for any PSR-18 HTTP client
- **Framework Adapters** - Integrations for Guzzle, Laravel HTTP, and Symfony HttpClient
- **Multiple Transports** - FFI (recommended), native curl (fallback), and process-based transport
- **Cookie Management** - Full cookie jar with HttpOnly, Secure, SameSite support
- **Proxy Support** - HTTP, HTTPS, SOCKS4, SOCKS5 with authentication
- **Middleware Pipeline** - Cookie, redirect, retry, and compression middleware
- **Immutable Profiles** - Type-safe, readonly value objects for all fingerprint configuration

## Architecture

```
┌──────────────────────────────────┐
│        User Application          │
│  Client::builder()               │
│    ->emulation(Browser::Chrome)  │
│    ->build()                     │
└──────────────┬───────────────────┘
               │
               ▼
┌──────────────────────────────────┐
│     PSR-18 Client (Reqxide)      │
│  Cookie → Redirect → Retry →    │
│  Compression → Transport         │
└──────────────┬───────────────────┘
               │
       ┌───────┼───────┐
       ▼       ▼       ▼
   FFI      ext/curl  Process
(recommended)(fallback)(basic)
       │       │       │
       ▼       ▼       ▼
    libcurl-impersonate
```

## Framework Integration

### Guzzle

```php
use Reqxide\Adapter\Guzzle\GuzzleHandlerAdapter;
use Reqxide\Emulation\Browser;

$handler = new GuzzleHandlerAdapter(Browser::Chrome131);
$guzzle = new \GuzzleHttp\Client(['handler' => $handler]);
$response = $guzzle->get('https://example.com');
```

### Laravel

```php
use Reqxide\Emulation\Browser;

Http::withFingerprint(Browser::Chrome131)->get('https://example.com');
```

### Symfony

```php
use Reqxide\Adapter\Symfony\SymfonyClientAdapter;
use Reqxide\Emulation\Browser;

$client = new SymfonyClientAdapter(Browser::Chrome131);
$response = $client->request('GET', 'https://example.com');
```

## Supported Browsers

| Browser | Versions |
|---------|----------|
| Chrome  | 128-131  |
| Firefox | 135-136  |
| Safari  | 18 (macOS, iOS, iPad) |
| Edge    | 131      |
| OkHttp  | 4, 5     |

## Testing

```bash
composer test:lint    # Laravel Pint
composer test:types   # PHPStan level max
composer test:unit    # PEST with coverage
composer test         # Full suite
```

## Credits

- Inspired by [wreq](https://github.com/nickel-org/wreq) (Rust)
- Powered by [curl-impersonate](https://github.com/lwthiker/curl-impersonate)

## License

Reqxide is open-sourced software licensed under the **[MIT license](https://opensource.org/licenses/MIT)**.
