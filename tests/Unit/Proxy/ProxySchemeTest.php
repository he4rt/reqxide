<?php

declare(strict_types=1);

use Reqxide\Proxy\ProxyScheme;

it('has the correct number of cases', function (): void {
    expect(ProxyScheme::cases())->toHaveCount(4);
});

it('has correct backed values', function (ProxyScheme $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [ProxyScheme::Http, 'http'],
    [ProxyScheme::Https, 'https'],
    [ProxyScheme::Socks4, 'socks4'],
    [ProxyScheme::Socks5, 'socks5'],
]);

it('can be created from backed value', function (string $value, ProxyScheme $expected): void {
    expect(ProxyScheme::from($value))->toBe($expected);
})->with([
    ['http', ProxyScheme::Http],
    ['https', ProxyScheme::Https],
    ['socks4', ProxyScheme::Socks4],
    ['socks5', ProxyScheme::Socks5],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(ProxyScheme::tryFrom('invalid'))->toBeNull();
    expect(ProxyScheme::tryFrom(''))->toBeNull();
});
