## Reqxide

Reqxide is a PHP 8.4+ TLS/HTTP fingerprinting library that emulates real browser fingerprints (Chrome, Firefox, Safari, Edge, OkHttp, Tor) for HTTP requests. It uses `curl_impersonate` as the transport engine and is PSR-18 compliant.

### Features

- **Browser Emulation**: 44 browser presets with accurate TLS, HTTP/2, and header fingerprints. Pick a browser and Reqxide handles TLS ciphers, curves, HTTP/2 settings, and default headers automatically.

@verbatim
<code-snippet name="Basic GET request with Chrome 131 fingerprint" lang="php">
use Reqxide\Client;
use Reqxide\Emulation\Browser;

$client = Client::builder()
    ->emulation(Browser::Chrome131)
    ->build();

$response = $client->get('https://example.com')->send();
echo $response->getBody();
</code-snippet>
@endverbatim

- **Fluent Request Builder**: Chainable API for headers, JSON/form bodies, query params, and auth.

@verbatim
<code-snippet name="POST JSON with auth and custom headers" lang="php">
$response = $client->post('https://api.example.com/data')
    ->bearerToken('my-token')
    ->header('X-Custom', 'value')
    ->json(['key' => 'value'])
    ->query(['page' => '1'])
    ->send();
</code-snippet>
@endverbatim

- **Proxy Support**: HTTP, HTTPS, SOCKS4, and SOCKS5 proxies with authentication.

@verbatim
<code-snippet name="Using a SOCKS5 proxy" lang="php">
use Reqxide\Proxy\Proxy;

$client = Client::builder()
    ->emulation(Browser::Firefox135)
    ->proxy(Proxy::socks5('user:pass@127.0.0.1:1080'))
    ->build();
</code-snippet>
@endverbatim

- **Cookie Jar**: Automatic cookie handling across requests.

@verbatim
<code-snippet name="Enable cookie store" lang="php">
$client = Client::builder()
    ->emulation(Browser::Safari18)
    ->cookieStore(true)
    ->build();
</code-snippet>
@endverbatim

- **Redirect & Retry Policies**: Configurable redirect following and retry strategies via contracts.

@verbatim
<code-snippet name="Custom redirect and retry policies" lang="php">
use Reqxide\Redirect\DefaultRedirectPolicy;
use Reqxide\Retry\DefaultRetryPolicy;

$client = Client::builder()
    ->emulation(Browser::Chrome136)
    ->redirect(new DefaultRedirectPolicy(maxRedirects: 5))
    ->retry(new DefaultRetryPolicy(maxRetries: 3))
    ->build();
</code-snippet>
@endverbatim

- **Guzzle Adapter**: Drop-in handler for existing Guzzle codebases.

@verbatim
<code-snippet name="Using with Guzzle" lang="php">
use Reqxide\Adapter\Guzzle\GuzzleHandlerAdapter;
use Reqxide\Emulation\Browser;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\HandlerStack;

$handler = new GuzzleHandlerAdapter(Browser::Chrome131);
$guzzle = new Guzzle(['handler' => HandlerStack::create($handler)]);
</code-snippet>
@endverbatim

### Available Browser Presets

The `Browser` enum provides all 44 presets:

| Family | Presets |
|--------|---------|
| Chrome Desktop | `Chrome99` through `Chrome146` (20 versions) |
| Chrome Android | `Chrome99Android`, `Chrome131Android` |
| Firefox | `Firefox133`, `Firefox135`, `Firefox136`, `Firefox144`, `Firefox147` |
| Safari | `Safari153`, `Safari155`, `Safari170`, `Safari172iOS`, `Safari18`, `SafariIPad18`, `SafariIOS18`, `Safari184`, `Safari184iOS`, `Safari260`, `Safari260iOS` |
| Edge | `Edge99`, `Edge101`, `Edge131` |
| OkHttp | `OkHttp4`, `OkHttp5` |
| Tor | `Tor145` |

### Architecture

```
Client::builder()
  ->emulation(Browser::Chrome131)  // selects TLS + HTTP/2 + headers profile
  ->proxy(Proxy::http('...'))      // optional proxy
  ->cookieStore(true)              // optional cookie jar
  ->timeout(30)                    // request timeout in seconds
  ->build()                        // returns PSR-18 Client
```

The internal flow is:

```
User code -> Client (PSR-18) -> Middleware pipeline (Cookie -> Redirect -> Retry -> Compression) -> TransportInterface -> curl_impersonate
```

### Transport Layer

Reqxide auto-detects the best available transport:

1. **FfiTransport** — FFI to libcurl-impersonate (full fingerprint control, recommended)
2. **CurlTransport** — Native ext/curl (limited fingerprint, fallback)
3. **ProcessTransport** — Shell exec of curl_impersonate binary (basic)

@verbatim
<code-snippet name="Force a specific transport" lang="php">
use Reqxide\Transport\ProcessTransport;

$client = Client::builder()
    ->emulation(Browser::Chrome131)
    ->transport(new ProcessTransport('/usr/local/bin/curl-impersonate'))
    ->build();
</code-snippet>
@endverbatim

### Custom Profiles

For advanced use cases, build a custom TLS/HTTP2 profile instead of using a preset:

@verbatim
<code-snippet name="Custom TLS profile" lang="php">
use Reqxide\Emulation\Profile;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsVersion;
use Reqxide\Tls\AlpnProtocol;
use Reqxide\Tls\KeyShare;
use Reqxide\Http2\Http2Options;

$profile = new Profile(
    tlsOptions: TlsOptions::builder()
        ->minTlsVersion(TlsVersion::TLS_1_2)
        ->maxTlsVersion(TlsVersion::TLS_1_3)
        ->cipherList('TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384')
        ->alpnProtocols([AlpnProtocol::Http2, AlpnProtocol::Http1])
        ->keyShares([KeyShare::X25519])
        ->greaseEnabled(true)
        ->permuteExtensions(true)
        ->build(),
    http2Options: Http2Options::builder()
        ->headerTableSize(65536)
        ->initialWindowSize(6291456)
        ->build(),
    defaultHeaders: ['User-Agent' => 'MyCustomAgent/1.0'],
);

$client = Client::builder()
    ->profile($profile)
    ->build();
</code-snippet>
@endverbatim

### Conventions

- All options classes are `readonly` — use builders (`TlsOptions::builder()`, `Http2Options::builder()`)
- PSR compliance: PSR-18 (HTTP client), PSR-7 (messages via nyholm/psr7), PSR-17 (factories)
- All source files use `declare(strict_types=1)`
- Browser profiles live in `src/Emulation/Catalog/` — one class per browser family
