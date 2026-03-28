<?php

declare(strict_types=1);

use Reqxide\Http2\Priority;

it('can be constructed with all required properties', function (): void {
    $priority = new Priority(streamId: 1, dependsOn: 0, weight: 256);

    expect($priority->streamId)->toBe(1)
        ->and($priority->dependsOn)->toBe(0)
        ->and($priority->weight)->toBe(256)
        ->and($priority->exclusive)->toBeFalse();
});

it('defaults exclusive to false', function (): void {
    $priority = new Priority(streamId: 3, dependsOn: 1, weight: 128);

    expect($priority->exclusive)->toBeFalse();
});

it('can be constructed with exclusive set to true', function (): void {
    $priority = new Priority(streamId: 5, dependsOn: 3, weight: 200, exclusive: true);

    expect($priority->streamId)->toBe(5)
        ->and($priority->dependsOn)->toBe(3)
        ->and($priority->weight)->toBe(200)
        ->and($priority->exclusive)->toBeTrue();
});
