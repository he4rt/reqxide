<?php

declare(strict_types=1);

use Reqxide\Http1\Http1Options;
use Reqxide\Http1\Http1OptionsBuilder;
use Reqxide\Http1\OriginalHeaderMap;

it('has correct default values', function (): void {
    $options = new Http1Options;

    expect($options->originalHeaderMap)->toBeNull();
});

it('can be constructed with an OriginalHeaderMap', function (): void {
    $map = new OriginalHeaderMap(['Host', 'User-Agent']);
    $options = new Http1Options(originalHeaderMap: $map);

    expect($options->originalHeaderMap)->toBe($map);
});

it('returns an Http1OptionsBuilder from builder()', function (): void {
    $builder = Http1Options::builder();

    expect($builder)->toBeInstanceOf(Http1OptionsBuilder::class);
});
