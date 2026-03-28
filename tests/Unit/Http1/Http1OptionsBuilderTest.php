<?php

declare(strict_types=1);

use Reqxide\Http1\Http1Options;
use Reqxide\Http1\Http1OptionsBuilder;
use Reqxide\Http1\OriginalHeaderMap;

it('builds Http1Options with default values', function (): void {
    $options = (new Http1OptionsBuilder)->build();

    expect($options)->toBeInstanceOf(Http1Options::class)
        ->and($options->originalHeaderMap)->toBeNull();
});

it('supports fluent originalHeaderMap setter', function (): void {
    $map = new OriginalHeaderMap(['Host', 'User-Agent', 'Accept']);
    $builder = (new Http1OptionsBuilder)->originalHeaderMap($map);

    expect($builder)->toBeInstanceOf(Http1OptionsBuilder::class)
        ->and($builder->build()->originalHeaderMap)->toBe($map);
});
