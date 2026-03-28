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

it('builds Http2Options with default values', function (): void {
    $options = (new Http2OptionsBuilder)->build();

    expect($options)->toBeInstanceOf(Http2Options::class)
        ->and($options->adaptiveWindow)->toBeFalse()
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

it('supports fluent adaptiveWindow setter', function (): void {
    $builder = (new Http2OptionsBuilder)->adaptiveWindow(true);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->adaptiveWindow)->toBeTrue();
});

it('supports fluent initialStreamId setter', function (): void {
    $builder = (new Http2OptionsBuilder)->initialStreamId(3);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->initialStreamId)->toBe(3);
});

it('supports fluent initialWindowSize setter', function (): void {
    $builder = (new Http2OptionsBuilder)->initialWindowSize(131072);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->initialWindowSize)->toBe(131072);
});

it('supports fluent initialConnWindowSize setter', function (): void {
    $builder = (new Http2OptionsBuilder)->initialConnWindowSize(262144);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->initialConnWindowSize)->toBe(262144);
});

it('supports fluent maxFrameSize setter', function (): void {
    $builder = (new Http2OptionsBuilder)->maxFrameSize(16384);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->maxFrameSize)->toBe(16384);
});

it('supports fluent headerTableSize setter', function (): void {
    $builder = (new Http2OptionsBuilder)->headerTableSize(4096);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->headerTableSize)->toBe(4096);
});

it('supports fluent enablePush setter', function (): void {
    $builder = (new Http2OptionsBuilder)->enablePush(false);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->enablePush)->toBeFalse();
});

it('supports fluent maxHeaderListSize setter', function (): void {
    $builder = (new Http2OptionsBuilder)->maxHeaderListSize(8192);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->maxHeaderListSize)->toBe(8192);
});

it('supports fluent maxConcurrentStreams setter', function (): void {
    $builder = (new Http2OptionsBuilder)->maxConcurrentStreams(100);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->maxConcurrentStreams)->toBe(100);
});

it('supports fluent enableConnectProtocol setter', function (): void {
    $builder = (new Http2OptionsBuilder)->enableConnectProtocol(true);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->enableConnectProtocol)->toBeTrue();
});

it('supports fluent noRfc7540Priorities setter', function (): void {
    $builder = (new Http2OptionsBuilder)->noRfc7540Priorities(true);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->noRfc7540Priorities)->toBeTrue();
});

it('supports fluent headersPseudoOrder setter', function (): void {
    $order = new PseudoHeaderOrder([PseudoHeader::Method, PseudoHeader::Path]);
    $builder = (new Http2OptionsBuilder)->headersPseudoOrder($order);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->headersPseudoOrder)->toBe($order);
});

it('supports fluent settingsOrder setter', function (): void {
    $order = new SettingsOrder([SettingId::HeaderTableSize, SettingId::EnablePush]);
    $builder = (new Http2OptionsBuilder)->settingsOrder($order);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->settingsOrder)->toBe($order);
});

it('supports fluent headersStreamDependency setter', function (): void {
    $dep = new StreamDependency(streamId: 1, weight: 256);
    $builder = (new Http2OptionsBuilder)->headersStreamDependency($dep);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->headersStreamDependency)->toBe($dep);
});

it('supports fluent priorities setter', function (): void {
    $priorities = [
        new Priority(streamId: 3, dependsOn: 0, weight: 200),
        new Priority(streamId: 5, dependsOn: 3, weight: 100, exclusive: true),
    ];
    $builder = (new Http2OptionsBuilder)->priorities($priorities);

    expect($builder)->toBeInstanceOf(Http2OptionsBuilder::class)
        ->and($builder->build()->priorities)->toBe($priorities);
});

it('supports chaining all setters together', function (): void {
    $pseudoOrder = new PseudoHeaderOrder([PseudoHeader::Method]);
    $settingsOrder = new SettingsOrder([SettingId::MaxFrameSize]);
    $streamDep = new StreamDependency(streamId: 0, weight: 128);
    $priorities = [new Priority(streamId: 1, dependsOn: 0, weight: 255)];

    $options = (new Http2OptionsBuilder)
        ->adaptiveWindow(true)
        ->initialStreamId(1)
        ->initialWindowSize(131072)
        ->initialConnWindowSize(262144)
        ->maxFrameSize(16384)
        ->headerTableSize(4096)
        ->enablePush(false)
        ->maxHeaderListSize(8192)
        ->maxConcurrentStreams(100)
        ->enableConnectProtocol(true)
        ->noRfc7540Priorities(true)
        ->headersPseudoOrder($pseudoOrder)
        ->settingsOrder($settingsOrder)
        ->headersStreamDependency($streamDep)
        ->priorities($priorities)
        ->build();

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
