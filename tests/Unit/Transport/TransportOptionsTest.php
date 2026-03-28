<?php

declare(strict_types=1);

use Reqxide\Proxy\Proxy;
use Reqxide\Transport\TransportOptions;

it('has correct default values', function (): void {
    $options = new TransportOptions;

    expect($options->timeoutMs)->toBe(30_000)
        ->and($options->connectTimeoutMs)->toBe(10_000)
        ->and($options->verifySsl)->toBeTrue()
        ->and($options->caBundle)->toBeNull()
        ->and($options->proxy)->toBeNull();
});

it('can be constructed with custom values', function (): void {
    $proxy = Proxy::http('127.0.0.1:8080');

    $options = new TransportOptions(
        timeoutMs: 60_000,
        connectTimeoutMs: 5_000,
        verifySsl: false,
        caBundle: '/etc/ssl/certs/ca-certificates.crt',
        proxy: $proxy,
    );

    expect($options->timeoutMs)->toBe(60_000)
        ->and($options->connectTimeoutMs)->toBe(5_000)
        ->and($options->verifySsl)->toBeFalse()
        ->and($options->caBundle)->toBe('/etc/ssl/certs/ca-certificates.crt')
        ->and($options->proxy)->toBe($proxy);
});

it('can be constructed with partial custom values', function (): void {
    $options = new TransportOptions(
        timeoutMs: 15_000,
        verifySsl: false,
    );

    expect($options->timeoutMs)->toBe(15_000)
        ->and($options->connectTimeoutMs)->toBe(10_000)
        ->and($options->verifySsl)->toBeFalse()
        ->and($options->caBundle)->toBeNull()
        ->and($options->proxy)->toBeNull();
});
