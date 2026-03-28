<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Psr\Http\Client\RequestExceptionInterface;
use Reqxide\Exception\RequestException;
use Reqxide\Exception\ReqxideException;

it('extends ReqxideException', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = new RequestException($request, 'bad request');

    expect($exception)->toBeInstanceOf(ReqxideException::class);
});

it('implements RequestExceptionInterface', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = new RequestException($request, 'bad request');

    expect($exception)->toBeInstanceOf(RequestExceptionInterface::class);
});

it('returns the request via getRequest()', function (): void {
    $request = new Request('DELETE', 'https://api.example.com/resource/1');
    $exception = new RequestException($request, 'invalid URI');

    expect($exception->getRequest())->toBe($request);
});

it('stores message and code', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = new RequestException($request, 'malformed request', 400);

    expect($exception->getMessage())->toBe('malformed request')
        ->and($exception->getCode())->toBe(400);
});

it('stores previous exception', function (): void {
    $request = new Request('GET', 'https://example.com');
    $previous = new RuntimeException('root cause');
    $exception = new RequestException($request, 'wrapper', 0, $previous);

    expect($exception->getPrevious())->toBe($previous);
});

it('defaults to empty message and zero code', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = new RequestException($request);

    expect($exception->getMessage())->toBe('')
        ->and($exception->getCode())->toBe(0);
});
