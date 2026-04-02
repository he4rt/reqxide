<?php

declare(strict_types=1);

use Reqxide\Middleware\ContentEncoding;

it('resolves known encodings from string', function (): void {
    expect(ContentEncoding::tryFrom('gzip'))->toBe(ContentEncoding::Gzip)
        ->and(ContentEncoding::tryFrom('deflate'))->toBe(ContentEncoding::Deflate)
        ->and(ContentEncoding::tryFrom('br'))->toBe(ContentEncoding::Brotli)
        ->and(ContentEncoding::tryFrom('zstd'))->toBe(ContentEncoding::Zstd);
});

it('returns null for unknown encoding', function (): void {
    expect(ContentEncoding::tryFrom('unknown-encoding'))->toBeNull()
        ->and(ContentEncoding::tryFrom('identity'))->toBeNull()
        ->and(ContentEncoding::tryFrom(''))->toBeNull();
});

it('detects gzip magic bytes', function (): void {
    $gzipData = gzencode('Hello');

    expect(ContentEncoding::Gzip->looksCompressed($gzipData))->toBeTrue()
        ->and(ContentEncoding::Gzip->looksCompressed('plaintext'))->toBeFalse()
        ->and(ContentEncoding::Gzip->looksCompressed('#EXTM3U'))->toBeFalse()
        ->and(ContentEncoding::Gzip->looksCompressed("\x1f"))->toBeFalse()
        ->and(ContentEncoding::Gzip->looksCompressed(''))->toBeFalse();
});

it('detects zstd magic bytes', function (): void {
    $zstdMagic = "\x28\xb5\x2f\xfd".'payload';

    expect(ContentEncoding::Zstd->looksCompressed($zstdMagic))->toBeTrue()
        ->and(ContentEncoding::Zstd->looksCompressed('plaintext'))->toBeFalse()
        ->and(ContentEncoding::Zstd->looksCompressed("\x28\xb5\x2f"))->toBeFalse()
        ->and(ContentEncoding::Zstd->looksCompressed(''))->toBeFalse();
});

it('always returns true for deflate and brotli', function (): void {
    expect(ContentEncoding::Deflate->looksCompressed('anything'))->toBeTrue()
        ->and(ContentEncoding::Deflate->looksCompressed(''))->toBeTrue()
        ->and(ContentEncoding::Brotli->looksCompressed('anything'))->toBeTrue()
        ->and(ContentEncoding::Brotli->looksCompressed(''))->toBeTrue();
});
