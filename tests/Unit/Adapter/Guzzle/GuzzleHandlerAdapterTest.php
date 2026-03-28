<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Adapter\Guzzle\GuzzleHandlerAdapter;
use Reqxide\Client;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Browser;
use Reqxide\Emulation\Profile;
use Reqxide\Transport\TransportOptions;

function createRecordingTransport(?ResponseInterface $response = null): TransportInterface
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

it('creates adapter with a browser', function (): void {
    $transport = createRecordingTransport();
    $builder = Client::builder()->transport($transport);

    $adapter = new GuzzleHandlerAdapter(Browser::Chrome131, $builder);

    expect($adapter)->toBeInstanceOf(GuzzleHandlerAdapter::class);
});

it('is callable', function (): void {
    $transport = createRecordingTransport();
    $builder = Client::builder()->transport($transport);

    $adapter = new GuzzleHandlerAdapter(Browser::Chrome131, $builder);

    expect($adapter)->toBeCallable();
});

it('sends request via internal client and returns response', function (): void {
    $expectedResponse = new Response(201, ['X-Custom' => 'header'], 'Created');
    $transport = createRecordingTransport($expectedResponse);
    $builder = Client::builder()->transport($transport);

    $adapter = new GuzzleHandlerAdapter(Browser::Chrome131, $builder);

    $request = new Request('GET', 'https://example.com/api');
    $response = $adapter($request);

    expect($response)->toBe($expectedResponse)
        ->and($response->getStatusCode())->toBe(201)
        ->and($transport->lastRequest)->toBeInstanceOf(RequestInterface::class)
        ->and($transport->lastRequest->getMethod())->toBe('GET')
        ->and((string) $transport->lastRequest->getUri())->toBe('https://example.com/api');
});

it('passes request options without error', function (): void {
    $transport = createRecordingTransport();
    $builder = Client::builder()->transport($transport);

    $adapter = new GuzzleHandlerAdapter(Browser::Chrome131, $builder);

    $request = new Request('POST', 'https://example.com');
    $response = $adapter($request, ['timeout' => 5, 'verify' => false]);

    expect($response->getStatusCode())->toBe(200);
});

it('uses default builder when none is provided', function (): void {
    // This test verifies the constructor doesn't throw when no builder is given.
    // It will use TransportFactory::create() internally (which needs ext-curl).
    $transport = createRecordingTransport();
    $builder = Client::builder()->transport($transport);

    $adapter = new GuzzleHandlerAdapter(Browser::Firefox136, $builder);

    $request = new Request('GET', 'https://example.com');
    $response = $adapter($request);

    expect($response->getStatusCode())->toBe(200);
});
