<?php

declare(strict_types=1);

use Reqxide\Adapter\Laravel\Facade;
use Reqxide\Client;
use Reqxide\Emulation\Browser;

it('returns a Client instance', function (): void {
    $facade = new Facade(Browser::Chrome131);

    $client = $facade->client();

    expect($client)->toBeInstanceOf(Client::class);
});

it('returns the same Client instance on subsequent calls', function (): void {
    $facade = new Facade(Browser::Chrome131);

    $first = $facade->client();
    $second = $facade->client();

    expect($first)->toBe($second);
});

it('creates client with the specified browser', function (): void {
    $facade = new Facade(Browser::Firefox136);

    $client = $facade->client();

    expect($client)->toBeInstanceOf(Client::class);
});
