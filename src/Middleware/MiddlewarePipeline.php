<?php

declare(strict_types=1);

namespace Reqxide\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class MiddlewarePipeline
{
    /** @param list<MiddlewareInterface> $middlewares */
    public function __construct(
        private array $middlewares = [],
    ) {}

    /** @param callable(RequestInterface): ResponseInterface $transport */
    public function handle(RequestInterface $request, callable $transport): ResponseInterface
    {
        $handler = array_reduce(
            array_reverse($this->middlewares),
            /** @param callable(RequestInterface): ResponseInterface $next */
            static fn (callable $next, MiddlewareInterface $middleware): \Closure => static fn (RequestInterface $req): ResponseInterface => $middleware->handle($req, $next),
            $transport,
        );

        return $handler($request);
    }
}
