<?php

declare(strict_types=1);

use Reqxide\Client;
use Reqxide\Emulation\Browser;
use Reqxide\Proxy\Proxy;
use Reqxide\Transport\CurlTransport;

it('connects through a SOCKS5 proxy', function (): void {
    if (! extension_loaded('curl')) {
        $this->markTestSkipped('ext-curl is required for proxy tests.');
    }

    $proxyHost = getenv('REQXIDE_TEST_SOCKS5_PROXY');

    if ($proxyHost === false || $proxyHost === '') {
        $this->markTestSkipped('Set REQXIDE_TEST_SOCKS5_PROXY env var to run proxy tests (e.g., 127.0.0.1:1080).');
    }

    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->proxy(Proxy::socks5($proxyHost))
        ->timeout(15)
        ->build();

    $response = $client->get('https://httpbin.org/ip')->send();

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode((string) $response->getBody(), true);
    expect($data)->toHaveKey('origin');
})->group('integration', 'proxy');

it('connects through an HTTP proxy', function (): void {
    if (! extension_loaded('curl')) {
        $this->markTestSkipped('ext-curl is required for proxy tests.');
    }

    $proxyHost = getenv('REQXIDE_TEST_HTTP_PROXY');

    if ($proxyHost === false || $proxyHost === '') {
        $this->markTestSkipped('Set REQXIDE_TEST_HTTP_PROXY env var to run proxy tests (e.g., 127.0.0.1:8080).');
    }

    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->proxy(Proxy::http($proxyHost))
        ->timeout(15)
        ->build();

    $response = $client->get('https://httpbin.org/ip')->send();

    expect($response->getStatusCode())->toBe(200);
})->group('integration', 'proxy');

it('connects through an HTTPS proxy', function (): void {
    if (! extension_loaded('curl')) {
        $this->markTestSkipped('ext-curl is required for proxy tests.');
    }

    $proxyHost = getenv('REQXIDE_TEST_HTTPS_PROXY');

    if ($proxyHost === false || $proxyHost === '') {
        $this->markTestSkipped('Set REQXIDE_TEST_HTTPS_PROXY env var to run proxy tests (e.g., 127.0.0.1:8443).');
    }

    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->proxy(Proxy::https($proxyHost))
        ->timeout(15)
        ->build();

    $response = $client->get('https://httpbin.org/ip')->send();

    expect($response->getStatusCode())->toBe(200);
})->group('integration', 'proxy');

it('connects through a SOCKS5H proxy (DNS resolved by proxy)', function (): void {
    if (! extension_loaded('curl')) {
        $this->markTestSkipped('ext-curl is required for proxy tests.');
    }

    $proxyHost = getenv('REQXIDE_TEST_SOCKS5H_PROXY');

    if ($proxyHost === false || $proxyHost === '') {
        $this->markTestSkipped('Set REQXIDE_TEST_SOCKS5H_PROXY env var to run proxy tests (e.g., user:pass@proxy.example.com:7777).');
    }

    $client = Client::builder()
        ->emulation(Browser::Chrome145)
        ->transport(new CurlTransport)
        ->proxy(Proxy::socks5h($proxyHost))
        ->timeout(15)
        ->build();

    $response = $client->get('https://httpbin.org/ip')->send();

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode((string) $response->getBody(), true);
    expect($data)->toHaveKey('origin');
})->group('integration', 'proxy');

it('connects through a SOCKS4 proxy', function (): void {
    if (! extension_loaded('curl')) {
        $this->markTestSkipped('ext-curl is required for proxy tests.');
    }

    $proxyHost = getenv('REQXIDE_TEST_SOCKS4_PROXY');

    if ($proxyHost === false || $proxyHost === '') {
        $this->markTestSkipped('Set REQXIDE_TEST_SOCKS4_PROXY env var to run proxy tests (e.g., 127.0.0.1:1080).');
    }

    $client = Client::builder()
        ->emulation(Browser::Chrome131)
        ->transport(new CurlTransport)
        ->proxy(Proxy::socks4($proxyHost))
        ->timeout(15)
        ->build();

    $response = $client->get('https://httpbin.org/ip')->send();

    expect($response->getStatusCode())->toBe(200);
})->group('integration', 'proxy');
