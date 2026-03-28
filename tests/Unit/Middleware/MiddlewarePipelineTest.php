<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Middleware\MiddlewareInterface;
use Reqxide\Middleware\MiddlewarePipeline;

it('passes request directly to transport when pipeline is empty', function (): void {
    $pipeline = new MiddlewarePipeline;
    $request = new Request('GET', 'https://example.com');
    $response = new Response(200, [], 'OK');

    $result = $pipeline->handle($request, static fn (RequestInterface $req): ResponseInterface => $response);

    expect($result)->toBe($response);
});

it('wraps transport with a single middleware', function (): void {
    $middleware = new class implements MiddlewareInterface
    {
        public function handle(RequestInterface $request, callable $next): ResponseInterface
        {
            $request = $request->withHeader('X-Middleware', 'applied');

            return $next($request);
        }
    };

    $pipeline = new MiddlewarePipeline([$middleware]);
    $request = new Request('GET', 'https://example.com');

    /** @var RequestInterface|null $captured */
    $captured = null;
    $response = new Response(200, [], 'OK');

    $result = $pipeline->handle($request, static function (RequestInterface $req) use (&$captured, $response): ResponseInterface {
        $captured = $req;

        return $response;
    });

    expect($result)->toBe($response)
        ->and($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->getHeaderLine('X-Middleware'))->toBe('applied');
});

it('executes multiple middlewares in correct order', function (): void {
    /** @var list<string> $order */
    $order = [];

    $first = new class($order) implements MiddlewareInterface
    {
        /** @param list<string> $order */
        public function __construct(private array &$order) {}

        public function handle(RequestInterface $request, callable $next): ResponseInterface
        {
            $this->order[] = 'first-before';
            $response = $next($request);
            $this->order[] = 'first-after';

            return $response;
        }
    };

    $second = new class($order) implements MiddlewareInterface
    {
        /** @param list<string> $order */
        public function __construct(private array &$order) {}

        public function handle(RequestInterface $request, callable $next): ResponseInterface
        {
            $this->order[] = 'second-before';
            $response = $next($request);
            $this->order[] = 'second-after';

            return $response;
        }
    };

    $pipeline = new MiddlewarePipeline([$first, $second]);
    $request = new Request('GET', 'https://example.com');
    $response = new Response(200);

    $pipeline->handle($request, static function (RequestInterface $req) use (&$order, $response): ResponseInterface {
        $order[] = 'transport';

        return $response;
    });

    expect($order)->toBe(['first-before', 'second-before', 'transport', 'second-after', 'first-after']);
});

it('allows middleware to modify request before passing to next', function (): void {
    $middleware = new class implements MiddlewareInterface
    {
        public function handle(RequestInterface $request, callable $next): ResponseInterface
        {
            $request = $request->withHeader('Authorization', 'Bearer token123');

            return $next($request);
        }
    };

    $pipeline = new MiddlewarePipeline([$middleware]);
    $request = new Request('GET', 'https://example.com');

    /** @var RequestInterface|null $captured */
    $captured = null;

    $pipeline->handle($request, static function (RequestInterface $req) use (&$captured): ResponseInterface {
        $captured = $req;

        return new Response(200);
    });

    expect($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->getHeaderLine('Authorization'))->toBe('Bearer token123');
});

it('allows middleware to modify response after receiving from next', function (): void {
    $middleware = new class implements MiddlewareInterface
    {
        public function handle(RequestInterface $request, callable $next): ResponseInterface
        {
            $response = $next($request);

            return $response->withHeader('X-Modified', 'true');
        }
    };

    $pipeline = new MiddlewarePipeline([$middleware]);
    $request = new Request('GET', 'https://example.com');
    $response = new Response(200, [], 'OK');

    $result = $pipeline->handle($request, static fn (RequestInterface $req): ResponseInterface => $response);

    expect($result->getHeaderLine('X-Modified'))->toBe('true');
});
