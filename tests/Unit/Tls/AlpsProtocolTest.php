<?php

declare(strict_types=1);

use Reqxide\Tls\AlpsProtocol;

it('has the correct number of cases', function (): void {
    expect(AlpsProtocol::cases())->toHaveCount(3);
});

it('has correct backed values', function (AlpsProtocol $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [AlpsProtocol::Http1, 'http/1.1'],
    [AlpsProtocol::Http2, 'h2'],
    [AlpsProtocol::Http3, 'h3'],
]);

it('can be created from backed value', function (string $value, AlpsProtocol $expected): void {
    expect(AlpsProtocol::from($value))->toBe($expected);
})->with([
    ['http/1.1', AlpsProtocol::Http1],
    ['h2', AlpsProtocol::Http2],
    ['h3', AlpsProtocol::Http3],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(AlpsProtocol::tryFrom('invalid'))->toBeNull();
    expect(AlpsProtocol::tryFrom(''))->toBeNull();
});
