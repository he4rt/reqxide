<?php

declare(strict_types=1);

use Reqxide\Emulation\Browser;
use Reqxide\Emulation\Catalog;
use Reqxide\Emulation\Profile;

it('resolves a Profile for every Browser case', function (Browser $browser): void {
    $profile = Catalog::resolve($browser);

    expect($profile)->toBeInstanceOf(Profile::class);
})->with(Browser::cases());

it('assigns a ConnectionGroup matching the Browser value', function (Browser $browser): void {
    $profile = Catalog::resolve($browser);

    expect($profile->connectionGroup)->not->toBeNull()
        ->and($profile->connectionGroup->identifier)->toBe($browser->value);
})->with(Browser::cases());

it('different browsers get different connection groups', function (): void {
    $chrome = Catalog::resolve(Browser::Chrome145);
    $firefox = Catalog::resolve(Browser::Firefox147);

    expect($chrome->connectionGroup->equals($firefox->connectionGroup))->toBeFalse();
});
