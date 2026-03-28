<?php

declare(strict_types=1);

use Reqxide\Http2\PseudoHeader;

it('has the correct number of cases', function (): void {
    expect(PseudoHeader::cases())->toHaveCount(4);
});

it('has correct backed values', function (PseudoHeader $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [PseudoHeader::Method, ':method'],
    [PseudoHeader::Path, ':path'],
    [PseudoHeader::Authority, ':authority'],
    [PseudoHeader::Scheme, ':scheme'],
]);

it('can be created from backed value', function (string $value, PseudoHeader $expected): void {
    expect(PseudoHeader::from($value))->toBe($expected);
})->with([
    [':method', PseudoHeader::Method],
    [':path', PseudoHeader::Path],
    [':authority', PseudoHeader::Authority],
    [':scheme', PseudoHeader::Scheme],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(PseudoHeader::tryFrom('invalid'))->toBeNull();
    expect(PseudoHeader::tryFrom(''))->toBeNull();
});
