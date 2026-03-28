<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Middleware\CompressionMiddleware;

it('adds Accept-Encoding header', function (): void {
    $middleware = new CompressionMiddleware;
    $request = new Request('GET', 'https://example.com');

    /** @var RequestInterface|null $captured */
    $captured = null;
    $response = new Response(200);

    $middleware->handle($request, static function (RequestInterface $req) use (&$captured, $response): ResponseInterface {
        $captured = $req;

        return $response;
    });

    expect($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->hasHeader('Accept-Encoding'))->toBeTrue()
        ->and($captured->getHeaderLine('Accept-Encoding'))->toContain('gzip')
        ->and($captured->getHeaderLine('Accept-Encoding'))->toContain('deflate');
});

it('does not override existing Accept-Encoding', function (): void {
    $middleware = new CompressionMiddleware;
    $request = new Request('GET', 'https://example.com', ['Accept-Encoding' => 'identity']);

    /** @var RequestInterface|null $captured */
    $captured = null;
    $response = new Response(200);

    $middleware->handle($request, static function (RequestInterface $req) use (&$captured, $response): ResponseInterface {
        $captured = $req;

        return $response;
    });

    expect($captured)->toBeInstanceOf(RequestInterface::class)
        ->and($captured->getHeaderLine('Accept-Encoding'))->toBe('identity');
});

it('decompresses gzip response', function (): void {
    $middleware = new CompressionMiddleware;
    $request = new Request('GET', 'https://example.com');
    $originalBody = 'Hello, World!';
    $compressedBody = gzencode($originalBody);

    $response = new Response(200, [
        'Content-Encoding' => 'gzip',
        'Content-Length' => (string) strlen($compressedBody),
    ], $compressedBody);

    $result = $middleware->handle($request, static fn (RequestInterface $req): ResponseInterface => $response);

    expect((string) $result->getBody())->toBe($originalBody)
        ->and($result->hasHeader('Content-Encoding'))->toBeFalse()
        ->and($result->hasHeader('Content-Length'))->toBeFalse();
});

it('decompresses deflate response', function (): void {
    $middleware = new CompressionMiddleware;
    $request = new Request('GET', 'https://example.com');
    $originalBody = 'Hello, Deflate!';
    $compressedBody = gzdeflate($originalBody);

    $response = new Response(200, [
        'Content-Encoding' => 'deflate',
        'Content-Length' => (string) strlen($compressedBody),
    ], $compressedBody);

    $result = $middleware->handle($request, static fn (RequestInterface $req): ResponseInterface => $response);

    expect((string) $result->getBody())->toBe($originalBody)
        ->and($result->hasHeader('Content-Encoding'))->toBeFalse()
        ->and($result->hasHeader('Content-Length'))->toBeFalse();
});

it('passes through uncompressed response', function (): void {
    $middleware = new CompressionMiddleware;
    $request = new Request('GET', 'https://example.com');
    $body = 'Plain text body';

    $response = new Response(200, [], $body);

    $result = $middleware->handle($request, static fn (RequestInterface $req): ResponseInterface => $response);

    expect((string) $result->getBody())->toBe($body)
        ->and($result->hasHeader('Content-Encoding'))->toBeFalse();
});

it('removes Content-Encoding and Content-Length after decompression', function (): void {
    $middleware = new CompressionMiddleware;
    $request = new Request('GET', 'https://example.com');
    $originalBody = 'Test body content';
    $compressedBody = gzencode($originalBody);

    $response = new Response(200, [
        'Content-Encoding' => 'gzip',
        'Content-Length' => (string) strlen($compressedBody),
        'Content-Type' => 'text/plain',
    ], $compressedBody);

    $result = $middleware->handle($request, static fn (RequestInterface $req): ResponseInterface => $response);

    expect($result->hasHeader('Content-Encoding'))->toBeFalse()
        ->and($result->hasHeader('Content-Length'))->toBeFalse()
        ->and($result->hasHeader('Content-Type'))->toBeTrue()
        ->and($result->getHeaderLine('Content-Type'))->toBe('text/plain');
});

it('handles unknown encoding gracefully', function (): void {
    $middleware = new CompressionMiddleware;
    $request = new Request('GET', 'https://example.com');
    $body = 'Some encoded body';

    $response = new Response(200, [
        'Content-Encoding' => 'unknown-encoding',
    ], $body);

    $result = $middleware->handle($request, static fn (RequestInterface $req): ResponseInterface => $response);

    expect((string) $result->getBody())->toBe($body)
        ->and($result->hasHeader('Content-Encoding'))->toBeTrue()
        ->and($result->getHeaderLine('Content-Encoding'))->toBe('unknown-encoding');
});
