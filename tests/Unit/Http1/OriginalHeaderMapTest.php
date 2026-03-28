<?php

declare(strict_types=1);

use Reqxide\Http1\OriginalHeaderMap;

it('can be constructed with a list of header names', function (): void {
    $headers = ['Host', 'User-Agent', 'Accept', 'Accept-Language'];
    $map = new OriginalHeaderMap($headers);

    expect($map->headerOrder)->toBe($headers);
});

it('can be constructed with an empty list', function (): void {
    $map = new OriginalHeaderMap([]);

    expect($map->headerOrder)->toBe([]);
});

it('preserves header order', function (): void {
    $headers = ['Accept-Encoding', 'Host', 'Connection'];
    $map = new OriginalHeaderMap($headers);

    expect($map->headerOrder)->toHaveCount(3)
        ->and($map->headerOrder[0])->toBe('Accept-Encoding')
        ->and($map->headerOrder[1])->toBe('Host')
        ->and($map->headerOrder[2])->toBe('Connection');
});
