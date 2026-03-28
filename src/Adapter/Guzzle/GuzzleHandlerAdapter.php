<?php

declare(strict_types=1);

namespace Reqxide\Adapter\Guzzle;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Reqxide\Client;
use Reqxide\ClientBuilder;
use Reqxide\Emulation\Browser;

/**
 * Guzzle handler adapter.
 *
 * Wraps a reqxide Client as a callable that Guzzle can use as a handler.
 * Returns a PromiseInterface as required by Guzzle's middleware stack.
 *
 * Usage with Guzzle:
 *   $handler = new GuzzleHandlerAdapter(Browser::Chrome131);
 *   $guzzle = new \GuzzleHttp\Client(['handler' => \GuzzleHttp\HandlerStack::create($handler)]);
 */
final class GuzzleHandlerAdapter
{
    private readonly Client $client;

    public function __construct(
        Browser $browser,
        ?ClientBuilder $builder = null,
    ) {
        $clientBuilder = $builder ?? Client::builder();
        $this->client = $clientBuilder->emulation($browser)->build();
    }

    /**
     * Invoked by Guzzle as the handler.
     *
     * @param  array<string, mixed>  $options  Guzzle request options (timeout, etc.)
     */
    public function __invoke(RequestInterface $request, array $options = []): PromiseInterface
    {
        try {
            $response = $this->client->sendRequest($request);

            return Create::promiseFor($response);
        } catch (\Throwable $e) {
            return Create::rejectionFor($e);
        }
    }
}
