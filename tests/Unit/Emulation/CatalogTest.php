<?php

declare(strict_types=1);

use Reqxide\Emulation\Browser;
use Reqxide\Emulation\Catalog;
use Reqxide\Emulation\Profile;

it('resolves a Profile for every Browser case', function (Browser $browser): void {
    $profile = Catalog::resolve($browser);

    expect($profile)->toBeInstanceOf(Profile::class);
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
