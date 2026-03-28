<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Client;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Profile;
use Reqxide\Middleware\MiddlewarePipeline;
use Reqxide\RequestBuilder;
use Reqxide\Transport\TransportOptions;

function createRequestBuilderTransport(?ResponseInterface $response = null): TransportInterface
{
    return new class($response) implements TransportInterface
    {
        public ?RequestInterface $lastRequest = null;

        public ResponseInterface $response;

        public function __construct(?ResponseInterface $response)
        {
            $this->response = $response ?? new Response(200, [], 'OK');
        }

        public function send(RequestInterface $request, Profile $profile, TransportOptions $options): ResponseInterface
        {
            $this->lastRequest = $request;

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

function createClientWithTransport(TransportInterface $transport): Client
{
    return new Client(
        profile: new Profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
    );
}

it('adds a header to the request', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $client->get('https://example.com')
        ->header('X-Custom', 'hello')
        ->send();

    expect($transport->lastRequest->getHeaderLine('X-Custom'))->toBe('hello');
});

it('adds multiple headers to the request', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $client->get('https://example.com')
        ->headers([
            'X-First' => 'one',
            'X-Second' => 'two',
        ])
        ->send();

    expect($transport->lastRequest->getHeaderLine('X-First'))->toBe('one')
        ->and($transport->lastRequest->getHeaderLine('X-Second'))->toBe('two');
});

it('sets Authorization Bearer header', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $client->get('https://example.com')
        ->bearerToken('my-token-123')
        ->send();

    expect($transport->lastRequest->getHeaderLine('Authorization'))->toBe('Bearer my-token-123');
});

it('sets Authorization Basic header with base64 encoding', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $client->get('https://example.com')
        ->basicAuth('user', 'pass')
        ->send();

    $expected = 'Basic '.base64_encode('user:pass');
    expect($transport->lastRequest->getHeaderLine('Authorization'))->toBe($expected);
});

it('sets a string body', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $client->post('https://example.com')
        ->body('raw body content')
        ->send();

    expect((string) $transport->lastRequest->getBody())->toBe('raw body content');
});

it('sets a stream body', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);
    $factory = new Psr17Factory;
    $stream = $factory->createStream('stream body');

    $client->post('https://example.com')
        ->body($stream)
        ->send();

    expect((string) $transport->lastRequest->getBody())->toBe('stream body');
});

it('sets JSON body and Content-Type header', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $client->post('https://api.example.com')
        ->json(['key' => 'value', 'number' => 42])
        ->send();

    expect((string) $transport->lastRequest->getBody())->toBe('{"key":"value","number":42}')
        ->and($transport->lastRequest->getHeaderLine('Content-Type'))->toBe('application/json');
});

it('sets form body and Content-Type header', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $client->post('https://example.com')
        ->form(['username' => 'john', 'password' => 'secret'])
        ->send();

    expect((string) $transport->lastRequest->getBody())->toBe('username=john&password=secret')
        ->and($transport->lastRequest->getHeaderLine('Content-Type'))->toBe('application/x-www-form-urlencoded');
});

it('appends query params to URI', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $client->get('https://example.com/search')
        ->query(['q' => 'php', 'page' => '1'])
        ->send();

    expect((string) $transport->lastRequest->getUri())->toBe('https://example.com/search?q=php&page=1');
});

it('appends query params with & when URI already has query string', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $client->get('https://example.com/search?existing=true')
        ->query(['extra' => 'param'])
        ->send();

    expect((string) $transport->lastRequest->getUri())->toBe('https://example.com/search?existing=true&extra=param');
});

it('sends request via Client::sendRequest with properly built PSR-7 request', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $response = $client->post('https://api.example.com/data')
        ->header('Accept', 'application/json')
        ->json(['name' => 'test'])
        ->send();

    expect($response->getStatusCode())->toBe(200)
        ->and($transport->lastRequest->getMethod())->toBe('POST')
        ->and((string) $transport->lastRequest->getUri())->toBe('https://api.example.com/data')
        ->and($transport->lastRequest->getHeaderLine('Accept'))->toBe('application/json')
        ->and($transport->lastRequest->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and((string) $transport->lastRequest->getBody())->toBe('{"name":"test"}');
});

it('returns self from all methods for fluent API', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);
    $factory = new Psr17Factory;

    $builder = new RequestBuilder($client, $factory, 'GET', 'https://example.com');

    expect($builder->header('X-Test', 'value'))->toBe($builder)
        ->and($builder->headers(['X-A' => 'a']))->toBe($builder)
        ->and($builder->bearerToken('tok'))->toBe($builder)
        ->and($builder->basicAuth('u', 'p'))->toBe($builder)
        ->and($builder->body('content'))->toBe($builder)
        ->and($builder->json(['k' => 'v']))->toBe($builder)
        ->and($builder->form(['k' => 'v']))->toBe($builder)
        ->and($builder->query(['q' => '1']))->toBe($builder);
});

it('allows json Content-Type to be overridden by subsequent header call', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $client->post('https://example.com')
        ->json(['key' => 'value'])
        ->header('Content-Type', 'application/json; charset=utf-8')
        ->send();

    expect($transport->lastRequest->getHeaderLine('Content-Type'))->toBe('application/json; charset=utf-8');
});

it('sends without body when none is set', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $client->get('https://example.com')
        ->send();

    expect((string) $transport->lastRequest->getBody())->toBe('');
});

it('sends without query params when none are set', function (): void {
    $transport = createRequestBuilderTransport();
    $client = createClientWithTransport($transport);

    $client->get('https://example.com/path')
        ->send();

    expect((string) $transport->lastRequest->getUri())->toBe('https://example.com/path');
});
