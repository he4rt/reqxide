<?php

declare(strict_types=1);

use Reqxide\Http2\StreamDependency;

it('can be constructed with stream id and weight', function (): void {
    $dep = new StreamDependency(streamId: 1, weight: 256);

    expect($dep->streamId)->toBe(1)
        ->and($dep->weight)->toBe(256)
        ->and($dep->exclusive)->toBeFalse();
});

it('defaults exclusive to false', function (): void {
    $dep = new StreamDependency(streamId: 0, weight: 128);

    expect($dep->exclusive)->toBeFalse();
});

it('can be constructed with exclusive set to true', function (): void {
    $dep = new StreamDependency(streamId: 3, weight: 200, exclusive: true);

    expect($dep->streamId)->toBe(3)
        ->and($dep->weight)->toBe(200)
        ->and($dep->exclusive)->toBeTrue();
});
