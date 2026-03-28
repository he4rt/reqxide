<?php

declare(strict_types=1);

use Reqxide\Client;
use Reqxide\Emulation\Browser;
use Reqxide\Transport\CurlTransport;

beforeEach(function (): void {
    if (! extension_loaded('curl')) {
        $this->markTestSkipped('ext-curl is required for fingerprint tests.');
    }

    // Verify network connectivity before running integration tests
    $ch = curl_init('https://tls.peet.ws/api/all');
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    $error = curl_errno($ch);

    if ($result === false || $error !== 0) {
        $this->markTestSkipped('Network access to tls.peet.ws is unavailable.');
    }
});

it('sends a request with Chrome 131 profile', function (): void {
    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->verify(true)
        ->build();

    $response = $client->get('https://tls.peet.ws/api/all')->send();

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode((string) $response->getBody(), true);
    expect($data)->toBeArray();
    expect($data)->toHaveKey('tls');
    // The TLS fingerprint data should contain cipher information
    expect($data['tls'])->toHaveKey('ciphers');
})->group('integration');

it('sends a request with Firefox 136 profile', function (): void {
    $client = Client::builder()
        ->emulation(Browser::Firefox136)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->build();

    $response = $client->get('https://tls.peet.ws/api/all')->send();

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode((string) $response->getBody(), true);
    expect($data)->toBeArray();
    expect($data)->toHaveKey('tls');
})->group('integration');

it('sends a request with Safari 18 profile', function (): void {
    $client = Client::builder()
        ->emulation(Browser::Safari18)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->build();

    $response = $client->get('https://tls.peet.ws/api/all')->send();

    expect($response->getStatusCode())->toBe(200);
})->group('integration');

it('sends a request with Edge 131 profile', function (): void {
    $client = Client::builder()
        ->emulation(Browser::Edge131)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->build();

    $response = $client->get('https://tls.peet.ws/api/all')->send();

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode((string) $response->getBody(), true);
    expect($data)->toBeArray();
    expect($data)->toHaveKey('tls');
})->group('integration');

it('sends a request with OkHttp 5 profile', function (): void {
    $client = Client::builder()
        ->emulation(Browser::OkHttp5)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->build();

    $response = $client->get('https://tls.peet.ws/api/all')->send();

    expect($response->getStatusCode())->toBe(200);
})->group('integration');

it('verifies different browsers produce different User-Agent headers', function (): void {
    $chrome = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->build();
    $firefox = Client::builder()
        ->emulation(Browser::Firefox136)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->build();

    $chromeResponse = $chrome->get('https://httpbin.org/user-agent')->send();
    $firefoxResponse = $firefox->get('https://httpbin.org/user-agent')->send();

    $chromeUA = json_decode((string) $chromeResponse->getBody(), true)['user-agent'] ?? '';
    $firefoxUA = json_decode((string) $firefoxResponse->getBody(), true)['user-agent'] ?? '';

    expect($chromeUA)->toContain('Chrome');
    expect($firefoxUA)->toContain('Firefox');
    expect($chromeUA)->not->toBe($firefoxUA);
})->group('integration');

it('returns valid JSON body from tls.peet.ws', function (): void {
    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->timeout(10)
        ->build();

    $response = $client->get('https://tls.peet.ws/api/all')->send();

    $body = (string) $response->getBody();
    $data = json_decode($body, true);

    expect(json_last_error())->toBe(JSON_ERROR_NONE);
    expect($data)->toBeArray();
    expect($data)->toHaveKey('ip');
    expect($data)->toHaveKey('http_version');
})->group('integration');
