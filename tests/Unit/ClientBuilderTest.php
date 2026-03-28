<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Client;
use Reqxide\ClientBuilder;
use Reqxide\Contract\TransportInterface;
use Reqxide\Cookie\CookieJar;
use Reqxide\Emulation\Browser;
use Reqxide\Emulation\Profile;
use Reqxide\Proxy\Proxy;
use Reqxide\Redirect\RedirectPolicy;
use Reqxide\Retry\RetryPolicy;
use Reqxide\Transport\TransportOptions;

function createMockTransport(): TransportInterface
{
    return new class implements TransportInterface
    {
        public ?RequestInterface $lastRequest = null;

        public ?Profile $lastProfile = null;

        public ?TransportOptions $lastOptions = null;

        public ResponseInterface $response;

        public function __construct()
        {
            $this->response = new Response(200, [], 'OK');
        }

        public function send(RequestInterface $request, Profile $profile, TransportOptions $options): ResponseInterface
        {
            $this->lastRequest = $request;
            $this->lastProfile = $profile;
            $this->lastOptions = $options;

            return $this->response;
        }

        public function supportsFingerprinting(): bool
        {
            return false;
        }

        public function supportsHttp2Configuration(): bool
        {
            return false;
        }
    };
}

it('builds a Client with default Profile when no config is set', function (): void {
    $transport = createMockTransport();
    $client = Client::builder()
        ->transport($transport)
        ->build();

    expect($client)->toBeInstanceOf(Client::class);

    // Send a request to verify the default profile is used
    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    expect($transport->lastProfile)->toBeInstanceOf(Profile::class)
        ->and($transport->lastProfile->tlsOptions)->toBeNull()
        ->and($transport->lastProfile->defaultHeaders)->toBe([]);
});

it('resolves browser profile when emulation is set', function (): void {
    $transport = createMockTransport();
    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport($transport)
        ->build();

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    $expectedProfile = Browser::Chrome131->profile();

    expect($transport->lastProfile->tlsOptions)->toEqual($expectedProfile->tlsOptions)
        ->and($transport->lastProfile->http2Options)->toEqual($expectedProfile->http2Options);
});

it('uses given profile directly when profile is set', function (): void {
    $transport = createMockTransport();
    $profile = new Profile(defaultHeaders: ['X-Custom' => 'value']);
    $client = Client::builder()
        ->profile($profile)
        ->transport($transport)
        ->build();

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    expect($transport->lastProfile)->toBe($profile);
});

it('sets timeoutMs to 5000 when timeout is 5', function (): void {
    $transport = createMockTransport();
    $client = Client::builder()
        ->timeout(5)
        ->transport($transport)
        ->build();

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    expect($transport->lastOptions->timeoutMs)->toBe(5000);
});

it('sets connectTimeoutMs to 3000 when connectTimeout is 3', function (): void {
    $transport = createMockTransport();
    $client = Client::builder()
        ->connectTimeout(3)
        ->transport($transport)
        ->build();

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    expect($transport->lastOptions->connectTimeoutMs)->toBe(3000);
});

it('sets proxy on transport options', function (): void {
    $transport = createMockTransport();
    $proxy = Proxy::socks5('127.0.0.1:9050');
    $client = Client::builder()
        ->proxy($proxy)
        ->transport($transport)
        ->build();

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    expect($transport->lastOptions->proxy)->toBe($proxy);
});

it('disables SSL verification when verify is false', function (): void {
    $transport = createMockTransport();
    $client = Client::builder()
        ->verify(false)
        ->transport($transport)
        ->build();

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    expect($transport->lastOptions->verifySsl)->toBeFalse();
});

it('sets CA bundle path', function (): void {
    $transport = createMockTransport();
    $client = Client::builder()
        ->caBundle('/etc/ssl/certs/ca-bundle.crt')
        ->transport($transport)
        ->build();

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    expect($transport->lastOptions->caBundle)->toBe('/etc/ssl/certs/ca-bundle.crt');
});

it('sets default headers on the client', function (): void {
    $transport = createMockTransport();
    $client = Client::builder()
        ->defaultHeaders(['X-Api-Key' => 'secret123'])
        ->transport($transport)
        ->build();

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    expect($transport->lastRequest->getHeaderLine('X-Api-Key'))->toBe('secret123');
});

it('enables cookie store when cookieStore is true', function (): void {
    $transport = createMockTransport();
    $builder = Client::builder()
        ->cookieStore(true)
        ->transport($transport);

    $client = $builder->build();

    expect($client)->toBeInstanceOf(Client::class);
});

it('accepts a CookieStoreInterface instance', function (): void {
    $transport = createMockTransport();
    $jar = new CookieJar;
    $builder = Client::builder()
        ->cookieStore($jar)
        ->transport($transport);

    $client = $builder->build();

    expect($client)->toBeInstanceOf(Client::class);
});

it('accepts a redirect policy', function (): void {
    $transport = createMockTransport();
    $policy = RedirectPolicy::none();
    $builder = Client::builder()
        ->redirect($policy)
        ->transport($transport);

    $client = $builder->build();

    expect($client)->toBeInstanceOf(Client::class);
});

it('accepts a retry policy', function (): void {
    $transport = createMockTransport();
    $policy = RetryPolicy::never();
    $builder = Client::builder()
        ->retry($policy)
        ->transport($transport);

    $client = $builder->build();

    expect($client)->toBeInstanceOf(Client::class);
});

it('uses given transport', function (): void {
    $transport = createMockTransport();
    $client = Client::builder()
        ->transport($transport)
        ->build();

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    expect($transport->lastRequest)->toBeInstanceOf(RequestInterface::class);
});

it('returns self from all setter methods for fluent API', function (): void {
    $builder = new ClientBuilder;
    $transport = createMockTransport();

    expect($builder->emulation(Browser::Chrome131))->toBe($builder)
        ->and($builder->profile(new Profile))->toBe($builder)
        ->and($builder->transport($transport))->toBe($builder)
        ->and($builder->proxy(Proxy::http('127.0.0.1:8080')))->toBe($builder)
        ->and($builder->timeout(30))->toBe($builder)
        ->and($builder->connectTimeout(10))->toBe($builder)
        ->and($builder->cookieStore(true))->toBe($builder)
        ->and($builder->redirect(RedirectPolicy::none()))->toBe($builder)
        ->and($builder->retry(RetryPolicy::never()))->toBe($builder)
        ->and($builder->defaultHeaders([]))->toBe($builder)
        ->and($builder->verify(true))->toBe($builder)
        ->and($builder->caBundle('/path'))->toBe($builder);
});

it('returns a ClientBuilder from Client::builder()', function (): void {
    $builder = Client::builder();

    expect($builder)->toBeInstanceOf(ClientBuilder::class);
});

it('profile takes precedence over emulation', function (): void {
    $transport = createMockTransport();
    $customProfile = new Profile(defaultHeaders: ['X-Source' => 'custom-profile']);
    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->profile($customProfile)
        ->transport($transport)
        ->build();

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    expect($transport->lastProfile)->toBe($customProfile);
});
