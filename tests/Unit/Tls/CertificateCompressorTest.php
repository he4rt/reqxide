<?php

declare(strict_types=1);

use Reqxide\Tls\CertificateCompressor;

it('has the correct number of cases', function (): void {
    expect(CertificateCompressor::cases())->toHaveCount(3);
});

it('has correct backed values', function (CertificateCompressor $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [CertificateCompressor::Brotli, 'brotli'],
    [CertificateCompressor::Zlib, 'zlib'],
    [CertificateCompressor::Zstd, 'zstd'],
]);

it('can be created from backed value', function (string $value, CertificateCompressor $expected): void {
    expect(CertificateCompressor::from($value))->toBe($expected);
})->with([
    ['brotli', CertificateCompressor::Brotli],
    ['zlib', CertificateCompressor::Zlib],
    ['zstd', CertificateCompressor::Zstd],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(CertificateCompressor::tryFrom('invalid'))->toBeNull();
    expect(CertificateCompressor::tryFrom(''))->toBeNull();
});
