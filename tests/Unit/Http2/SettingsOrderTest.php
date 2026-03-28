<?php

declare(strict_types=1);

use Reqxide\Http2\SettingId;
use Reqxide\Http2\SettingsOrder;

it('can be constructed with a list of setting ids', function (): void {
    $settings = [SettingId::HeaderTableSize, SettingId::EnablePush, SettingId::MaxFrameSize];
    $order = new SettingsOrder($settings);

    expect($order->settings)->toBe($settings);
});

it('can be constructed with an empty list', function (): void {
    $order = new SettingsOrder([]);

    expect($order->settings)->toBe([]);
});

it('can be constructed with all setting ids', function (): void {
    $settings = SettingId::cases();
    $order = new SettingsOrder($settings);

    expect($order->settings)->toHaveCount(8);
});
