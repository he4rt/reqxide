<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Psr\Http\Client\NetworkExceptionInterface;
use Reqxide\Exception\NetworkException;
use Reqxide\Exception\ReqxideException;

it('extends ReqxideException', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = new NetworkException($request, 'network error');

    expect($exception)->toBeInstanceOf(ReqxideException::class);
});

it('implements NetworkExceptionInterface', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = new NetworkException($request, 'network error');

    expect($exception)->toBeInstanceOf(NetworkExceptionInterface::class);
});

it('returns the request via getRequest()', function (): void {
    $request = new Request('POST', 'https://api.example.com/data');
    $exception = new NetworkException($request, 'connection refused');

    expect($exception->getRequest())->toBe($request);
});

it('stores message and code', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = new NetworkException($request, 'timeout', 28);

    expect($exception->getMessage())->toBe('timeout')
        ->and($exception->getCode())->toBe(28);
});

it('stores previous exception', function (): void {
    $request = new Request('GET', 'https://example.com');
    $previous = new RuntimeException('root cause');
    $exception = new NetworkException($request, 'wrapper', 0, $previous);

    expect($exception->getPrevious())->toBe($previous);
});

it('defaults to empty message and zero code', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = new NetworkException($request);

    expect($exception->getMessage())->toBe('')
        ->and($exception->getCode())->toBe(0);
});
