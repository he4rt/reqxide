<?php

declare(strict_types=1);

use Reqxide\Tls\TlsVersion;

it('has the correct number of cases', function (): void {
    expect(TlsVersion::cases())->toHaveCount(4);
});

it('has correct backed values', function (TlsVersion $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [TlsVersion::TLS_1_0, '1.0'],
    [TlsVersion::TLS_1_1, '1.1'],
    [TlsVersion::TLS_1_2, '1.2'],
    [TlsVersion::TLS_1_3, '1.3'],
]);

it('can be created from backed value', function (string $value, TlsVersion $expected): void {
    expect(TlsVersion::from($value))->toBe($expected);
})->with([
    ['1.0', TlsVersion::TLS_1_0],
    ['1.1', TlsVersion::TLS_1_1],
    ['1.2', TlsVersion::TLS_1_2],
    ['1.3', TlsVersion::TLS_1_3],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(TlsVersion::tryFrom('invalid'))->toBeNull();
    expect(TlsVersion::tryFrom('2.0'))->toBeNull();
    expect(TlsVersion::tryFrom(''))->toBeNull();
});
