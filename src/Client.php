<?php

declare(strict_types=1);

namespace Reqxide;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Profile;
use Reqxide\Middleware\MiddlewarePipeline;
use Reqxide\Transport\TransportOptions;

final readonly class Client implements ClientInterface
{
    private Psr17Factory $factory;

    /**
     * @param  array<string, string>  $defaultHeaders
     */
    public function __construct(
        private Profile $profile,
        private TransportInterface $transport,
        private TransportOptions $transportOptions,
        private MiddlewarePipeline $pipeline,
        private array $defaultHeaders = [],
    ) {
        $this->factory = new Psr17Factory;
    }

    public static function builder(): ClientBuilder
    {
        return new ClientBuilder;
    }

    /**
     * PSR-18: Sends a PSR-7 request and returns a PSR-7 response.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $request = $this->mergeDefaultHeaders($request);

        return $this->pipeline->handle(
            $request,
            fn (RequestInterface $req): ResponseInterface => $this->transport->send(
                $req,
                $this->profile,
                $this->transportOptions,
            ),
        );
    }

    public function get(string $uri): RequestBuilder
    {
        return new RequestBuilder($this, $this->factory, 'GET', $uri);
    }

    public function post(string $uri): RequestBuilder
    {
        return new RequestBuilder($this, $this->factory, 'POST', $uri);
    }

    public function put(string $uri): RequestBuilder
    {
        return new RequestBuilder($this, $this->factory, 'PUT', $uri);
    }

    public function delete(string $uri): RequestBuilder
    {
        return new RequestBuilder($this, $this->factory, 'DELETE', $uri);
    }

    public function patch(string $uri): RequestBuilder
    {
        return new RequestBuilder($this, $this->factory, 'PATCH', $uri);
    }

    public function head(string $uri): RequestBuilder
    {
        return new RequestBuilder($this, $this->factory, 'HEAD', $uri);
    }

    private function mergeDefaultHeaders(RequestInterface $request): RequestInterface
    {
        // Profile default headers (lower priority)
        foreach ($this->profile->defaultHeaders as $name => $value) {
            if (! $request->hasHeader($name)) {
                $request = $request->withHeader($name, $value);
            }
        }

        // Builder-level default headers (medium priority)
        foreach ($this->defaultHeaders as $name => $value) {
            if (! $request->hasHeader($name)) {
                $request = $request->withHeader($name, $value);
            }
        }

        return $request;
    }
}
