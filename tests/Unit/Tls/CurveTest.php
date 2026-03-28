<?php

declare(strict_types=1);

use Reqxide\Tls\Curve;

it('has the correct number of cases', function (): void {
    expect(Curve::cases())->toHaveCount(7);
});

it('has correct backed values', function (Curve $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [Curve::X25519, 'X25519'],
    [Curve::X25519MLKEM768, 'X25519MLKEM768'],
    [Curve::P256, 'P-256'],
    [Curve::P384, 'P-384'],
    [Curve::P521, 'P-521'],
    [Curve::FFDHE2048, 'ffdhe2048'],
    [Curve::FFDHE3072, 'ffdhe3072'],
]);

it('can be created from backed value', function (string $value, Curve $expected): void {
    expect(Curve::from($value))->toBe($expected);
})->with([
    ['X25519', Curve::X25519],
    ['X25519MLKEM768', Curve::X25519MLKEM768],
    ['P-256', Curve::P256],
    ['P-384', Curve::P384],
    ['P-521', Curve::P521],
    ['ffdhe2048', Curve::FFDHE2048],
    ['ffdhe3072', Curve::FFDHE3072],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(Curve::tryFrom('invalid'))->toBeNull();
    expect(Curve::tryFrom(''))->toBeNull();
});
