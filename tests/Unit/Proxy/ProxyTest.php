<?php

declare(strict_types=1);

use Reqxide\Proxy\Proxy;
use Reqxide\Proxy\ProxyScheme;

it('creates an HTTP proxy from address with port', function (): void {
    $proxy = Proxy::http('127.0.0.1:8080');

    expect($proxy->scheme)->toBe(ProxyScheme::Http)
        ->and($proxy->host)->toBe('127.0.0.1')
        ->and($proxy->port)->toBe(8080)
        ->and($proxy->username)->toBeNull()
        ->and($proxy->password)->toBeNull()
        ->and($proxy->noProxy)->toBe([]);
});

it('creates an HTTPS proxy from address with port', function (): void {
    $proxy = Proxy::https('proxy.example.com:443');

    expect($proxy->scheme)->toBe(ProxyScheme::Https)
        ->and($proxy->host)->toBe('proxy.example.com')
        ->and($proxy->port)->toBe(443);
});

it('creates a SOCKS5 proxy from address with port', function (): void {
    $proxy = Proxy::socks5('socks.example.com:1080');

    expect($proxy->scheme)->toBe(ProxyScheme::Socks5)
        ->and($proxy->host)->toBe('socks.example.com')
        ->and($proxy->port)->toBe(1080);
});

it('creates a SOCKS4 proxy from address with port', function (): void {
    $proxy = Proxy::socks4('socks4.example.com:1080');

    expect($proxy->scheme)->toBe(ProxyScheme::Socks4)
        ->and($proxy->host)->toBe('socks4.example.com')
        ->and($proxy->port)->toBe(1080);
});

it('parses authentication from SOCKS5 address', function (): void {
    $proxy = Proxy::socks5('//user:pass@proxy.com:1080');

    expect($proxy->scheme)->toBe(ProxyScheme::Socks5)
        ->and($proxy->host)->toBe('proxy.com')
        ->and($proxy->port)->toBe(1080)
        ->and($proxy->username)->toBe('user')
        ->and($proxy->password)->toBe('pass');
});

it('parses authentication with username only', function (): void {
    $proxy = Proxy::http('admin@proxy.com:3128');

    expect($proxy->host)->toBe('proxy.com')
        ->and($proxy->port)->toBe(3128)
        ->and($proxy->username)->toBe('admin')
        ->and($proxy->password)->toBeNull();
});

it('uses default port for HTTP when not specified', function (): void {
    $proxy = Proxy::http('proxy.example.com');

    expect($proxy->host)->toBe('proxy.example.com')
        ->and($proxy->port)->toBe(80);
});

it('uses default port for HTTPS when not specified', function (): void {
    $proxy = Proxy::https('proxy.example.com');

    expect($proxy->host)->toBe('proxy.example.com')
        ->and($proxy->port)->toBe(443);
});

it('uses default port for SOCKS5 when not specified', function (): void {
    $proxy = Proxy::socks5('proxy.example.com');

    expect($proxy->host)->toBe('proxy.example.com')
        ->and($proxy->port)->toBe(1080);
});

it('uses default port for SOCKS4 when not specified', function (): void {
    $proxy = Proxy::socks4('proxy.example.com');

    expect($proxy->host)->toBe('proxy.example.com')
        ->and($proxy->port)->toBe(1080);
});

it('builds correct URL string from toUrl()', function (): void {
    $proxy = Proxy::http('127.0.0.1:8080');

    expect($proxy->toUrl())->toBe('http://127.0.0.1:8080');
});

it('builds correct URL string with authentication', function (): void {
    $proxy = Proxy::socks5('//user:pass@proxy.com:1080');

    expect($proxy->toUrl())->toBe('socks5://user:pass@proxy.com:1080');
});

it('builds correct URL string with username only', function (): void {
    $proxy = Proxy::http('admin@proxy.com:3128');

    expect($proxy->toUrl())->toBe('http://admin@proxy.com:3128');
});

it('URL-encodes special characters in credentials', function (): void {
    $proxy = new Proxy(
        scheme: ProxyScheme::Http,
        host: 'proxy.com',
        port: 8080,
        username: 'user@domain',
        password: 'p@ss:word',
    );

    expect($proxy->toUrl())->toBe('http://user%40domain:p%40ss%3Aword@proxy.com:8080');
});

it('bypasses for exact hostname match in noProxy', function (): void {
    $proxy = new Proxy(
        scheme: ProxyScheme::Http,
        host: '127.0.0.1',
        port: 8080,
        noProxy: ['localhost'],
    );

    expect($proxy->shouldBypass('localhost'))->toBeTrue();
});

it('bypasses for domain suffix match in noProxy', function (): void {
    $proxy = new Proxy(
        scheme: ProxyScheme::Http,
        host: '127.0.0.1',
        port: 8080,
        noProxy: ['.example.com'],
    );

    expect($proxy->shouldBypass('api.example.com'))->toBeTrue();
});

it('does not bypass for non-matching domain in noProxy', function (): void {
    $proxy = new Proxy(
        scheme: ProxyScheme::Http,
        host: '127.0.0.1',
        port: 8080,
        noProxy: ['.example.com'],
    );

    expect($proxy->shouldBypass('other.com'))->toBeFalse();
});

it('bypasses for wildcard in noProxy', function (): void {
    $proxy = new Proxy(
        scheme: ProxyScheme::Http,
        host: '127.0.0.1',
        port: 8080,
        noProxy: ['*'],
    );

    expect($proxy->shouldBypass('any.host'))->toBeTrue();
});

it('does not bypass when noProxy is empty', function (): void {
    $proxy = Proxy::http('127.0.0.1:8080');

    expect($proxy->shouldBypass('anything.com'))->toBeFalse();
});

it('bypasses case-insensitively for exact match', function (): void {
    $proxy = new Proxy(
        scheme: ProxyScheme::Http,
        host: '127.0.0.1',
        port: 8080,
        noProxy: ['LocalHost'],
    );

    expect($proxy->shouldBypass('localhost'))->toBeTrue();
});

it('bypasses case-insensitively for domain suffix match', function (): void {
    $proxy = new Proxy(
        scheme: ProxyScheme::Http,
        host: '127.0.0.1',
        port: 8080,
        noProxy: ['.Example.COM'],
    );

    expect($proxy->shouldBypass('API.Example.com'))->toBeTrue();
});

it('can be constructed directly with all properties', function (): void {
    $proxy = new Proxy(
        scheme: ProxyScheme::Https,
        host: 'secure.proxy.com',
        port: 443,
        username: 'admin',
        password: 'secret',
        noProxy: ['localhost', '.internal.com'],
    );

    expect($proxy->scheme)->toBe(ProxyScheme::Https)
        ->and($proxy->host)->toBe('secure.proxy.com')
        ->and($proxy->port)->toBe(443)
        ->and($proxy->username)->toBe('admin')
        ->and($proxy->password)->toBe('secret')
        ->and($proxy->noProxy)->toBe(['localhost', '.internal.com']);
});
