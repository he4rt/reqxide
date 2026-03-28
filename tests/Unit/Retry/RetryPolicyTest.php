<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Contract\RetryPolicyInterface;
use Reqxide\Retry\RetryPolicy;

it('implements RetryPolicyInterface', function (): void {
    $policy = RetryPolicy::default();

    expect($policy)->toBeInstanceOf(RetryPolicyInterface::class);
});

it('never() shouldRetry always returns false', function (): void {
    $policy = RetryPolicy::never();
    $request = new Request('GET', 'https://example.com');

    expect($policy->shouldRetry($request, null, new RuntimeException('fail'), 0))->toBeFalse()
        ->and($policy->shouldRetry($request, new Response(500), null, 0))->toBeFalse()
        ->and($policy->shouldRetry($request, new Response(200), null, 0))->toBeFalse();
});

it('never() delayMs returns 0', function (): void {
    $policy = RetryPolicy::never();

    expect($policy->delayMs(0))->toBe(0)
        ->and($policy->delayMs(1))->toBe(0)
        ->and($policy->delayMs(5))->toBe(0);
});

it('default() retries on server error (500)', function (): void {
    $policy = RetryPolicy::default();
    $request = new Request('GET', 'https://example.com');

    expect($policy->shouldRetry($request, new Response(500), null, 0))->toBeTrue()
        ->and($policy->shouldRetry($request, new Response(503), null, 0))->toBeTrue();
});

it('default() retries on exception', function (): void {
    $policy = RetryPolicy::default();
    $request = new Request('GET', 'https://example.com');

    expect($policy->shouldRetry($request, null, new RuntimeException('timeout'), 0))->toBeTrue();
});

it('default() does not retry on 200 response', function (): void {
    $policy = RetryPolicy::default();
    $request = new Request('GET', 'https://example.com');

    expect($policy->shouldRetry($request, new Response(200), null, 0))->toBeFalse();
});

it('default() respects maxRetries (attempt >= 2 returns false)', function (): void {
    $policy = RetryPolicy::default();
    $request = new Request('GET', 'https://example.com');

    expect($policy->shouldRetry($request, new Response(500), null, 0))->toBeTrue()
        ->and($policy->shouldRetry($request, new Response(500), null, 1))->toBeTrue()
        ->and($policy->shouldRetry($request, new Response(500), null, 2))->toBeFalse()
        ->and($policy->shouldRetry($request, new Response(500), null, 3))->toBeFalse();
});

it('custom() uses the provided classifier', function (): void {
    $policy = RetryPolicy::custom(
        classifier: fn ($request, $response, $exception): bool => $response instanceof ResponseInterface && $response->getStatusCode() === 429,
        maxRetries: 3,
    );

    $request = new Request('GET', 'https://example.com');

    expect($policy->shouldRetry($request, new Response(429), null, 0))->toBeTrue()
        ->and($policy->shouldRetry($request, new Response(500), null, 0))->toBeFalse()
        ->and($policy->shouldRetry($request, null, new RuntimeException('fail'), 0))->toBeFalse();
});

it('delayMs uses exponential backoff', function (): void {
    $policy = RetryPolicy::default();

    expect($policy->delayMs(0))->toBe(500)
        ->and($policy->delayMs(1))->toBe(1000)
        ->and($policy->delayMs(2))->toBe(2000)
        ->and($policy->delayMs(3))->toBe(4000);
});

it('budget blocks further retries after too many retries', function (): void {
    // Use custom with maxRetries high enough that the budget is the limiting factor
    $policy = RetryPolicy::custom(
        classifier: fn (): true => true,
        maxRetries: 100,
    );

    $request = new Request('GET', 'https://example.com');

    // The budget has ratio 0.2 and minRequests 10
    // First 10 retries are always allowed (below minRequests threshold in budget)
    // After that, ratio kicks in. Since ALL requests are retries (withdraw),
    // ratio = retryRequests/totalRequests approaches 1.0, well above 0.2
    // So eventually budget->canRetry() returns false
    $retryCount = 0;
    for ($i = 0; $i < 20; $i++) {
        if ($policy->shouldRetry($request, new Response(500), null, $i)) {
            $retryCount++;
        } else {
            break;
        }
    }

    // Budget allows through minRequests (10), then blocks
    expect($retryCount)->toBe(10);
});

it('budget gets deposit when shouldRetry returns false for non-retryable response', function (): void {
    $policy = RetryPolicy::default();
    $request = new Request('GET', 'https://example.com');

    // A 200 response is not retryable, so the budget should get a deposit
    // This should not throw and should return false
    $result = $policy->shouldRetry($request, new Response(200), null, 0);

    expect($result)->toBeFalse();

    // After depositing via non-retryable, retryable requests should still work
    $result = $policy->shouldRetry($request, new Response(500), null, 0);

    expect($result)->toBeTrue();
});
