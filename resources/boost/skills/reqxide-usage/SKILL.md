---
name: reqxide-usage
description: Use Reqxide correctly for browser-emulated HTTP requests — browser selection, header handling for navigation vs XHR vs beacon requests, proxy, cookies, Saloon/Guzzle integration, and common pitfalls.
---

# Reqxide Usage

## When to use this skill

Use this skill when writing code that makes HTTP requests using Reqxide — choosing a browser preset, setting headers, configuring proxies, handling cookies, integrating with Saloon or Guzzle, or debugging fingerprint issues.

## Core Concept: Navigation vs XHR vs Beacon

A real browser sends **different headers depending on the request type**. Reqxide profiles set headers for **navigation requests** (loading a page). When making API calls, image fetches, or tracking beacons, you MUST override certain headers to match what a real browser would send for that request type.

### Header categories

**Fingerprint headers (MUST match TLS profile — never invent your own):**
```
User-Agent                  # must match the Browser enum's version
sec-ch-ua                   # must match the Browser enum's version
sec-ch-ua-mobile            # must match platform (desktop vs mobile)
sec-ch-ua-platform          # must match platform (Windows, macOS, Android)
```

**Context headers (MUST change per request type):**
```
Accept                      # differs: text/html (nav), */* (XHR), image/* (beacon)
Sec-Fetch-Dest              # differs: document (nav), empty (XHR), image (beacon)
Sec-Fetch-Mode              # differs: navigate (nav), cors (XHR), no-cors (beacon)
Sec-Fetch-Site              # differs: none (nav), same-site/cross-site (XHR/beacon)
Sec-Fetch-User              # present on navigation only (?1)
Priority                    # differs: u=0,i (nav), u=1,i (XHR), i (beacon)
```

**Request-specific headers (set freely):**
```
Authorization               # Bearer tokens, OAuth, API keys
Origin                      # required for CORS/XHR requests
Referer                     # expected by most APIs
Content-Type                # set by ->json() and ->form() automatically
Cookie                      # managed by cookieStore() — don't set manually
X-Device-Id, Client-Id      # app-specific identifiers
any X-* or custom headers
```

**Profile-managed headers (leave alone for navigation requests):**
```
Accept-Encoding             # controls decompression — profile sets gzip,deflate,br,zstd
Accept-Language             # profile sets en-US,en;q=0.9
Upgrade-Insecure-Requests   # profile sets 1 for navigation
```

### Request type cheat sheet

| Header | Navigation (page load) | XHR / API call | Image / Beacon |
|--------|----------------------|----------------|----------------|
| Accept | let profile handle | `*/*` | `image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8` |
| Sec-Fetch-Dest | `document` (profile) | `empty` | `image` |
| Sec-Fetch-Mode | `navigate` (profile) | `cors` | `no-cors` |
| Sec-Fetch-Site | `none` (profile) | `same-site` or `cross-site` | `cross-site` |
| Sec-Fetch-User | `?1` (profile) | omit | omit |
| Priority | `u=0, i` (profile) | `u=1, i` | `i` |
| Origin | not sent | `https://origin.site` | not sent |
| Referer | not sent | `https://origin.site/` | `https://origin.site/` |

## Header Priority

Reqxide merges headers in this order (highest wins):

```
1. Request-level headers     ->header('Accept', '*/*')     # HIGHEST — always applied
2. Builder-level defaults    ->defaultHeaders([...])        # only if absent on request
3. Profile defaults          Browser::Chrome131              # only if absent above
```

For navigation requests, the profile defaults are correct — don't set anything. For XHR/API requests, set the context headers on the request to override the profile's navigation defaults.

## Basic Usage

### Navigation request (page load)

```php
use Reqxide\Client;
use Reqxide\Emulation\Browser;

$client = Client::builder()
    ->emulation(Browser::Chrome136)
    ->build();

// Profile handles ALL headers — just send
$response = $client->get('https://example.com')->send();
```

### XHR / API request

```php
// Override context headers to match XHR behavior
$response = $client->post('https://api.example.com/graphql')
    ->header('Accept', '*/*')
    ->header('Sec-Fetch-Dest', 'empty')
    ->header('Sec-Fetch-Mode', 'cors')
    ->header('Sec-Fetch-Site', 'same-site')
    ->header('Origin', 'https://example.com')
    ->header('Referer', 'https://example.com/')
    ->header('priority', 'u=1, i')
    ->bearerToken('my-token')
    ->json(['query' => '{ viewer { id } }'])
    ->send();
```

### Image / tracking beacon

```php
$response = $client->get('https://beacon.example.com/track?event=pageview')
    ->header('Accept', 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8')
    ->header('Sec-Fetch-Dest', 'image')
    ->header('Sec-Fetch-Mode', 'no-cors')
    ->header('Sec-Fetch-Site', 'cross-site')
    ->header('Referer', 'https://example.com/')
    ->header('priority', 'i')
    ->send();
```

## Saloon Integration

Reqxide works as a Saloon Sender, replacing Guzzle for all requests with TLS fingerprinting:

