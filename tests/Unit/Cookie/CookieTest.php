<?php

declare(strict_types=1);

use Reqxide\Cookie\Cookie;

it('can be constructed with all fields', function (): void {
    $expires = new DateTimeImmutable('2030-01-01 00:00:00');

    $cookie = new Cookie(
        name: 'session',
        value: 'abc123',
        domain: 'example.com',
        path: '/app',
        maxAge: 3600,
        expires: $expires,
        secure: true,
        httpOnly: true,
        sameSite: 'Lax',
    );

    expect($cookie->name)->toBe('session')
        ->and($cookie->value)->toBe('abc123')
        ->and($cookie->domain)->toBe('example.com')
        ->and($cookie->path)->toBe('/app')
        ->and($cookie->maxAge)->toBe(3600)
        ->and($cookie->expires)->toBe($expires)
        ->and($cookie->secure)->toBeTrue()
        ->and($cookie->httpOnly)->toBeTrue()
        ->and($cookie->sameSite)->toBe('Lax');
});

it('has correct default values', function (): void {
    $cookie = new Cookie(name: 'foo', value: 'bar');

    expect($cookie->name)->toBe('foo')
        ->and($cookie->value)->toBe('bar')
        ->and($cookie->domain)->toBeNull()
        ->and($cookie->path)->toBeNull()
        ->and($cookie->maxAge)->toBeNull()
        ->and($cookie->expires)->toBeNull()
        ->and($cookie->secure)->toBeFalse()
        ->and($cookie->httpOnly)->toBeFalse()
        ->and($cookie->sameSite)->toBeNull();
});

it('is readonly', function (): void {
    $cookie = new Cookie(name: 'foo', value: 'bar');
    $reflection = new ReflectionClass($cookie);

    expect($reflection->isReadOnly())->toBeTrue();
});

it('parses a simple name=value cookie', function (): void {
    $cookie = Cookie::parse('session=abc');

    expect($cookie->name)->toBe('session')
        ->and($cookie->value)->toBe('abc')
        ->and($cookie->domain)->toBeNull()
        ->and($cookie->path)->toBeNull()
        ->and($cookie->secure)->toBeFalse()
        ->and($cookie->httpOnly)->toBeFalse();
});

it('parses a cookie with domain and path', function (): void {
    $cookie = Cookie::parse('id=123; Domain=example.com; Path=/');

    expect($cookie->name)->toBe('id')
        ->and($cookie->value)->toBe('123')
        ->and($cookie->domain)->toBe('example.com')
        ->and($cookie->path)->toBe('/');
});

it('parses a cookie with secure and httponly flags', function (): void {
    $cookie = Cookie::parse('token=xyz; Secure; HttpOnly');

    expect($cookie->name)->toBe('token')
        ->and($cookie->value)->toBe('xyz')
        ->and($cookie->secure)->toBeTrue()
        ->and($cookie->httpOnly)->toBeTrue();
});

it('parses a cookie with samesite attribute', function (): void {
    $cookie = Cookie::parse('id=123; SameSite=Lax');

    expect($cookie->sameSite)->toBe('Lax');
});

it('parses a cookie with max-age', function (): void {
    $cookie = Cookie::parse('token=xyz; Max-Age=3600');

    expect($cookie->name)->toBe('token')
        ->and($cookie->value)->toBe('xyz')
        ->and($cookie->maxAge)->toBe(3600);
});

it('parses a cookie with expires', function (): void {
    $cookie = Cookie::parse('old=val; Expires=Thu, 01 Jan 1970 00:00:00 GMT');

    expect($cookie->name)->toBe('old')
        ->and($cookie->value)->toBe('val')
        ->and($cookie->expires)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($cookie->expires->getTimestamp())->toBe(0);
});

it('parses a fully featured set-cookie header', function (): void {
    $cookie = Cookie::parse('id=123; Domain=example.com; Path=/; Secure; HttpOnly; SameSite=Lax');

    expect($cookie->name)->toBe('id')
        ->and($cookie->value)->toBe('123')
        ->and($cookie->domain)->toBe('example.com')
        ->and($cookie->path)->toBe('/')
        ->and($cookie->secure)->toBeTrue()
        ->and($cookie->httpOnly)->toBeTrue()
        ->and($cookie->sameSite)->toBe('Lax');
});

it('parses a cookie with no value after equals sign', function (): void {
    $cookie = Cookie::parse('empty=');

    expect($cookie->name)->toBe('empty')
        ->and($cookie->value)->toBe('');
});

it('reports not expired when no expiry information is set', function (): void {
    $cookie = new Cookie(name: 'foo', value: 'bar');

    expect($cookie->isExpired())->toBeFalse();
});

it('reports expired when max-age is zero', function (): void {
    $cookie = Cookie::parse('token=xyz; Max-Age=0');

    expect($cookie->isExpired())->toBeTrue();
});

it('reports expired when expires is in the past', function (): void {
    $cookie = new Cookie(
        name: 'old',
        value: 'val',
        expires: new DateTimeImmutable('1970-01-01 00:00:00'),
    );

    expect($cookie->isExpired())->toBeTrue();
});

it('reports not expired when expires is in the future', function (): void {
    $cookie = new Cookie(
        name: 'fresh',
        value: 'val',
        expires: new DateTimeImmutable('2099-12-31 23:59:59'),
    );

    expect($cookie->isExpired())->toBeFalse();
});

it('reports not expired when max-age is positive', function (): void {
    $cookie = new Cookie(name: 'foo', value: 'bar', maxAge: 3600);

    expect($cookie->isExpired())->toBeFalse();
});

it('handles a cookie header with no equals sign', function (): void {
    $cookie = Cookie::parse('justname');

    expect($cookie->name)->toBe('justname')
        ->and($cookie->value)->toBe('');
});

it('skips empty parts from consecutive semicolons', function (): void {
    $cookie = Cookie::parse('name=val;; ; Secure;;Path=/');

    expect($cookie->name)->toBe('name')
        ->and($cookie->value)->toBe('val')
        ->and($cookie->secure)->toBeTrue()
        ->and($cookie->path)->toBe('/');
});
