<?php

declare(strict_types=1);

use Psr\Http\Client\ClientExceptionInterface;
use Reqxide\Exception\ReqxideException;
use Reqxide\Exception\TransportException;

it('extends ReqxideException', function (): void {
    $exception = new TransportException('transport failed');

    expect($exception)->toBeInstanceOf(ReqxideException::class);
});

it('implements ClientExceptionInterface', function (): void {
    $exception = new TransportException('transport error');

    expect($exception)->toBeInstanceOf(ClientExceptionInterface::class);
});

it('stores message and code', function (): void {
    $exception = new TransportException('curl exec failed', 7);

    expect($exception->getMessage())->toBe('curl exec failed')
        ->and($exception->getCode())->toBe(7);
});

it('stores previous exception', function (): void {
    $previous = new RuntimeException('curl error');
    $exception = new TransportException('wrapper', 0, $previous);

    expect($exception->getPrevious())->toBe($previous);
});