```php
use Reqxide\Client as ReqxideClient;
use Reqxide\Emulation\Browser;
use Reqxide\Proxy\Proxy as ReqxideProxy;
use Reqxide\Proxy\ProxyScheme;
use Saloon\Contracts\Sender;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

class ReqxideSender implements Sender
{
    private ReqxideClient $client;

    public function __construct(
        Browser $browser = Browser::Chrome131,
        ?string $proxyUrl = null,
        int $timeout = 5,
        int $connectTimeout = 3,
    ) {
        $builder = ReqxideClient::builder()
            ->emulation($browser)
            ->timeout($timeout)
            ->connectTimeout($connectTimeout);

        if ($proxyUrl !== null) {
            $proxy = self::parseProxy($proxyUrl);
            if ($proxy !== null) {
                $builder->proxy($proxy);
            }
        }

        $this->client = $builder->build();
    }

    public function send(PendingRequest $pendingRequest): Response
    {
        $psrRequest = $pendingRequest->createPsrRequest();
        $psrResponse = $this->client->sendRequest($psrRequest);
        $responseClass = $pendingRequest->getResponseClass();

        return $responseClass::fromPsrResponse($psrResponse, $pendingRequest, $psrRequest);
    }
}
```

**Using it in a Saloon Connector:**

```php
use Saloon\Http\Connector;

class MyApiConnector extends Connector
{
    public function __construct(
        private Browser $browser = Browser::Chrome136,
        private ?string $proxyUrl = null,
    ) {}

    public function resolveBaseUrl(): string
    {
        return 'https://api.example.com';
    }

    protected function defaultSender(): Sender
    {
        return new ReqxideSender(
            browser: $this->browser,
            proxyUrl: $this->proxyUrl,
        );
    }

    // Set XHR context headers as connector defaults for API connectors
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => '*/*',
            'Origin' => 'https://example.com',
            'Referer' => 'https://example.com/',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-site',
            'priority' => 'u=1, i',
        ];
    }
}
```

**Important:** Saloon connector `defaultHeaders()` override Reqxide profile defaults (because Saloon sets them on the PSR-7 request). This is correct for API connectors — you WANT to override the navigation-style Sec-Fetch/Accept headers with XHR-style ones.

## Keeping Fingerprint Headers in Sync

When overriding User-Agent or sec-ch-ua (e.g., for API connectors that need them), the values **MUST match the Browser enum you selected**. A mismatch between TLS fingerprint and headers is a strong bot detection signal.

**Pattern: extract header values from the profile itself:**

```php
$browser = Browser::Chrome136;
$profile = $browser->profile();

$userAgent = $profile->defaultHeaders['User-Agent'];
$secChUa = $profile->defaultHeaders['sec-ch-ua'];
$secChUaMobile = $profile->defaultHeaders['sec-ch-ua-mobile'];
$secChUaPlatform = $profile->defaultHeaders['sec-ch-ua-platform'];
```

This ensures headers always stay in sync with the TLS profile, even when you rotate browsers.

## Guzzle Integration

```php
use Reqxide\Adapter\Guzzle\GuzzleHandlerAdapter;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\HandlerStack;

$handler = new GuzzleHandlerAdapter(Browser::Chrome131);
$guzzle = new Guzzle(['handler' => HandlerStack::create($handler)]);

$response = $guzzle->get('https://example.com');
```

## Choosing a Browser

### For general web scraping / API calls
```php
Browser::Chrome136   // latest Chrome — most common, widest compatibility
Browser::Chrome131   // stable, well-tested
```

### For sites that block Chrome fingerprints
```php
Browser::Firefox135  // distinct TLS fingerprint — good for rotation
Browser::Safari260   // Apple ecosystem
```

### For mobile endpoints
```php
Browser::Chrome131Android  // Android Chrome
Browser::Safari260iOS      // iPhone Safari
Browser::Safari184iOS      // older iPhone Safari
Browser::Safari172iOS      // iPhone Safari 17.2
```

### For privacy-focused sites
```php
Browser::Tor145  // includes Sec-GPC: 1 header, Firefox-based TLS
```

### For fingerprint rotation
```php
$browsers = [
    Browser::Chrome136,
    Browser::Chrome131,
    Browser::Firefox135,
    Browser::Safari260,
    Browser::Edge131,
];
$browser = $browsers[array_rand($browsers)];
$client = Client::builder()->emulation($browser)->build();
```

### For older site compatibility
```php
Browser::Chrome99   // legacy Chrome
Browser::Safari153  // legacy Safari (TLS 1.0)
```

## Proxy Configuration

```php
use Reqxide\Proxy\Proxy;

// HTTP proxy
$client = Client::builder()
    ->emulation(Browser::Chrome136)
    ->proxy(Proxy::http('proxy.example.com:8080'))
    ->build();

// SOCKS5 with auth
$client = Client::builder()
    ->emulation(Browser::Chrome136)
    ->proxy(Proxy::socks5('user:pass@127.0.0.1:1080'))
    ->build();

// Available: Proxy::http(), Proxy::https(), Proxy::socks4(), Proxy::socks5()
```

**Parsing proxy URLs from config files:**

