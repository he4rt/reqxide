<?php

declare(strict_types=1);

use Reqxide\Http2\PseudoHeader;
use Reqxide\Http2\PseudoHeaderOrder;

it('can be constructed with a list of pseudo headers', function (): void {
    $headers = [PseudoHeader::Method, PseudoHeader::Path, PseudoHeader::Authority, PseudoHeader::Scheme];
    $order = new PseudoHeaderOrder($headers);

    expect($order->headers)->toBe($headers);
});

it('can be constructed with an empty list', function (): void {
    $order = new PseudoHeaderOrder([]);

    expect($order->headers)->toBe([]);
});

it('can be constructed with a partial list', function (): void {
    $headers = [PseudoHeader::Method, PseudoHeader::Scheme];
    $order = new PseudoHeaderOrder($headers);

    expect($order->headers)->toHaveCount(2)
        ->and($order->headers[0])->toBe(PseudoHeader::Method)
        ->and($order->headers[1])->toBe(PseudoHeader::Scheme);
});
