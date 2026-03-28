<?php

declare(strict_types=1);

use Reqxide\Http2\SettingId;

it('has the correct number of cases', function (): void {
    expect(SettingId::cases())->toHaveCount(8);
});

it('has correct backed values', function (SettingId $case, int $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [SettingId::HeaderTableSize, 0x1],
    [SettingId::EnablePush, 0x2],
    [SettingId::MaxConcurrentStreams, 0x3],
    [SettingId::InitialWindowSize, 0x4],
    [SettingId::MaxFrameSize, 0x5],
    [SettingId::MaxHeaderListSize, 0x6],
    [SettingId::EnableConnectProtocol, 0x8],
    [SettingId::NoRfc7540Priorities, 0x9],
]);

it('can be created from backed value', function (int $value, SettingId $expected): void {
    expect(SettingId::from($value))->toBe($expected);
})->with([
    [0x1, SettingId::HeaderTableSize],
    [0x2, SettingId::EnablePush],
    [0x3, SettingId::MaxConcurrentStreams],
    [0x4, SettingId::InitialWindowSize],
    [0x5, SettingId::MaxFrameSize],
    [0x6, SettingId::MaxHeaderListSize],
    [0x8, SettingId::EnableConnectProtocol],
    [0x9, SettingId::NoRfc7540Priorities],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(SettingId::tryFrom(0x7))->toBeNull();
    expect(SettingId::tryFrom(0))->toBeNull();
    expect(SettingId::tryFrom(999))->toBeNull();
});
