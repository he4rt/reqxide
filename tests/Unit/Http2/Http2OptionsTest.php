<?php

declare(strict_types=1);

use Reqxide\Http2\Http2Options;
use Reqxide\Http2\Http2OptionsBuilder;
use Reqxide\Http2\Priority;
use Reqxide\Http2\PseudoHeader;
use Reqxide\Http2\PseudoHeaderOrder;
use Reqxide\Http2\SettingId;
use Reqxide\Http2\SettingsOrder;
use Reqxide\Http2\StreamDependency;

it('has correct default values', function (): void {
    $options = new Http2Options;

    expect($options->adaptiveWindow)->toBeFalse()
        ->and($options->initialStreamId)->toBeNull()
        ->and($options->initialWindowSize)->toBe(65535)
        ->and($options->initialConnWindowSize)->toBe(65535)
        ->and($options->maxFrameSize)->toBeNull()
        ->and($options->headerTableSize)->toBeNull()
        ->and($options->enablePush)->toBeNull()
        ->and($options->maxHeaderListSize)->toBeNull()
        ->and($options->maxConcurrentStreams)->toBeNull()
        ->and($options->enableConnectProtocol)->toBeNull()
        ->and($options->noRfc7540Priorities)->toBeNull()
        ->and($options->headersPseudoOrder)->toBeNull()
        ->and($options->settingsOrder)->toBeNull()
        ->and($options->headersStreamDependency)->toBeNull()
        ->and($options->priorities)->toBeNull();
});

it('can be constructed with all custom values', function (): void {
    $pseudoOrder = new PseudoHeaderOrder([PseudoHeader::Method, PseudoHeader::Path]);
    $settingsOrder = new SettingsOrder([SettingId::HeaderTableSize]);
    $streamDep = new StreamDependency(streamId: 1, weight: 256);
    $priorities = [new Priority(streamId: 3, dependsOn: 0, weight: 200)];

    $options = new Http2Options(
        adaptiveWindow: true,
        initialStreamId: 1,
        initialWindowSize: 131072,
        initialConnWindowSize: 262144,
        maxFrameSize: 16384,
        headerTableSize: 4096,
        enablePush: false,
        maxHeaderListSize: 8192,
        maxConcurrentStreams: 100,
        enableConnectProtocol: true,
        noRfc7540Priorities: true,
        headersPseudoOrder: $pseudoOrder,
        settingsOrder: $settingsOrder,
        headersStreamDependency: $streamDep,
        priorities: $priorities,
    );

    expect($options->adaptiveWindow)->toBeTrue()
        ->and($options->initialStreamId)->toBe(1)
        ->and($options->initialWindowSize)->toBe(131072)
        ->and($options->initialConnWindowSize)->toBe(262144)
        ->and($options->maxFrameSize)->toBe(16384)
        ->and($options->headerTableSize)->toBe(4096)
        ->and($options->enablePush)->toBeFalse()
        ->and($options->maxHeaderListSize)->toBe(8192)
        ->and($options->maxConcurrentStreams)->toBe(100)
        ->and($options->enableConnectProtocol)->toBeTrue()
        ->and($options->noRfc7540Priorities)->toBeTrue()
        ->and($options->headersPseudoOrder)->toBe($pseudoOrder)
        ->and($options->settingsOrder)->toBe($settingsOrder)
        ->and($options->headersStreamDependency)->toBe($streamDep)
        ->and($options->priorities)->toBe($priorities);
});

it('returns an Http2OptionsBuilder from builder()', function (): void {
    $builder = Http2Options::builder();

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class);
});
