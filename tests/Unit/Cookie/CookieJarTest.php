<?php

declare(strict_types=1);

use Nyholm\Psr7\Uri;
use Reqxide\Contract\CookieStoreInterface;
use Reqxide\Cookie\Cookie;
use Reqxide\Cookie\CookieJar;

it('implements CookieStoreInterface', function (): void {
    $jar = new CookieJar;

    expect($jar)->toBeInstanceOf(CookieStoreInterface::class);
});

it('stores and retrieves cookies via setCookies and getCookies', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/path');

    $jar->setCookies(['session=abc; Path=/'], $uri);

    $cookies = $jar->getCookies($uri);

    expect($cookies)->toBe(['session=abc']);
});

it('stores multiple cookies', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['a=1; Path=/', 'b=2; Path=/'], $uri);

    $cookies = $jar->getCookies($uri);

    expect($cookies)->toContain('a=1')
        ->and($cookies)->toContain('b=2')
        ->and($cookies)->toHaveCount(2);
});

it('matches cookies by exact domain', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['foo=bar; Domain=example.com; Path=/'], $uri);

    $cookies = $jar->getCookies(new Uri('https://example.com/'));

    expect($cookies)->toBe(['foo=bar']);
});

it('matches cookies for subdomains', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://api.example.com/');

    $jar->setCookies(['foo=bar; Domain=example.com; Path=/'], $uri);

    $cookies = $jar->getCookies(new Uri('https://api.example.com/'));

    expect($cookies)->toBe(['foo=bar']);
});

it('rejects cookies with mismatched domain', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['foo=bar; Domain=other.com; Path=/'], $uri);

    $cookies = $jar->getCookies(new Uri('https://example.com/'));

    expect($cookies)->toBe([]);
});

it('matches cookies by exact path', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/app');

    $jar->setCookies(['foo=bar; Path=/app'], $uri);

    $cookies = $jar->getCookies(new Uri('https://example.com/app'));

    expect($cookies)->toBe(['foo=bar']);
});

it('matches cookies by path prefix', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/app');

    $jar->setCookies(['foo=bar; Path=/app'], $uri);

    $cookies = $jar->getCookies(new Uri('https://example.com/app/sub'));

    expect($cookies)->toBe(['foo=bar']);
});

it('does not match cookies when path does not match', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/app');

    $jar->setCookies(['foo=bar; Path=/app'], $uri);

    $cookies = $jar->getCookies(new Uri('https://example.com/other'));

    expect($cookies)->toBe([]);
});

it('filters secure cookies on http scheme', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['secret=val; Path=/; Secure'], $uri);

    $httpCookies = $jar->getCookies(new Uri('http://example.com/'));
    $httpsCookies = $jar->getCookies(new Uri('https://example.com/'));

    expect($httpCookies)->toBe([])
        ->and($httpsCookies)->toBe(['secret=val']);
});

it('filters expired cookies by expires datetime', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    // Cookie with a future expires
    $jar->add(
        new Cookie(name: 'fresh', value: 'yes', path: '/', expires: new DateTimeImmutable('2099-12-31 23:59:59')),
        $uri,
    );

    $cookies = $jar->getCookies($uri);

    expect($cookies)->toBe(['fresh=yes']);
});

it('clears all cookies', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['a=1; Path=/', 'b=2; Path=/'], $uri);

    expect($jar->getCookies($uri))->toHaveCount(2);

    $jar->clear();

    expect($jar->getCookies($uri))->toBe([]);
});

it('adds a cookie directly and retrieves it by name', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');
    $cookie = new Cookie(name: 'token', value: 'xyz', path: '/');

    $jar->add($cookie, $uri);

    $retrieved = $jar->get('token', $uri);

    expect($retrieved)->not->toBeNull()
        ->and($retrieved->name)->toBe('token')
        ->and($retrieved->value)->toBe('xyz');
});

it('returns null when getting a non-existent cookie by name', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    expect($jar->get('missing', $uri))->toBeNull();
});

it('removes a cookie by name', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->add(new Cookie(name: 'foo', value: 'bar', path: '/'), $uri);

    expect($jar->get('foo', $uri))->not->toBeNull();

    $jar->remove('foo', $uri);

    expect($jar->get('foo', $uri))->toBeNull();
});

it('handles remove when cookie does not exist', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->remove('nonexistent', $uri);

    expect($jar->getCookies($uri))->toBe([]);
});

it('normalizes domain by stripping leading dot', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['foo=bar; Domain=.example.com; Path=/'], $uri);

    $cookies = $jar->getCookies(new Uri('https://example.com/'));

    expect($cookies)->toBe(['foo=bar']);
});

it('normalizes domain by stripping port', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com:8080/');

    $jar->setCookies(['foo=bar; Path=/'], $uri);

    $cookies = $jar->getCookies(new Uri('https://example.com/'));

    expect($cookies)->toBe(['foo=bar']);
});

it('computes default path from request URI', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/foo/bar');

    $jar->setCookies(['session=abc'], $uri);

    $matchingCookies = $jar->getCookies(new Uri('https://example.com/foo'));
    $nonMatchingCookies = $jar->getCookies(new Uri('https://example.com/other'));

    expect($matchingCookies)->toBe(['session=abc'])
        ->and($nonMatchingCookies)->toBe([]);
});

it('removes cookie when max-age is zero', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['token=xyz; Path=/'], $uri);

    expect($jar->getCookies($uri))->toBe(['token=xyz']);

    $jar->setCookies(['token=xyz; Path=/; Max-Age=0'], $uri);

    expect($jar->getCookies($uri))->toBe([]);
});

it('overwrites a cookie with the same name', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['key=old; Path=/'], $uri);

    expect($jar->getCookies($uri))->toBe(['key=old']);

    $jar->setCookies(['key=new; Path=/'], $uri);

    expect($jar->getCookies($uri))->toBe(['key=new']);
});

