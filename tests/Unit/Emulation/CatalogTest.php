<?php

declare(strict_types=1);

use Reqxide\Emulation\Browser;
use Reqxide\Emulation\Catalog;
use Reqxide\Emulation\Profile;

it('resolves a Profile for every Browser case', function (Browser $browser): void {
    $profile = Catalog::resolve($browser);

    expect($profile)->toBeInstanceOf(Profile::class);
})->with(Browser::cases());
