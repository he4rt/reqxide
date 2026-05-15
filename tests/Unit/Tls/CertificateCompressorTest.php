<?php

declare(strict_types=1);

use Reqxide\Tls\BrotliCompressor;
use Reqxide\Tls\CertificateCompressionAlgorithm;
use Reqxide\Tls\CertificateCompressorInterface;
use Reqxide\Tls\ZlibCompressor;
use Reqxide\Tls\ZstdCompressor;

it('CertificateCompressionAlgorithm has the correct number of cases', function (): void {
    expect(CertificateCompressionAlgorithm::cases())->toHaveCount(3);
});

it('CertificateCompressionAlgorithm has correct backed values', function (CertificateCompressionAlgorithm $case, int $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [CertificateCompressionAlgorithm::Zlib, 0x0001],
    [CertificateCompressionAlgorithm::Brotli, 0x0002],
    [CertificateCompressionAlgorithm::Zstd, 0x0003],
]);

it('ZlibCompressor implements CertificateCompressorInterface', function (): void {
    $compressor = new ZlibCompressor;

    expect($compressor)->toBeInstanceOf(CertificateCompressorInterface::class)
        ->and($compressor->algorithm())->toBe(CertificateCompressionAlgorithm::Zlib);
});

it('ZlibCompressor compresses and decompresses', function (): void {
    $compressor = new ZlibCompressor;
    $original = 'Hello, certificate compression!';

    $compressed = $compressor->compress($original);
    $decompressed = $compressor->decompress($compressed, strlen($original));

    expect($decompressed)->toBe($original);
});

it('BrotliCompressor implements CertificateCompressorInterface', function (): void {
    $compressor = new BrotliCompressor;

    expect($compressor)->toBeInstanceOf(CertificateCompressorInterface::class)
        ->and($compressor->algorithm())->toBe(CertificateCompressionAlgorithm::Brotli);
});

it('BrotliCompressor compresses and decompresses', function (): void {
    $compressor = new BrotliCompressor;
    $original = 'Hello, brotli certificate compression!';

    $compressed = $compressor->compress($original);
    $decompressed = $compressor->decompress($compressed, strlen($original));

    expect($decompressed)->toBe($original);
})->skip(! function_exists('brotli_compress'), 'brotli extension is not available');

it('ZstdCompressor implements CertificateCompressorInterface', function (): void {
    $compressor = new ZstdCompressor;

    expect($compressor)->toBeInstanceOf(CertificateCompressorInterface::class)
        ->and($compressor->algorithm())->toBe(CertificateCompressionAlgorithm::Zstd);
});

it('ZstdCompressor compresses and decompresses', function (): void {
    $compressor = new ZstdCompressor;
    $original = 'Hello, zstd certificate compression!';

    $compressed = $compressor->compress($original);
    $decompressed = $compressor->decompress($compressed, strlen($original));

    expect($decompressed)->toBe($original);
})->skip(! function_exists('zstd_compress'), 'zstd extension is not available');
