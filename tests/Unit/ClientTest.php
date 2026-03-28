<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Client;
use Reqxide\ClientBuilder;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Profile;
use Reqxide\Middleware\MiddlewarePipeline;
use Reqxide\RequestBuilder;
use Reqxide\Transport\TransportOptions;

function createTestTransport(?ResponseInterface $response = null): TransportInterface
{
    return new class($response) implements TransportInterface
    {
        public ?RequestInterface $lastRequest = null;

        public ?Profile $lastProfile = null;

        public ResponseInterface $response;

        public function __construct(?ResponseInterface $response)
        {
            $this->response = $response ?? new Response(200, [], 'OK');
        }

        public function send(RequestInterface $request, Profile $profile, TransportOptions $options): ResponseInterface
        {
            $this->lastRequest = $request;
            $this->lastProfile = $profile;

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

it('implements Psr\Http\Client\ClientInterface', function (): void {
    $transport = createTestTransport();
    $client = new Client(
        profile: new Profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
    );

    expect($client)->toBeInstanceOf(ClientInterface::class);
});

it('delegates sendRequest to transport via pipeline', function (): void {
    $expectedResponse = new Response(201, ['X-Custom' => 'value'], 'Created');
    $transport = createTestTransport($expectedResponse);
    $client = new Client(
        profile: new Profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
    );

    $request = new Request('POST', 'https://api.example.com/data');
    $response = $client->sendRequest($request);

    expect($response)->toBe($expectedResponse)
        ->and($transport->lastRequest)->toBeInstanceOf(RequestInterface::class)
        ->and($transport->lastRequest->getMethod())->toBe('POST')
        ->and((string) $transport->lastRequest->getUri())->toBe('https://api.example.com/data');
});

it('merges profile default headers when not set on request', function (): void {
    $transport = createTestTransport();
    $profile = new Profile(defaultHeaders: [
        'User-Agent' => 'Reqxide/1.0',
        'Accept-Language' => 'en-US',
    ]);
    $client = new Client(
        profile: $profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
    );

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    expect($transport->lastRequest->getHeaderLine('User-Agent'))->toBe('Reqxide/1.0')
        ->and($transport->lastRequest->getHeaderLine('Accept-Language'))->toBe('en-US');
});

it('merges builder default headers when not set on request', function (): void {
    $transport = createTestTransport();
    $client = new Client(
        profile: new Profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
        defaultHeaders: ['X-Api-Key' => 'abc123'],
    );

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    expect($transport->lastRequest->getHeaderLine('X-Api-Key'))->toBe('abc123');
});

it('gives request headers priority over default headers', function (): void {
    $transport = createTestTransport();
    $profile = new Profile(defaultHeaders: [
        'User-Agent' => 'Profile-UA',
    ]);
    $client = new Client(
        profile: $profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
        defaultHeaders: ['User-Agent' => 'Builder-UA', 'X-Api-Key' => 'builder-key'],
    );

    $request = new Request('GET', 'https://example.com', [
        'User-Agent' => 'Request-UA',
        'X-Api-Key' => 'request-key',
    ]);
    $client->sendRequest($request);

    expect($transport->lastRequest->getHeaderLine('User-Agent'))->toBe('Request-UA')
        ->and($transport->lastRequest->getHeaderLine('X-Api-Key'))->toBe('request-key');
});

it('returns RequestBuilder from get()', function (): void {
    $transport = createTestTransport();
    $client = new Client(
        profile: new Profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
    );

    expect($client->get('https://example.com'))->toBeInstanceOf(RequestBuilder::class);
});

it('returns RequestBuilder from post()', function (): void {
    $transport = createTestTransport();
    $client = new Client(
        profile: new Profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
    );

    expect($client->post('https://example.com'))->toBeInstanceOf(RequestBuilder::class);
});

it('returns RequestBuilder from put()', function (): void {
    $transport = createTestTransport();
    $client = new Client(
        profile: new Profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
    );

    expect($client->put('https://example.com'))->toBeInstanceOf(RequestBuilder::class);
});

it('returns RequestBuilder from delete()', function (): void {
    $transport = createTestTransport();
    $client = new Client(
        profile: new Profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
    );

    expect($client->delete('https://example.com'))->toBeInstanceOf(RequestBuilder::class);
});

it('returns RequestBuilder from patch()', function (): void {
    $transport = createTestTransport();
    $client = new Client(
        profile: new Profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
    );

    expect($client->patch('https://example.com'))->toBeInstanceOf(RequestBuilder::class);
});

it('returns RequestBuilder from head()', function (): void {
    $transport = createTestTransport();
    $client = new Client(
        profile: new Profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
    );

    expect($client->head('https://example.com'))->toBeInstanceOf(RequestBuilder::class);
});

it('returns ClientBuilder from static builder() method', function (): void {
    expect(Client::builder())->toBeInstanceOf(ClientBuilder::class);
});

it('builder default headers do not overwrite profile default headers', function (): void {
    $transport = createTestTransport();
    $profile = new Profile(defaultHeaders: [
        'Accept' => 'text/html',
    ]);
    $client = new Client(
        profile: $profile,
        transport: $transport,
        transportOptions: new TransportOptions,
        pipeline: new MiddlewarePipeline,
        defaultHeaders: ['X-Extra' => 'extra-value'],
    );

    $request = new Request('GET', 'https://example.com');
    $client->sendRequest($request);

    // Profile header is set because it's added first
    expect($transport->lastRequest->getHeaderLine('Accept'))->toBe('text/html')
        ->and($transport->lastRequest->getHeaderLine('X-Extra'))->toBe('extra-value');
});
