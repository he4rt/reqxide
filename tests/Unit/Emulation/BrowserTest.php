<?php

declare(strict_types=1);

use Reqxide\Emulation\Browser;
use Reqxide\Emulation\Profile;

it('has the correct number of cases', function (): void {
    expect(Browser::cases())->toHaveCount(12);
});

it('has correct backed values', function (Browser $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [Browser::Chrome131, 'chrome_131'],
    [Browser::Chrome130, 'chrome_130'],
    [Browser::Chrome129, 'chrome_129'],
    [Browser::Chrome128, 'chrome_128'],
    [Browser::Firefox136, 'firefox_136'],
    [Browser::Firefox135, 'firefox_135'],
    [Browser::Safari18, 'safari_18'],
    [Browser::SafariIPad18, 'safari_ipad_18'],
    [Browser::SafariIOS18, 'safari_ios_18'],
    [Browser::Edge131, 'edge_131'],
    [Browser::OkHttp5, 'okhttp_5'],
    [Browser::OkHttp4, 'okhttp_4'],
]);

it('can be created from backed value', function (string $value, Browser $expected): void {
    expect(Browser::from($value))->toBe($expected);
})->with([
    ['chrome_131', Browser::Chrome131],
    ['chrome_130', Browser::Chrome130],
    ['chrome_129', Browser::Chrome129],
    ['chrome_128', Browser::Chrome128],
    ['firefox_136', Browser::Firefox136],
    ['firefox_135', Browser::Firefox135],
    ['safari_18', Browser::Safari18],
    ['safari_ipad_18', Browser::SafariIPad18],
    ['safari_ios_18', Browser::SafariIOS18],
    ['edge_131', Browser::Edge131],
    ['okhttp_5', Browser::OkHttp5],
    ['okhttp_4', Browser::OkHttp4],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(Browser::tryFrom('invalid'))->toBeNull();
    expect(Browser::tryFrom(''))->toBeNull();
});

it('returns a Profile from profile()', function (Browser $browser): void {
    expect($browser->profile())->toBeInstanceOf(Profile::class);
})->with([
    [Browser::Chrome131],
    [Browser::Chrome130],
    [Browser::Chrome129],
    [Browser::Chrome128],
    [Browser::Firefox136],
    [Browser::Firefox135],
    [Browser::Safari18],
    [Browser::SafariIPad18],
    [Browser::SafariIOS18],
    [Browser::Edge131],
    [Browser::OkHttp5],
    [Browser::OkHttp4],
]);
