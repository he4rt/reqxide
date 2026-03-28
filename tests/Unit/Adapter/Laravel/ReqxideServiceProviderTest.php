<?php

declare(strict_types=1);

use Reqxide\Adapter\Laravel\ReqxideServiceProvider;
use Reqxide\Client;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Browser;

it('creates a transport instance', function (): void {
    $provider = new ReqxideServiceProvider;

    $transport = $provider->createTransport();

    expect($transport)->toBeInstanceOf(TransportInterface::class);
});

it('creates a client with no browser', function (): void {
    $provider = new ReqxideServiceProvider;

    $client = $provider->createClient();

    expect($client)->toBeInstanceOf(Client::class);
});

it('creates a client with a specific browser', function (): void {
    $provider = new ReqxideServiceProvider;

    $client = $provider->createClient(Browser::Chrome131);

    expect($client)->toBeInstanceOf(Client::class);
});

it('resolves default browser with valid browser string', function (): void {
    $provider = new ReqxideServiceProvider(defaultBrowser: 'chrome_131');

    $browser = $provider->resolveDefaultBrowser();

    expect($browser)->toBe(Browser::Chrome131);
});

it('resolves default browser with null returns null', function (): void {
    $provider = new ReqxideServiceProvider;

    $browser = $provider->resolveDefaultBrowser();

    expect($browser)->toBeNull();
});

it('resolves default browser with invalid string returns null', function (): void {
    $provider = new ReqxideServiceProvider(defaultBrowser: 'nonexistent_browser_999');

    $browser = $provider->resolveDefaultBrowser();

    expect($browser)->toBeNull();
});

it('creates a client using the resolved default browser', function (): void {
    $provider = new ReqxideServiceProvider(defaultBrowser: 'firefox_136');

    $client = $provider->createClient();

    expect($client)->toBeInstanceOf(Client::class);
});

it('returns the library path', function (): void {
    $provider = new ReqxideServiceProvider(libraryPath: '/usr/lib/curl-impersonate.so');

    expect($provider->libraryPath())->toBe('/usr/lib/curl-impersonate.so');
});

it('returns null library path when not set', function (): void {
    $provider = new ReqxideServiceProvider;

    expect($provider->libraryPath())->toBeNull();
});
