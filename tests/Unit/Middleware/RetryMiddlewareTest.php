<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Contract\RetryPolicyInterface;
use Reqxide\Middleware\RetryMiddleware;

it('does not retry on success', function (): void {
    $policy = new class implements RetryPolicyInterface
    {
        public function shouldRetry(RequestInterface $request, ?ResponseInterface $response, ?Throwable $exception, int $attempt): bool
        {
            return false;
        }

        public function delayMs(int $attempt): int
        {
            return 0;
        }
    };

    $middleware = new RetryMiddleware($policy);
    $request = new Request('GET', 'https://example.com');
    $response = new Response(200, [], 'OK');

    $callCount = 0;
    $result = $middleware->handle($request, static function (RequestInterface $req) use (&$callCount, $response): ResponseInterface {
        $callCount++;

        return $response;
    });

    expect($result)->toBe($response)
        ->and($callCount)->toBe(1);
});

it('retries on 500 response', function (): void {
    $policy = new class implements RetryPolicyInterface
    {
        public function shouldRetry(RequestInterface $request, ?ResponseInterface $response, ?Throwable $exception, int $attempt): bool
        {
            if ($attempt >= 2) {
                return false;
            }

            return $response instanceof ResponseInterface && $response->getStatusCode() >= 500;
        }

        public function delayMs(int $attempt): int
        {
            return 0;
        }
    };

    $middleware = new RetryMiddleware($policy);
    $request = new Request('GET', 'https://example.com');

    $callCount = 0;
    $result = $middleware->handle($request, static function (RequestInterface $req) use (&$callCount): ResponseInterface {
        $callCount++;

        if ($callCount < 3) {
            return new Response(500);
        }

        return new Response(200, [], 'OK');
    });

    expect($result->getStatusCode())->toBe(200)
        ->and($callCount)->toBe(3);
});

it('retries on exception', function (): void {
    $policy = new class implements RetryPolicyInterface
    {
        public function shouldRetry(RequestInterface $request, ?ResponseInterface $response, ?Throwable $exception, int $attempt): bool
        {
            if ($attempt >= 2) {
                return false;
            }

            return $exception instanceof Throwable;
        }

        public function delayMs(int $attempt): int
        {
            return 0;
        }
    };

    $middleware = new RetryMiddleware($policy);
    $request = new Request('GET', 'https://example.com');

    $callCount = 0;
    $result = $middleware->handle($request, static function (RequestInterface $req) use (&$callCount): ResponseInterface {
        $callCount++;

        if ($callCount === 1) {
            throw new RuntimeException('Connection failed');
        }

        return new Response(200, [], 'OK');
    });

    expect($result->getStatusCode())->toBe(200)
        ->and($callCount)->toBe(2);
});

it('respects max retries', function (): void {
    $policy = new class implements RetryPolicyInterface
    {
        public function shouldRetry(RequestInterface $request, ?ResponseInterface $response, ?Throwable $exception, int $attempt): bool
        {
            return $attempt < 1;
        }

        public function delayMs(int $attempt): int
        {
            return 0;
        }
    };

    $middleware = new RetryMiddleware($policy);
    $request = new Request('GET', 'https://example.com');

    $callCount = 0;
    $result = $middleware->handle($request, static function (RequestInterface $req) use (&$callCount): ResponseInterface {
        $callCount++;

        return new Response(500);
    });

    expect($result->getStatusCode())->toBe(500)
        ->and($callCount)->toBe(2);
});

it('gives up after max attempts and returns last response', function (): void {
    $policy = new class implements RetryPolicyInterface
    {
        public function shouldRetry(RequestInterface $request, ?ResponseInterface $response, ?Throwable $exception, int $attempt): bool
        {
            return $attempt < 3;
        }

        public function delayMs(int $attempt): int
        {
            return 0;
        }
    };

    $middleware = new RetryMiddleware($policy);
    $request = new Request('GET', 'https://example.com');

    $callCount = 0;
    $result = $middleware->handle($request, static function (RequestInterface $req) use (&$callCount): ResponseInterface {
        $callCount++;

        return new Response(503);
    });

    expect($result->getStatusCode())->toBe(503)
        ->and($callCount)->toBe(4);
});

it('re-throws exception when no more retries', function (): void {
    $policy = new class implements RetryPolicyInterface
    {
        public function shouldRetry(RequestInterface $request, ?ResponseInterface $response, ?Throwable $exception, int $attempt): bool
        {
            return false;
        }

        public function delayMs(int $attempt): int
        {
            return 0;
        }
    };

    $middleware = new RetryMiddleware($policy);
    $request = new Request('GET', 'https://example.com');

    expect(static fn (): ResponseInterface => $middleware->handle(
        $request,
        static fn (RequestInterface $req): ResponseInterface => throw new RuntimeException('Network error'),
    ))->toThrow(RuntimeException::class, 'Network error');
});