it('returns empty array when URI has no host', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('/path');

    expect($jar->getCookies($uri))->toBe([]);
});

it('does not add cookie when URI has no host', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('/path');

    $jar->add(new Cookie(name: 'foo', value: 'bar'), $uri);

    expect($jar->getCookies($uri))->toBe([]);
});

it('returns null from get when URI has no host', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('/path');

    expect($jar->get('foo', $uri))->toBeNull();
});

it('handles remove when URI has no host', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('/path');

    $jar->remove('foo', $uri);

    expect($jar->getCookies($uri))->toBe([]);
});

it('falls back to default path when cookie path does not start with slash', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/foo/bar');

    $jar->setCookies(['key=val; Path=noslash'], $uri);

    $matchingCookies = $jar->getCookies(new Uri('https://example.com/foo'));

    expect($matchingCookies)->toBe(['key=val']);
});

it('uses default path of / when request path has no subdirectory', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['session=abc'], $uri);

    $cookies = $jar->getCookies(new Uri('https://example.com/'));

    expect($cookies)->toBe(['session=abc']);
});

it('does not match subdomain cookie to parent domain', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://sub.example.com/');

    $jar->setCookies(['foo=bar; Path=/'], $uri);

    $cookies = $jar->getCookies(new Uri('https://example.com/'));

    expect($cookies)->toBe([]);
});

it('normalizes domain by stripping trailing dot', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['foo=bar; Domain=example.com.; Path=/'], $uri);

    $cookies = $jar->getCookies(new Uri('https://example.com/'));

    expect($cookies)->toBe(['foo=bar']);
});

it('removes cookie when expires is in the past', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['token=xyz; Path=/'], $uri);

    expect($jar->getCookies($uri))->toBe(['token=xyz']);

    $jar->add(
        new Cookie(name: 'token', value: 'xyz', path: '/', expires: new DateTimeImmutable('1970-01-01 00:00:00')),
        $uri,
    );

    expect($jar->getCookies($uri))->toBe([]);
});

it('handles empty path in request URI', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com');

    $jar->setCookies(['foo=bar; Path=/'], $uri);

    $cookies = $jar->getCookies(new Uri('https://example.com'));

    expect($cookies)->toBe(['foo=bar']);
});

it('accepts matching parent domain from subdomain request', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://api.example.com/');

    $jar->setCookies(['session=abc; Domain=example.com; Path=/'], $uri);

    $cookies = $jar->getCookies(new Uri('https://api.example.com/'));

    expect($cookies)->toBe(['session=abc']);
});

it('skips expired cookies in getCookies', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->add(
        new Cookie(name: 'expired', value: 'old', path: '/', expires: new DateTimeImmutable('2099-12-31')),
        $uri,
    );
    $jar->add(
        new Cookie(name: 'fresh', value: 'new', path: '/', expires: new DateTimeImmutable('2099-12-31')),
        $uri,
    );

    expect($jar->getCookies($uri))->toHaveCount(2);

    $reflection = new ReflectionClass($jar);
    $prop = $reflection->getProperty('cookies');
    $cookies = $prop->getValue($jar);
    $cookies['example.com']['/']['expired'] = new Cookie(
        name: 'expired',
        value: 'old',
        path: '/',
        expires: new DateTimeImmutable('1970-01-01'),
    );
    $prop->setValue($jar, $cookies);

    expect($jar->getCookies($uri))->toBe(['fresh=new']);
});

it('uses default path for get when URI path is empty', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->add(new Cookie(name: 'foo', value: 'bar', path: '/'), $uri);

    $result = $jar->get('foo', new Uri('https://example.com'));

    expect($result)->not->toBeNull()
        ->and($result->value)->toBe('bar');
});

it('uses default path for remove when URI path is empty', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->add(new Cookie(name: 'foo', value: 'bar', path: '/'), $uri);

    $jar->remove('foo', new Uri('https://example.com'));

    expect($jar->get('foo', $uri))->toBeNull();
});

it('rejects cookie with empty domain attribute', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['foo=bar; Domain=; Path=/'], $uri);

    expect($jar->getCookies($uri))->toBe([]);
});

it('normalizes cookie path that does not start with slash to default', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/some/path');

    $jar->add(new Cookie(name: 'x', value: 'y', path: null), $uri);

    expect($jar->get('x', new Uri('https://example.com/some')))->not->toBeNull();
});

it('domainMatch returns false for empty domain via cookie with empty normalized domain', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->setCookies(['foo=bar; Domain=.; Path=/'], $uri);

    expect($jar->getCookies($uri))->toBe([]);
});

it('domainMatch returns false when domain normalizes to empty string in getCookies', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/');

    $jar->add(new Cookie(name: 'x', value: 'y', path: '/'), $uri);

    $reflection = new ReflectionClass($jar);
    $prop = $reflection->getProperty('cookies');
    $cookies = $prop->getValue($jar);
    $cookies['']['/'] = $cookies['example.com']['/'];
    unset($cookies['example.com']);
    $prop->setValue($jar, $cookies);

    expect($jar->getCookies($uri))->toBe([]);
});

it('normalizePath returns default for path without slash', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com');

    $jar->add(new Cookie(name: 'x', value: 'y'), $uri);

    expect($jar->getCookies(new Uri('https://example.com/')))->toBe(['x=y']);
});

it('normalizePath returns default when strrpos finds no slash beyond root', function (): void {
    $jar = new CookieJar;
    $uri = new Uri('https://example.com/file');

    $jar->add(new Cookie(name: 'x', value: 'y'), $uri);

    expect($jar->getCookies(new Uri('https://example.com/')))->toBe(['x=y']);
});
