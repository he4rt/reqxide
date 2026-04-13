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

it('creates from curl exit code with stderr detail', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = NetworkException::fromCurlExitCode($request, 7, 'Connection refused');

    expect($exception)->toBeInstanceOf(NetworkException::class)
        ->and($exception->getMessage())->toBe('curl_impersonate failed (exit 7): Connection refused')
        ->and($exception->getCode())->toBe(7)
        ->and($exception->getRequest())->toBe($request);
});

it('creates from curl exit code with mapped description when stderr is empty', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = NetworkException::fromCurlExitCode($request, 28);

    expect($exception->getMessage())->toBe('curl_impersonate failed (exit 28): operation timed out')
        ->and($exception->getCode())->toBe(28);
});

it('creates from curl exit code with unknown code fallback', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = NetworkException::fromCurlExitCode($request, 999);

    expect($exception->getMessage())->toBe('curl_impersonate failed (exit 999): unknown error (code 999)')
        ->and($exception->getCode())->toBe(999);
});
