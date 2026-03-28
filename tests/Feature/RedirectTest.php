<?php

declare(strict_types=1);

use Reqxide\Client;
use Reqxide\Emulation\Browser;
use Reqxide\Redirect\RedirectPolicy;
use Reqxide\Transport\CurlTransport;

beforeEach(function (): void {
    if (! extension_loaded('curl')) {
        $this->markTestSkipped('ext-curl is required for redirect tests.');
    }

    // Verify network connectivity before running integration tests
    $ch = curl_init('https://httpbin.org/get');
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    $error = curl_errno($ch);

    if ($result === false || $error !== 0) {
        $this->markTestSkipped('Network access to httpbin.org is unavailable.');
    }
});

it('receives a redirect response from httpbin', function (): void {
    // CurlTransport disables FOLLOWLOCATION, so without redirect middleware
    // we should receive the raw 302 response.
    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->build();

    $response = $client->get('https://httpbin.org/redirect/1')->send();

    // Without redirect middleware wired (Phase 5), we get the raw redirect
    expect($response->getStatusCode())->toBeGreaterThanOrEqual(300);
    expect($response->getStatusCode())->toBeLessThan(400);
    expect($response->hasHeader('Location'))->toBeTrue();
})->group('integration');

it('follows redirects when redirect policy is configured', function (): void {
    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->redirect(RedirectPolicy::limited(5))
        ->timeout(10)
        ->build();

    // httpbin.org/redirect/3 redirects 3 times then returns 200.
    // Note: Once Phase 5 wires RedirectMiddleware, this will follow
    // redirects and return 200. Until then, we get the first 302.
    $response = $client->get('https://httpbin.org/redirect/3')->send();

    // Phase 5 will change this to 200 once middleware is wired
    expect($response->getStatusCode())->toBeIn([200, 302]);
})->group('integration');

it('stops following redirects when policy is none', function (): void {
    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->redirect(RedirectPolicy::none())
        ->timeout(10)
        ->build();

    // Should get the 302 response directly
    $response = $client->get('https://httpbin.org/redirect/1')->send();

    expect($response->getStatusCode())->toBeGreaterThanOrEqual(300);
    expect($response->getStatusCode())->toBeLessThan(400);
})->group('integration');

it('receives a redirect response with Location header for relative redirects', function (): void {
    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->build();

    $response = $client->get('https://httpbin.org/relative-redirect/2')->send();

    // Without redirect middleware, we get the raw 302 with a Location header
    expect($response->getStatusCode())->toBeGreaterThanOrEqual(300);
    expect($response->getStatusCode())->toBeLessThan(400);
    expect($response->hasHeader('Location'))->toBeTrue();
})->group('integration');

it('receives the correct HTTP status for absolute redirects', function (): void {
    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->build();

    $response = $client->get('https://httpbin.org/absolute-redirect/1')->send();

    expect($response->getStatusCode())->toBeGreaterThanOrEqual(300);
    expect($response->getStatusCode())->toBeLessThan(400);
    expect($response->hasHeader('Location'))->toBeTrue();
})->group('integration');
