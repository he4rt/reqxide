<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Middleware\RedirectMiddleware;
use Reqxide\Redirect\RedirectPolicy;

it('follows 301 redirect', function (): void {
    $policy = RedirectPolicy::limited(10);
    $middleware = new RedirectMiddleware($policy);
    $request = new Request('GET', 'https://example.com/old');

    $callCount = 0;
    $result = $middleware->handle($request, static function (RequestInterface $req) use (&$callCount): ResponseInterface {
        $callCount++;

        if ($callCount === 1) {
            return new Response(301, ['Location' => 'https://example.com/new']);
        }

        return new Response(200, [], 'OK');
    });

    expect($result->getStatusCode())->toBe(200)
        ->and($callCount)->toBe(2);
});

it('follows 302 redirect', function (): void {
    $policy = RedirectPolicy::limited(10);
    $middleware = new RedirectMiddleware($policy);
    $request = new Request('GET', 'https://example.com/old');

    $callCount = 0;
    $result = $middleware->handle($request, static function (RequestInterface $req) use (&$callCount): ResponseInterface {
        $callCount++;

        if ($callCount === 1) {
            return new Response(302, ['Location' => 'https://example.com/new']);
        }

        return new Response(200, [], 'OK');
    });

    expect($result->getStatusCode())->toBe(200)
        ->and($callCount)->toBe(2);
});

it('follows 307 redirect preserving method', function (): void {
    $policy = RedirectPolicy::limited(10);
    $middleware = new RedirectMiddleware($policy);
    $request = new Request('POST', 'https://example.com/old');

    /** @var RequestInterface|null $captured */
    $captured = null;
    $callCount = 0;

    $result = $middleware->handle($request, static function (RequestInterface $req) use (&$callCount, &$captured): ResponseInterface {
        $callCount++;

        if ($callCount === 1) {
            return new Response(307, ['Location' => 'https://example.com/new']);
        }

        $captured = $req;

        return new Response(200, [], 'OK');
    });

    expect($result->getStatusCode())->toBe(200)
        ->and($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->getMethod())->toBe('POST');
});

it('follows 308 redirect preserving method', function (): void {
    $policy = RedirectPolicy::limited(10);
    $middleware = new RedirectMiddleware($policy);
    $request = new Request('PUT', 'https://example.com/old');

    /** @var RequestInterface|null $captured */
    $captured = null;
    $callCount = 0;

    $result = $middleware->handle($request, static function (RequestInterface $req) use (&$callCount, &$captured): ResponseInterface {
        $callCount++;

        if ($callCount === 1) {
            return new Response(308, ['Location' => 'https://example.com/new']);
        }

        $captured = $req;

        return new Response(200, [], 'OK');
    });

    expect($result->getStatusCode())->toBe(200)
        ->and($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->getMethod())->toBe('PUT');
});

it('changes method to GET on 303 redirect', function (): void {
    $policy = RedirectPolicy::limited(10);
    $middleware = new RedirectMiddleware($policy);
    $request = new Request('POST', 'https://example.com/submit');

    /** @var RequestInterface|null $captured */
    $captured = null;
    $callCount = 0;

    $result = $middleware->handle($request, static function (RequestInterface $req) use (&$callCount, &$captured): ResponseInterface {
        $callCount++;

        if ($callCount === 1) {
            return new Response(303, ['Location' => 'https://example.com/result']);
        }

        $captured = $req;

        return new Response(200, [], 'OK');
    });

    expect($result->getStatusCode())->toBe(200)
        ->and($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->getMethod())->toBe('GET');
});

it('changes POST to GET on 301 and 302 redirects', function (): void {
    $policy = RedirectPolicy::limited(10);

    foreach ([301, 302] as $statusCode) {
        $middleware = new RedirectMiddleware($policy);
        $request = new Request('POST', 'https://example.com/submit');

        /** @var RequestInterface|null $captured */
        $captured = null;
        $callCount = 0;

        $middleware->handle($request, static function (RequestInterface $req) use (&$callCount, &$captured, $statusCode): ResponseInterface {
            $callCount++;

            if ($callCount === 1) {
                return new Response($statusCode, ['Location' => 'https://example.com/result']);
            }

            $captured = $req;

            return new Response(200, [], 'OK');
        });

        expect($captured)->toBeInstanceOf(RequestInterface::class)
            ->and($captured->getMethod())->toBe('GET');
    }
});

