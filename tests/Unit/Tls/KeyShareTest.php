<?php

declare(strict_types=1);

use Reqxide\Tls\KeyShare;

it('has the correct number of cases', function (): void {
    expect(KeyShare::cases())->toHaveCount(6);
});

it('has correct backed values', function (KeyShare $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [KeyShare::X25519Kyber768Draft00, 'X25519Kyber768Draft00'],
    [KeyShare::X25519MLKEM768, 'X25519MLKEM768'],
    [KeyShare::X25519, 'X25519'],
    [KeyShare::P256, 'P-256'],
    [KeyShare::P384, 'P-384'],
    [KeyShare::P521, 'P-521'],
]);

it('can be created from backed value', function (string $value, KeyShare $expected): void {
    expect(KeyShare::from($value))->toBe($expected);
})->with([
    ['X25519Kyber768Draft00', KeyShare::X25519Kyber768Draft00],
    ['X25519MLKEM768', KeyShare::X25519MLKEM768],
    ['X25519', KeyShare::X25519],
    ['P-256', KeyShare::P256],
    ['P-384', KeyShare::P384],
    ['P-521', KeyShare::P521],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(KeyShare::tryFrom('invalid'))->toBeNull();
    expect(KeyShare::tryFrom(''))->toBeNull();
});
