<?php

declare(strict_types=1);

use Reqxide\Retry\RetryBudget;

it('canRetry returns true when below minRequests', function (): void {
    $budget = new RetryBudget(ratio: 0.2, minRequests: 10);

    // Even with 100% retries, still allowed if below minRequests
    for ($i = 0; $i < 9; $i++) {
        $budget->withdraw();
    }

    expect($budget->canRetry())->toBeTrue();
});

it('canRetry returns true when retry ratio is below budget', function (): void {
    $budget = new RetryBudget(ratio: 0.5, minRequests: 5);

    // 5 deposits + 2 withdrawals = 7 total, 2 retries => 2/7 = 0.286 < 0.5
    for ($i = 0; $i < 5; $i++) {
        $budget->deposit();
    }

    $budget->withdraw();
    $budget->withdraw();

    expect($budget->canRetry())->toBeTrue();
});

it('canRetry returns false when retry ratio is at or above budget', function (): void {
    $budget = new RetryBudget(ratio: 0.2, minRequests: 5);

    // 2 deposits + 8 withdrawals = 10 total, 8 retries => 8/10 = 0.8 >= 0.2
    $budget->deposit();
    $budget->deposit();
    for ($i = 0; $i < 8; $i++) {
        $budget->withdraw();
    }

    expect($budget->canRetry())->toBeFalse();
});

it('deposit increments totalRequests only', function (): void {
    $budget = new RetryBudget(ratio: 0.1, minRequests: 0);

    // With minRequests 0, we're immediately checking ratio
    // 0 retries / 1 total = 0.0 < 0.1 => can retry
    $budget->deposit();

    expect($budget->canRetry())->toBeTrue();
});

it('withdraw increments both retryRequests and totalRequests', function (): void {
    $budget = new RetryBudget(ratio: 0.1, minRequests: 0);

    // 1 retry / 1 total = 1.0 >= 0.1 => cannot retry
    $budget->withdraw();

    expect($budget->canRetry())->toBeFalse();
});

it('reset clears all counters', function (): void {
    $budget = new RetryBudget(ratio: 0.2, minRequests: 5);

    // Fill up with retries past minRequests threshold
    for ($i = 0; $i < 10; $i++) {
        $budget->withdraw();
    }

    expect($budget->canRetry())->toBeFalse();

    $budget->reset();

    // After reset, we're back below minRequests, so canRetry is true
    expect($budget->canRetry())->toBeTrue();
});

it('handles exactly at ratio boundary', function (): void {
    // ratio is 0.2, so we need retries/total === 0.2 exactly
    // 2 retries out of 10 total = 0.2 which is NOT less than 0.2 => false
    $budget = new RetryBudget(ratio: 0.2, minRequests: 5);

    // 8 deposits + 2 withdrawals = 10 total, 2 retries => 2/10 = 0.2
    for ($i = 0; $i < 8; $i++) {
        $budget->deposit();
    }

    $budget->withdraw();
    $budget->withdraw();

    expect($budget->canRetry())->toBeFalse();
});
