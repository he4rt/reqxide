<?php

declare(strict_types=1);

use Reqxide\Tls\AlpnProtocol;
use Reqxide\Tls\AlpsProtocol;
use Reqxide\Tls\CertificateCompressor;
use Reqxide\Tls\KeyShare;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsOptionsBuilder;
use Reqxide\Tls\TlsVersion;

it('has correct defaults when constructed with no arguments', function (): void {
    $options = new TlsOptions;

    expect($options->alpnProtocols)->toBeNull()
        ->and($options->alpsProtocols)->toBeNull()
        ->and($options->alpsUseNewCodepoint)->toBeFalse()
        ->and($options->sessionTicket)->toBeTrue()
        ->and($options->minTlsVersion)->toBeNull()
        ->and($options->maxTlsVersion)->toBeNull()
        ->and($options->preSharedKey)->toBeFalse()
        ->and($options->enableEchGrease)->toBeFalse()
        ->and($options->permuteExtensions)->toBeNull()
        ->and($options->greaseEnabled)->toBeNull()
        ->and($options->enableOcspStapling)->toBeFalse()
        ->and($options->enableSignedCertTimestamps)->toBeFalse()
        ->and($options->recordSizeLimit)->toBeNull()
        ->and($options->pskSkipSessionTicket)->toBeFalse()
        ->and($options->keyShares)->toBeNull()
        ->and($options->pskDheKe)->toBeTrue()
        ->and($options->renegotiation)->toBeTrue()
        ->and($options->delegatedCredentials)->toBeNull()
        ->and($options->curvesList)->toBeNull()
        ->and($options->sigalgsList)->toBeNull()
        ->and($options->cipherList)->toBeNull()
        ->and($options->preserveTls13CipherList)->toBeNull()
        ->and($options->certificateCompressors)->toBeNull()
        ->and($options->extensionPermutation)->toBeNull()
        ->and($options->aesHwOverride)->toBeNull()
        ->and($options->randomAesHwOverride)->toBeFalse();
});

it('can be constructed with all parameters', function (): void {
    $alpnProtocols = [AlpnProtocol::Http1, AlpnProtocol::Http2];
    $alpsProtocols = [AlpsProtocol::Http2];
    $keyShares = [KeyShare::X25519, KeyShare::P256];
    $certificateCompressors = [CertificateCompressor::Brotli, CertificateCompressor::Zlib];
    $extensionPermutation = [1, 2, 3];

    $options = new TlsOptions(
        alpnProtocols: $alpnProtocols,
        alpsProtocols: $alpsProtocols,
        alpsUseNewCodepoint: true,
        sessionTicket: false,
        minTlsVersion: TlsVersion::TLS_1_2,
        maxTlsVersion: TlsVersion::TLS_1_3,
        preSharedKey: true,
        enableEchGrease: true,
        permuteExtensions: true,
        greaseEnabled: true,
        enableOcspStapling: true,
        enableSignedCertTimestamps: true,
        recordSizeLimit: 16384,
        pskSkipSessionTicket: true,
        keyShares: $keyShares,
        pskDheKe: false,
        renegotiation: false,
        delegatedCredentials: 'test-credentials',
        curvesList: 'X25519:P-256',
        sigalgsList: 'ecdsa_secp256r1_sha256',
        cipherList: 'TLS_AES_128_GCM_SHA256',
        preserveTls13CipherList: true,
        certificateCompressors: $certificateCompressors,
        extensionPermutation: $extensionPermutation,
        aesHwOverride: true,
        randomAesHwOverride: true,
    );

    expect($options->alpnProtocols)->toBe($alpnProtocols)
        ->and($options->alpsProtocols)->toBe($alpsProtocols)
        ->and($options->alpsUseNewCodepoint)->toBeTrue()
        ->and($options->sessionTicket)->toBeFalse()
        ->and($options->minTlsVersion)->toBe(TlsVersion::TLS_1_2)
        ->and($options->maxTlsVersion)->toBe(TlsVersion::TLS_1_3)
        ->and($options->preSharedKey)->toBeTrue()
        ->and($options->enableEchGrease)->toBeTrue()
        ->and($options->permuteExtensions)->toBeTrue()
        ->and($options->greaseEnabled)->toBeTrue()
        ->and($options->enableOcspStapling)->toBeTrue()
        ->and($options->enableSignedCertTimestamps)->toBeTrue()
        ->and($options->recordSizeLimit)->toBe(16384)
        ->and($options->pskSkipSessionTicket)->toBeTrue()
        ->and($options->keyShares)->toBe($keyShares)
        ->and($options->pskDheKe)->toBeFalse()
        ->and($options->renegotiation)->toBeFalse()
        ->and($options->delegatedCredentials)->toBe('test-credentials')
        ->and($options->curvesList)->toBe('X25519:P-256')
        ->and($options->sigalgsList)->toBe('ecdsa_secp256r1_sha256')
        ->and($options->cipherList)->toBe('TLS_AES_128_GCM_SHA256')
        ->and($options->preserveTls13CipherList)->toBeTrue()
        ->and($options->certificateCompressors)->toBe($certificateCompressors)
        ->and($options->extensionPermutation)->toBe($extensionPermutation)
        ->and($options->aesHwOverride)->toBeTrue()
        ->and($options->randomAesHwOverride)->toBeTrue();
});

it('is readonly', function (): void {
    $options = new TlsOptions;

    expect($options)->toBeInstanceOf(TlsOptions::class);

    $reflection = new ReflectionClass($options);

    expect($reflection->isReadOnly())->toBeTrue();
});

it('returns a TlsOptionsBuilder from static builder method', function (): void {
    $builder = TlsOptions::builder();

    expect($builder)->toBeInstanceOf(TlsOptionsBuilder::class);
});

it('returns a new builder instance on each call', function (): void {
    $builder1 = TlsOptions::builder();
    $builder2 = TlsOptions::builder();

    expect($builder1)->not->toBe($builder2);
});
