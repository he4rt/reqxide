<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Reqxide\Contract\RedirectPolicyInterface;
use Reqxide\Redirect\RedirectPolicy;

it('implements RedirectPolicyInterface', function (): void {
    $policy = RedirectPolicy::default();

    expect($policy)->toBeInstanceOf(RedirectPolicyInterface::class);
});

it('limited(10) follows up to 9 redirects', function (): void {
    $policy = RedirectPolicy::limited(10);
    $request = new Request('GET', 'https://example.com');
    $response = new Response(302);

    expect($policy->shouldFollow($request, $response, 0))->toBeTrue()
        ->and($policy->shouldFollow($request, $response, 5))->toBeTrue()
        ->and($policy->shouldFollow($request, $response, 9))->toBeTrue();
});

it('limited(10) rejects at redirect count 10', function (): void {
    $policy = RedirectPolicy::limited(10);
    $request = new Request('GET', 'https://example.com');
    $response = new Response(302);

    expect($policy->shouldFollow($request, $response, 10))->toBeFalse()
        ->and($policy->shouldFollow($request, $response, 11))->toBeFalse();
});

it('limited(0) always rejects like none', function (): void {
    $policy = RedirectPolicy::limited(0);
    $request = new Request('GET', 'https://example.com');
    $response = new Response(302);

    expect($policy->shouldFollow($request, $response, 0))->toBeFalse();
});

it('none() shouldFollow always returns false', function (): void {
    $policy = RedirectPolicy::none();
    $request = new Request('GET', 'https://example.com');
    $response = new Response(301);

    expect($policy->shouldFollow($request, $response, 0))->toBeFalse()
        ->and($policy->shouldFollow($request, $response, 1))->toBeFalse();
});

it('none() maxRedirects returns 0', function (): void {
    $policy = RedirectPolicy::none();

    expect($policy->maxRedirects())->toBe(0);
});

it('default() behaves like limited(10)', function (): void {
    $policy = RedirectPolicy::default();
    $request = new Request('GET', 'https://example.com');
    $response = new Response(302);

    expect($policy->maxRedirects())->toBe(10)
        ->and($policy->shouldFollow($request, $response, 0))->toBeTrue()
        ->and($policy->shouldFollow($request, $response, 9))->toBeTrue()
        ->and($policy->shouldFollow($request, $response, 10))->toBeFalse();
});

it('custom() closure receives correct arguments and controls behavior', function (): void {
    $receivedArgs = [];
    $policy = RedirectPolicy::custom(function ($request, $response, $redirectCount) use (&$receivedArgs): bool {
        $receivedArgs = [
            'method' => $request->getMethod(),
            'status' => $response->getStatusCode(),
            'count' => $redirectCount,
        ];

        return $response->getStatusCode() === 301;
    });

    $request = new Request('POST', 'https://example.com');
    $response301 = new Response(301);
    $response302 = new Response(302);

    expect($policy->shouldFollow($request, $response301, 3))->toBeTrue()
        ->and($receivedArgs)->toBe([
            'method' => 'POST',
            'status' => 301,
            'count' => 3,
        ])
        ->and($policy->shouldFollow($request, $response302, 1))->toBeFalse();
});

it('custom() respects max even if closure returns true', function (): void {
    $policy = RedirectPolicy::custom(fn (): true => true, max: 3);

    $request = new Request('GET', 'https://example.com');
    $response = new Response(302);

    expect($policy->shouldFollow($request, $response, 2))->toBeTrue()
        ->and($policy->shouldFollow($request, $response, 3))->toBeFalse()
        ->and($policy->shouldFollow($request, $response, 4))->toBeFalse();
});

it('maxRedirects() returns correct value for each variant', function (): void {
    expect(RedirectPolicy::limited(5)->maxRedirects())->toBe(5)
        ->and(RedirectPolicy::limited(20)->maxRedirects())->toBe(20)
        ->and(RedirectPolicy::none()->maxRedirects())->toBe(0)
        ->and(RedirectPolicy::default()->maxRedirects())->toBe(10)
        ->and(RedirectPolicy::custom(fn (): true => true, max: 7)->maxRedirects())->toBe(7);
});
