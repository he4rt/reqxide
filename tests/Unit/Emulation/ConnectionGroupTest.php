<?php

declare(strict_types=1);

use Reqxide\Emulation\ConnectionGroup;

it('can be constructed with an identifier', function (): void {
    $group = new ConnectionGroup('chrome_131');

    expect($group->identifier)->toBe('chrome_131');
});

it('can be created via named factory', function (): void {
    $group = ConnectionGroup::named('firefox_147');

    expect($group->identifier)->toBe('firefox_147');
});

it('equals another group with same identifier', function (): void {
    $a = ConnectionGroup::named('chrome_131');
    $b = ConnectionGroup::named('chrome_131');

    expect($a->equals($b))->toBeTrue();
});

it('does not equal a group with different identifier', function (): void {
    $a = ConnectionGroup::named('chrome_131');
    $b = ConnectionGroup::named('firefox_147');

    expect($a->equals($b))->toBeFalse();
});

it('is readonly', function (): void {
    $reflection = new ReflectionClass(ConnectionGroup::class);

    expect($reflection->isReadOnly())->toBeTrue();
});
