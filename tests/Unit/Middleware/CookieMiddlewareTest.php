<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Reqxide\Contract\CookieStoreInterface;
use Reqxide\Middleware\CookieMiddleware;

it('attaches Cookie header from jar', function (): void {
    $jar = new class implements CookieStoreInterface
    {
        public function setCookies(array $cookieHeaders, UriInterface $uri): void {}

        public function getCookies(UriInterface $uri): array
        {
            return ['session=abc123', 'lang=en'];
        }

        public function clear(): void {}
    };

    $middleware = new CookieMiddleware($jar);
    $request = new Request('GET', 'https://example.com');

    /** @var RequestInterface|null $captured */
    $captured = null;
    $response = new Response(200);

    $middleware->handle($request, static function (RequestInterface $req) use (&$captured, $response): ResponseInterface {
        $captured = $req;

        return $response;
    });

    expect($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->getHeaderLine('Cookie'))->toBe('session=abc123; lang=en');
});

it('stores Set-Cookie headers from response', function (): void {
    /** @var list<string> $storedHeaders */
    $storedHeaders = [];

    /** @var UriInterface|null $storedUri */
    $storedUri = null;

    $jar = new class($storedHeaders, $storedUri) implements CookieStoreInterface
    {
        /** @param list<string> $storedHeaders */
        public function __construct(
            private array &$storedHeaders,
            private ?UriInterface &$storedUri,
        ) {}

        public function setCookies(array $cookieHeaders, UriInterface $uri): void
        {
            $this->storedHeaders = $cookieHeaders;
            $this->storedUri = $uri;
        }

        public function getCookies(UriInterface $uri): array
        {
            return [];
        }

        public function clear(): void {}
    };

    $middleware = new CookieMiddleware($jar);
    $request = new Request('GET', 'https://example.com/page');
    $response = new Response(200, ['Set-Cookie' => ['session=abc123; Path=/', 'lang=en; Path=/']]);

    $middleware->handle($request, static fn (RequestInterface $req): ResponseInterface => $response);

    expect($storedHeaders)->toBe(['session=abc123; Path=/', 'lang=en; Path=/'])
        ->and($storedUri)->toBeInstanceOf(UriInterface::class)
        ->and((string) $storedUri)->toBe('https://example.com/page');
});

it('does not attach Cookie header when jar is empty', function (): void {
    $jar = new class implements CookieStoreInterface
    {
        public function setCookies(array $cookieHeaders, UriInterface $uri): void {}

        public function getCookies(UriInterface $uri): array
        {
            return [];
        }

        public function clear(): void {}
    };

    $middleware = new CookieMiddleware($jar);
    $request = new Request('GET', 'https://example.com');

    /** @var RequestInterface|null $captured */
    $captured = null;
    $response = new Response(200);

    $middleware->handle($request, static function (RequestInterface $req) use (&$captured, $response): ResponseInterface {
        $captured = $req;

        return $response;
    });

    expect($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->hasHeader('Cookie'))->toBeFalse();
});

it('does not process Set-Cookie when not present in response', function (): void {
    $setCookieCalled = false;

    $jar = new class($setCookieCalled) implements CookieStoreInterface
    {
        public function __construct(private bool &$setCookieCalled) {}

        public function setCookies(array $cookieHeaders, UriInterface $uri): void
        {
            $this->setCookieCalled = true;
        }

        public function getCookies(UriInterface $uri): array
        {
            return [];
        }

        public function clear(): void {}
    };

    $middleware = new CookieMiddleware($jar);
    $request = new Request('GET', 'https://example.com');
    $response = new Response(200);

    $middleware->handle($request, static fn (RequestInterface $req): ResponseInterface => $response);

    expect($setCookieCalled)->toBeFalse();
});

it('joins multiple cookies with semicolon and space', function (): void {
    $jar = new class implements CookieStoreInterface
    {
        public function setCookies(array $cookieHeaders, UriInterface $uri): void {}

        public function getCookies(UriInterface $uri): array
        {
            return ['a=1', 'b=2', 'c=3'];
        }

        public function clear(): void {}
    };

    $middleware = new CookieMiddleware($jar);
    $request = new Request('GET', 'https://example.com');

    /** @var RequestInterface|null $captured */
    $captured = null;
    $response = new Response(200);

    $middleware->handle($request, static function (RequestInterface $req) use (&$captured, $response): ResponseInterface {
        $captured = $req;

        return $response;
    });

    expect($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->getHeaderLine('Cookie'))->toBe('a=1; b=2; c=3');
});
