<?php

declare(strict_types=1);

use Psr\Http\Client\ClientExceptionInterface;
use Reqxide\Exception\ReqxideException;
use Reqxide\Exception\TlsException;

it('extends ReqxideException', function (): void {
    $exception = new TlsException('TLS handshake failed');

    expect($exception)->toBeInstanceOf(ReqxideException::class);
});

it('implements ClientExceptionInterface', function (): void {
    $exception = new TlsException('TLS error');

    expect($exception)->toBeInstanceOf(ClientExceptionInterface::class);
});

it('stores message and code', function (): void {
    $exception = new TlsException('certificate verify failed', 60);

    expect($exception->getMessage())->toBe('certificate verify failed')
        ->and($exception->getCode())->toBe(60);
});

it('stores previous exception', function (): void {
    $previous = new RuntimeException('SSL error');
    $exception = new TlsException('wrapper', 0, $previous);

    expect($exception->getPrevious())->toBe($previous);
});
