<?php

declare(strict_types=1);

use Reqxide\Tls\CipherSuite;

it('has the correct number of cases', function (): void {
    expect(CipherSuite::cases())->toHaveCount(15);
});

it('has correct backed values for TLS 1.3 ciphers', function (CipherSuite $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [CipherSuite::TLS_AES_128_GCM_SHA256, 'TLS_AES_128_GCM_SHA256'],
    [CipherSuite::TLS_AES_256_GCM_SHA384, 'TLS_AES_256_GCM_SHA384'],
    [CipherSuite::TLS_CHACHA20_POLY1305_SHA256, 'TLS_CHACHA20_POLY1305_SHA256'],
]);

it('has correct backed values for TLS 1.2 ciphers', function (CipherSuite $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [CipherSuite::TLS_ECDHE_ECDSA_WITH_AES_128_GCM_SHA256, 'ECDHE-ECDSA-AES128-GCM-SHA256'],
    [CipherSuite::TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256, 'ECDHE-RSA-AES128-GCM-SHA256'],
    [CipherSuite::TLS_ECDHE_ECDSA_WITH_AES_256_GCM_SHA384, 'ECDHE-ECDSA-AES256-GCM-SHA384'],
    [CipherSuite::TLS_ECDHE_RSA_WITH_AES_256_GCM_SHA384, 'ECDHE-RSA-AES256-GCM-SHA384'],
    [CipherSuite::TLS_ECDHE_ECDSA_WITH_CHACHA20_POLY1305_SHA256, 'ECDHE-ECDSA-CHACHA20-POLY1305'],
    [CipherSuite::TLS_ECDHE_RSA_WITH_CHACHA20_POLY1305_SHA256, 'ECDHE-RSA-CHACHA20-POLY1305'],
    [CipherSuite::TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA, 'ECDHE-RSA-AES128-SHA'],
    [CipherSuite::TLS_ECDHE_RSA_WITH_AES_256_CBC_SHA, 'ECDHE-RSA-AES256-SHA'],
    [CipherSuite::TLS_RSA_WITH_AES_128_GCM_SHA256, 'AES128-GCM-SHA256'],
    [CipherSuite::TLS_RSA_WITH_AES_256_GCM_SHA384, 'AES256-GCM-SHA384'],
    [CipherSuite::TLS_RSA_WITH_AES_128_CBC_SHA, 'AES128-SHA'],
    [CipherSuite::TLS_RSA_WITH_AES_256_CBC_SHA, 'AES256-SHA'],
]);

it('can be created from backed value', function (string $value, CipherSuite $expected): void {
    expect(CipherSuite::from($value))->toBe($expected);
})->with([
    ['TLS_AES_128_GCM_SHA256', CipherSuite::TLS_AES_128_GCM_SHA256],
    ['TLS_AES_256_GCM_SHA384', CipherSuite::TLS_AES_256_GCM_SHA384],
    ['TLS_CHACHA20_POLY1305_SHA256', CipherSuite::TLS_CHACHA20_POLY1305_SHA256],
    ['ECDHE-ECDSA-AES128-GCM-SHA256', CipherSuite::TLS_ECDHE_ECDSA_WITH_AES_128_GCM_SHA256],
    ['ECDHE-RSA-AES128-GCM-SHA256', CipherSuite::TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256],
    ['ECDHE-ECDSA-AES256-GCM-SHA384', CipherSuite::TLS_ECDHE_ECDSA_WITH_AES_256_GCM_SHA384],
    ['ECDHE-RSA-AES256-GCM-SHA384', CipherSuite::TLS_ECDHE_RSA_WITH_AES_256_GCM_SHA384],
    ['ECDHE-ECDSA-CHACHA20-POLY1305', CipherSuite::TLS_ECDHE_ECDSA_WITH_CHACHA20_POLY1305_SHA256],
    ['ECDHE-RSA-CHACHA20-POLY1305', CipherSuite::TLS_ECDHE_RSA_WITH_CHACHA20_POLY1305_SHA256],
    ['ECDHE-RSA-AES128-SHA', CipherSuite::TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA],
    ['ECDHE-RSA-AES256-SHA', CipherSuite::TLS_ECDHE_RSA_WITH_AES_256_CBC_SHA],
    ['AES128-GCM-SHA256', CipherSuite::TLS_RSA_WITH_AES_128_GCM_SHA256],
    ['AES256-GCM-SHA384', CipherSuite::TLS_RSA_WITH_AES_256_GCM_SHA384],
    ['AES128-SHA', CipherSuite::TLS_RSA_WITH_AES_128_CBC_SHA],
    ['AES256-SHA', CipherSuite::TLS_RSA_WITH_AES_256_CBC_SHA],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(CipherSuite::tryFrom('invalid'))->toBeNull();
    expect(CipherSuite::tryFrom(''))->toBeNull();
});
