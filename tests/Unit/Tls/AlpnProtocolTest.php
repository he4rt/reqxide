<?php

declare(strict_types=1);

use Reqxide\Tls\AlpnProtocol;

it('has the correct number of cases', function (): void {
    expect(AlpnProtocol::cases())->toHaveCount(3);
});

it('has correct backed values', function (AlpnProtocol $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [AlpnProtocol::Http1, 'http/1.1'],
    [AlpnProtocol::Http2, 'h2'],
    [AlpnProtocol::Http3, 'h3'],
]);

it('can be created from backed value', function (string $value, AlpnProtocol $expected): void {
    expect(AlpnProtocol::from($value))->toBe($expected);
})->with([
    ['http/1.1', AlpnProtocol::Http1],
    ['h2', AlpnProtocol::Http2],
    ['h3', AlpnProtocol::Http3],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(AlpnProtocol::tryFrom('invalid'))->toBeNull();
    expect(AlpnProtocol::tryFrom(''))->toBeNull();
});
