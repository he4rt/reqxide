<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Psr\Http\Client\RequestExceptionInterface;
use Reqxide\Exception\RedirectException;
use Reqxide\Exception\RequestException;
use Reqxide\Exception\ReqxideException;

it('extends RequestException', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = new RedirectException($request, 'too many redirects');

    expect($exception)->toBeInstanceOf(RequestException::class);
});

it('extends ReqxideException', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = new RedirectException($request, 'too many redirects');

    expect($exception)->toBeInstanceOf(ReqxideException::class);
});

it('implements RequestExceptionInterface', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = new RedirectException($request, 'redirect error');

    expect($exception)->toBeInstanceOf(RequestExceptionInterface::class);
});

it('returns the request via getRequest()', function (): void {
    $request = new Request('GET', 'https://example.com/redirect');
    $exception = new RedirectException($request, 'redirect loop');

    expect($exception->getRequest())->toBe($request);
});

it('stores message and code', function (): void {
    $request = new Request('GET', 'https://example.com');
    $exception = new RedirectException($request, 'too many redirects', 310);

    expect($exception->getMessage())->toBe('too many redirects')
        ->and($exception->getCode())->toBe(310);
});

it('stores previous exception', function (): void {
    $request = new Request('GET', 'https://example.com');
    $previous = new RuntimeException('original error');
    $exception = new RedirectException($request, 'redirect failed', 0, $previous);

    expect($exception->getPrevious())->toBe($previous);
});