```php
use Reqxide\Proxy\Proxy as ReqxideProxy;
use Reqxide\Proxy\ProxyScheme;

function parseProxy(string $url): ?ReqxideProxy
{
    $parts = parse_url($url);
    if (!$parts || empty($parts['host'])) {
        return null;
    }

    $scheme = match ($parts['scheme'] ?? 'http') {
        'socks5', 'socks5h' => ProxyScheme::Socks5,
        'socks4' => ProxyScheme::Socks4,
        'https' => ProxyScheme::Https,
        default => ProxyScheme::Http,
    };

    return new ReqxideProxy(
        scheme: $scheme,
        host: $parts['host'],
        port: $parts['port'] ?? ($scheme === ProxyScheme::Socks5 ? 1080 : 80),
        username: isset($parts['user']) ? urldecode($parts['user']) : null,
        password: isset($parts['pass']) ? urldecode($parts['pass']) : null,
    );
}
```

## Cookie Handling

```php
$client = Client::builder()
    ->emulation(Browser::Chrome136)
    ->cookieStore(true)
    ->build();

// Cookies persist across requests on the same client instance
$client->post('https://example.com/login')
    ->form(['user' => 'me', 'pass' => 'secret'])
    ->send();

// Session cookie sent automatically
$response = $client->get('https://example.com/dashboard')->send();
```

**Do not** set the `Cookie` header manually when `cookieStore` is enabled.

## Redirect, Retry & Timeouts

```php
use Reqxide\Redirect\DefaultRedirectPolicy;
use Reqxide\Retry\DefaultRetryPolicy;

$client = Client::builder()
    ->emulation(Browser::Chrome136)
    ->redirect(new DefaultRedirectPolicy(maxRedirects: 5))
    ->retry(new DefaultRetryPolicy(maxRetries: 3))
    ->timeout(60)           // total request timeout (default: 30s)
    ->connectTimeout(10)    // connection timeout (default: 10s)
    ->build();
```

## SSL Verification

```php
// Disable (development only)
$client = Client::builder()->emulation(Browser::Chrome136)->verify(false)->build();

// Custom CA bundle
$client = Client::builder()->emulation(Browser::Chrome136)->caBundle('/path/to/ca.crt')->build();
```

## Common Mistakes

### Setting fingerprint headers to arbitrary values

```php
// WRONG — User-Agent doesn't match Chrome136 TLS fingerprint
$response = $client->get('https://example.com')
    ->header('User-Agent', 'MyBot/1.0')
    ->send();

// WRONG — sec-ch-ua says Chrome 120 but TLS says Chrome 136
$response = $client->get('https://example.com')
    ->header('sec-ch-ua', '"Chromium";v="120"')
    ->send();

// CORRECT — let the profile handle fingerprint headers
$response = $client->get('https://example.com')->send();
```

### Forgetting to override context headers for XHR requests

```php
// WRONG — sends navigation headers (Sec-Fetch-Dest: document) to an API endpoint
$response = $client->post('https://api.example.com/graphql')
    ->json(['query' => '{ me { id } }'])
    ->send();

// CORRECT — override context headers to match XHR behavior
$response = $client->post('https://api.example.com/graphql')
    ->header('Accept', '*/*')
    ->header('Sec-Fetch-Dest', 'empty')
    ->header('Sec-Fetch-Mode', 'cors')
    ->header('Sec-Fetch-Site', 'same-site')
    ->header('Origin', 'https://example.com')
    ->header('Referer', 'https://example.com/')
    ->json(['query' => '{ me { id } }'])
    ->send();
```

### Creating a new client per request

```php
// WRONG — wastes resources, loses cookie state
foreach ($urls as $url) {
    $client = Client::builder()->emulation(Browser::Chrome136)->build();
    $client->get($url)->send();
}

// CORRECT — reuse the client instance
$client = Client::builder()->emulation(Browser::Chrome136)->cookieStore(true)->build();
foreach ($urls as $url) {
    $client->get($url)->send();
}
```

### Mixing browser and identity when rotating

```php
// WRONG — Browser enum is Chrome136 but headers say Firefox
$client = Client::builder()->emulation(Browser::Chrome136)->build();
$client->get('https://example.com')
    ->header('User-Agent', 'Mozilla/5.0 ... Firefox/135.0')
    ->send();

// CORRECT — extract headers from the profile to stay in sync
$browser = Browser::Chrome136;
$profile = $browser->profile();
$client = Client::builder()->emulation($browser)->build();
// $profile->defaultHeaders['User-Agent'] is already set correctly
```

### Using defaultHeaders for navigation headers on an API client

```php
// WRONG — Accept-Language override applies to ALL requests, breaks fingerprint consistency
$client = Client::builder()
    ->emulation(Browser::Chrome136)
    ->defaultHeaders(['Accept-Language' => 'pt-BR'])
    ->build();

// CORRECT — defaultHeaders for non-fingerprint shared headers only
$client = Client::builder()
    ->emulation(Browser::Chrome136)
    ->defaultHeaders([
        'X-API-Key' => 'abc123',
        'X-Request-Source' => 'backend',
    ])
    ->build();
```
