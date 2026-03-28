<?php

declare(strict_types=1);

use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Adapter\Symfony\SymfonyClientAdapter;
use Reqxide\Client;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Browser;
use Reqxide\Emulation\Profile;
use Reqxide\Transport\TransportOptions;

function createSymfonyRecordingTransport(?ResponseInterface $response = null): TransportInterface
{
    return new class($response) implements TransportInterface
    {
        public ?RequestInterface $lastRequest = null;

        public ResponseInterface $response;

        public function __construct(?ResponseInterface $response)
        {
            $this->response = $response ?? new Response(200, [], 'ok');
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

it('sends a GET request', function (): void {
    $transport = createSymfonyRecordingTransport();
    $builder = Client::builder()->transport($transport);

    $adapter = new SymfonyClientAdapter(Browser::Chrome131, $builder);
    $response = $adapter->request('GET', 'https://example.com');

    expect($response->getStatusCode())->toBe(200)
        ->and($transport->lastRequest)->toBeInstanceOf(RequestInterface::class)
        ->and($transport->lastRequest->getMethod())->toBe('GET')
        ->and((string) $transport->lastRequest->getUri())->toBe('https://example.com');
});

it('sends a POST request', function (): void {
    $transport = createSymfonyRecordingTransport();
    $builder = Client::builder()->transport($transport);

    $adapter = new SymfonyClientAdapter(Browser::Chrome131, $builder);
    $response = $adapter->request('POST', 'https://example.com/api');

    expect($transport->lastRequest->getMethod())->toBe('POST')
        ->and((string) $transport->lastRequest->getUri())->toBe('https://example.com/api');
});

it('applies headers from options', function (): void {
    $transport = createSymfonyRecordingTransport();
    $builder = Client::builder()->transport($transport);

    $adapter = new SymfonyClientAdapter(Browser::Chrome131, $builder);
    $adapter->request('GET', 'https://example.com', [
        'headers' => [
            'Authorization' => 'Bearer token123',
            'Accept' => 'application/json',
        ],
    ]);

    expect($transport->lastRequest->getHeaderLine('Authorization'))->toBe('Bearer token123')
        ->and($transport->lastRequest->getHeaderLine('Accept'))->toBe('application/json');
});

it('applies body from options', function (): void {
    $transport = createSymfonyRecordingTransport();
    $builder = Client::builder()->transport($transport);

    $adapter = new SymfonyClientAdapter(Browser::Chrome131, $builder);
    $adapter->request('POST', 'https://example.com', [
        'body' => 'raw body content',
    ]);

    expect((string) $transport->lastRequest->getBody())->toBe('raw body content');
});

it('applies json from options', function (): void {
    $transport = createSymfonyRecordingTransport();
    $builder = Client::builder()->transport($transport);

    $adapter = new SymfonyClientAdapter(Browser::Chrome131, $builder);
    $adapter->request('POST', 'https://example.com', [
        'json' => ['key' => 'value', 'number' => 42],
    ]);

    expect($transport->lastRequest->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and((string) $transport->lastRequest->getBody())->toBe('{"key":"value","number":42}');
});

it('ignores non-array headers option', function (): void {
    $transport = createSymfonyRecordingTransport();
    $builder = Client::builder()->transport($transport);

    $adapter = new SymfonyClientAdapter(Browser::Chrome131, $builder);
    $adapter->request('GET', 'https://example.com', [
        'headers' => 'not-an-array',
    ]);

    expect($transport->lastRequest)->toBeInstanceOf(RequestInterface::class);
});

it('ignores non-string body option', function (): void {
    $transport = createSymfonyRecordingTransport();
    $builder = Client::builder()->transport($transport);

    $adapter = new SymfonyClientAdapter(Browser::Chrome131, $builder);
    $adapter->request('POST', 'https://example.com', [
        'body' => 12345,
    ]);

    expect((string) $transport->lastRequest->getBody())->toBe('');
});

it('json option overrides body content type', function (): void {
    $transport = createSymfonyRecordingTransport();
    $builder = Client::builder()->transport($transport);

    $adapter = new SymfonyClientAdapter(Browser::Chrome131, $builder);
    $adapter->request('POST', 'https://example.com', [
        'json' => ['data' => true],
    ]);

    expect($transport->lastRequest->getHeaderLine('Content-Type'))->toBe('application/json');
});