it('stops when policy says no', function (): void {
    $policy = RedirectPolicy::none();
    $middleware = new RedirectMiddleware($policy);
    $request = new Request('GET', 'https://example.com/old');

    $result = $middleware->handle($request, static fn (RequestInterface $req): ResponseInterface => new Response(301, ['Location' => 'https://example.com/new']));

    expect($result->getStatusCode())->toBe(301);
});

it('strips sensitive headers on cross-origin redirect', function (): void {
    $policy = RedirectPolicy::limited(10);
    $middleware = new RedirectMiddleware($policy);
    $request = new Request('GET', 'https://example.com/old', [
        'Authorization' => 'Bearer secret',
        'Cookie' => 'session=abc',
        'X-Custom' => 'keep-me',
    ]);

    /** @var RequestInterface|null $captured */
    $captured = null;
    $callCount = 0;

    $middleware->handle($request, static function (RequestInterface $req) use (&$callCount, &$captured): ResponseInterface {
        $callCount++;

        if ($callCount === 1) {
            return new Response(302, ['Location' => 'https://other-domain.com/page']);
        }

        $captured = $req;

        return new Response(200, [], 'OK');
    });

    expect($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->hasHeader('Authorization'))->toBeFalse()
        ->and($captured->hasHeader('Cookie'))->toBeFalse()
        ->and($captured->hasHeader('X-Custom'))->toBeTrue()
        ->and($captured->getHeaderLine('X-Custom'))->toBe('keep-me');
});

it('preserves headers on same-origin redirect', function (): void {
    $policy = RedirectPolicy::limited(10);
    $middleware = new RedirectMiddleware($policy);
    $request = new Request('GET', 'https://example.com/old', [
        'Authorization' => 'Bearer secret',
        'Cookie' => 'session=abc',
    ]);

    /** @var RequestInterface|null $captured */
    $captured = null;
    $callCount = 0;

    $middleware->handle($request, static function (RequestInterface $req) use (&$callCount, &$captured): ResponseInterface {
        $callCount++;

        if ($callCount === 1) {
            return new Response(302, ['Location' => 'https://example.com/new']);
        }

        $captured = $req;

        return new Response(200, [], 'OK');
    });

    expect($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->hasHeader('Authorization'))->toBeTrue()
        ->and($captured->getHeaderLine('Authorization'))->toBe('Bearer secret')
        ->and($captured->hasHeader('Cookie'))->toBeTrue()
        ->and($captured->getHeaderLine('Cookie'))->toBe('session=abc');
});

it('handles relative Location URLs', function (): void {
    $policy = RedirectPolicy::limited(10);
    $middleware = new RedirectMiddleware($policy);
    $request = new Request('GET', 'https://example.com/dir/page');

    /** @var RequestInterface|null $captured */
    $captured = null;
    $callCount = 0;

    $middleware->handle($request, static function (RequestInterface $req) use (&$callCount, &$captured): ResponseInterface {
        $callCount++;

        if ($callCount === 1) {
            return new Response(302, ['Location' => '/new-path']);
        }

        $captured = $req;

        return new Response(200, [], 'OK');
    });

    expect($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->getUri()->getPath())->toBe('/new-path')
        ->and($captured->getUri()->getHost())->toBe('example.com');
});

it('returns non-redirect response directly', function (): void {
    $policy = RedirectPolicy::limited(10);
    $middleware = new RedirectMiddleware($policy);
    $request = new Request('GET', 'https://example.com');
    $response = new Response(200, [], 'OK');

    $result = $middleware->handle($request, static fn (RequestInterface $req): ResponseInterface => $response);

    expect($result)->toBe($response);
});

it('returns redirect response when no Location header', function (): void {
    $policy = RedirectPolicy::limited(10);
    $middleware = new RedirectMiddleware($policy);
    $request = new Request('GET', 'https://example.com');
    $response = new Response(301);

    $result = $middleware->handle($request, static fn (RequestInterface $req): ResponseInterface => $response);

    expect($result->getStatusCode())->toBe(301);
});
