<?php

declare(strict_types=1);

use Reqxide\Emulation\Browser;
use Reqxide\Emulation\Profile;

it('has the correct number of cases', function (): void {
    expect(Browser::cases())->toHaveCount(44);
});

it('has correct backed values', function (Browser $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [Browser::Chrome99, 'chrome_99'],
    [Browser::Chrome100, 'chrome_100'],
    [Browser::Chrome101, 'chrome_101'],
    [Browser::Chrome104, 'chrome_104'],
    [Browser::Chrome107, 'chrome_107'],
    [Browser::Chrome110, 'chrome_110'],
    [Browser::Chrome116, 'chrome_116'],
    [Browser::Chrome119, 'chrome_119'],
    [Browser::Chrome120, 'chrome_120'],
    [Browser::Chrome123, 'chrome_123'],
    [Browser::Chrome124, 'chrome_124'],
    [Browser::Chrome128, 'chrome_128'],
    [Browser::Chrome129, 'chrome_129'],
    [Browser::Chrome130, 'chrome_130'],
    [Browser::Chrome131, 'chrome_131'],
    [Browser::Chrome133a, 'chrome_133a'],
    [Browser::Chrome136, 'chrome_136'],
    [Browser::Chrome142, 'chrome_142'],
    [Browser::Chrome145, 'chrome_145'],
    [Browser::Chrome146, 'chrome_146'],
    [Browser::Chrome99Android, 'chrome_99_android'],
    [Browser::Chrome131Android, 'chrome_131_android'],
    [Browser::Firefox133, 'firefox_133'],
    [Browser::Firefox135, 'firefox_135'],
    [Browser::Firefox136, 'firefox_136'],
    [Browser::Firefox144, 'firefox_144'],
    [Browser::Firefox147, 'firefox_147'],
    [Browser::Safari153, 'safari_15_3'],
    [Browser::Safari155, 'safari_15_5'],
    [Browser::Safari170, 'safari_17_0'],
    [Browser::Safari172iOS, 'safari_17_2_ios'],
    [Browser::Safari18, 'safari_18'],
    [Browser::SafariIPad18, 'safari_ipad_18'],
    [Browser::SafariIOS18, 'safari_ios_18'],
    [Browser::Safari184, 'safari_18_4'],
    [Browser::Safari184iOS, 'safari_18_4_ios'],
    [Browser::Safari260, 'safari_26_0'],
    [Browser::Safari260iOS, 'safari_26_0_ios'],
    [Browser::Edge99, 'edge_99'],
    [Browser::Edge101, 'edge_101'],
    [Browser::Edge131, 'edge_131'],
    [Browser::OkHttp5, 'okhttp_5'],
    [Browser::OkHttp4, 'okhttp_4'],
    [Browser::Tor145, 'tor_14_5'],
]);

it('can be created from backed value', function (string $value, Browser $expected): void {
    expect(Browser::from($value))->toBe($expected);
})->with([
    ['chrome_99', Browser::Chrome99],
    ['chrome_131', Browser::Chrome131],
    ['chrome_99_android', Browser::Chrome99Android],
    ['firefox_133', Browser::Firefox133],
    ['firefox_135', Browser::Firefox135],
    ['safari_15_3', Browser::Safari153],
    ['safari_18', Browser::Safari18],
    ['safari_26_0', Browser::Safari260],
    ['edge_99', Browser::Edge99],
    ['edge_131', Browser::Edge131],
    ['okhttp_5', Browser::OkHttp5],
    ['tor_14_5', Browser::Tor145],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(Browser::tryFrom('invalid'))->toBeNull();
    expect(Browser::tryFrom(''))->toBeNull();
});

it('returns a Profile from profile()', function (Browser $browser): void {
    expect($browser->profile())->toBeInstanceOf(Profile::class);
})->with(Browser::cases());
