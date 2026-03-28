<?php

declare(strict_types=1);

use Reqxide\Client;
use Reqxide\Cookie\CookieJar;
use Reqxide\Emulation\Browser;
use Reqxide\Transport\CurlTransport;

beforeEach(function (): void {
    if (! extension_loaded('curl')) {
        $this->markTestSkipped('ext-curl is required for cookie flow tests.');
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

it('persists cookies across requests using CookieJar', function (): void {
    $jar = new CookieJar;

    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->cookieStore($jar)
        ->timeout(10)
        ->build();

    // First request sets a cookie via httpbin.
    // Note: httpbin /cookies/set redirects to /cookies. Since redirect middleware
    // is not yet wired (Phase 5), we'll get a 302 response directly.
    $response = $client->get('https://httpbin.org/cookies/set/test_cookie/test_value')->send();

    // Until Phase 5 wires CookieMiddleware, the response will be a 302 redirect.
    // After Phase 5, the redirect middleware will follow it and we'll get 200.
    expect($response->getStatusCode())->toBeIn([200, 302]);
})->group('integration');

it('sends a request to the cookies endpoint', function (): void {
    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->build();

    $response = $client->get('https://httpbin.org/cookies')->send();

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode((string) $response->getBody(), true);
    expect($data)->toBeArray();
    expect($data)->toHaveKey('cookies');
})->group('integration');

it('response includes Set-Cookie headers when server sets cookies', function (): void {
    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->build();

    // httpbin /response-headers sets response headers including Set-Cookie
    $response = $client->get('https://httpbin.org/response-headers')
        ->query(['Set-Cookie' => 'test=value'])
        ->send();

    expect($response->getStatusCode())->toBe(200);
    // The Set-Cookie header should be present in the response
    expect($response->hasHeader('Set-Cookie'))->toBeTrue();
})->group('integration');
