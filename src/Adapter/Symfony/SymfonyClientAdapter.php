<?php

declare(strict_types=1);

namespace Reqxide\Adapter\Symfony;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Client;
use Reqxide\ClientBuilder;
use Reqxide\Emulation\Browser;

/**
 * Symfony HttpClient-compatible adapter.
 *
 * Exposes a request() method matching Symfony's HttpClientInterface signature,
 * backed by a reqxide Client with browser fingerprint emulation.
 *
 * Usage:
 *   $adapter = new SymfonyClientAdapter(Browser::Chrome131);
 *   $response = $adapter->request('GET', 'https://example.com');
 */
final class SymfonyClientAdapter
{
    private readonly Client $client;

    private readonly Psr17Factory $factory;

    public function __construct(
        Browser $browser,
        ?ClientBuilder $builder = null,
    ) {
        $clientBuilder = $builder ?? Client::builder();
        $this->client = $clientBuilder->emulation($browser)->build();
        $this->factory = new Psr17Factory;
    }

    /**
     * Send an HTTP request.
     *
     * @param  array<string, mixed>  $options  Request options (headers, body, json, etc.)
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $request = $this->factory->createRequest($method, $url);

        if (isset($options['headers']) && is_array($options['headers'])) {
            /** @var array<string, string|list<string>> $headers */
            $headers = $options['headers'];
            foreach ($headers as $name => $value) {
                $request = $request->withHeader((string) $name, $value);
            }
        }

        if (isset($options['body']) && is_string($options['body'])) {
            $request = $request->withBody($this->factory->createStream($options['body']));
        }

        if (isset($options['json'])) {
            $encoded = json_encode($options['json'], JSON_THROW_ON_ERROR);
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->factory->createStream($encoded));
        }

        return $this->client->sendRequest($request);
    }
}
