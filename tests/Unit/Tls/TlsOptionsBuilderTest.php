<?php

declare(strict_types=1);

use Reqxide\Tls\AlpnProtocol;
use Reqxide\Tls\AlpsProtocol;
use Reqxide\Tls\BrotliCompressor;
use Reqxide\Tls\KeyShare;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsOptionsBuilder;
use Reqxide\Tls\TlsVersion;
use Reqxide\Tls\ZlibCompressor;
use Reqxide\Tls\ZstdCompressor;

it('builds TlsOptions with defaults when nothing is set', function (): void {
    $builder = new TlsOptionsBuilder;
    $options = $builder->build();

    expect($options)->toBeInstanceOf(TlsOptions::class)
        ->and($options->alpnProtocols)->toBeNull()
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

it('sets alpnProtocols and returns self', function (): void {
    $builder = new TlsOptionsBuilder;
    $protocols = [AlpnProtocol::Http1, AlpnProtocol::Http2, AlpnProtocol::Http3];

    $result = $builder->alpnProtocols($protocols);

    expect($result)->toBe($builder);
    expect($result->build()->alpnProtocols)->toBe($protocols);
});

it('sets alpsProtocols and returns self', function (): void {
    $builder = new TlsOptionsBuilder;
    $protocols = [AlpsProtocol::Http2, AlpsProtocol::Http3];

    $result = $builder->alpsProtocols($protocols);

    expect($result)->toBe($builder);
    expect($result->build()->alpsProtocols)->toBe($protocols);
});

it('sets alpsUseNewCodepoint and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->alpsUseNewCodepoint(true);

    expect($result)->toBe($builder);
    expect($result->build()->alpsUseNewCodepoint)->toBeTrue();
});

it('sets sessionTicket and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->sessionTicket(false);

    expect($result)->toBe($builder);
    expect($result->build()->sessionTicket)->toBeFalse();
});

it('sets minTlsVersion and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->minTlsVersion(TlsVersion::TLS_1_2);

    expect($result)->toBe($builder);
    expect($result->build()->minTlsVersion)->toBe(TlsVersion::TLS_1_2);
});

it('sets maxTlsVersion and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->maxTlsVersion(TlsVersion::TLS_1_3);

    expect($result)->toBe($builder);
    expect($result->build()->maxTlsVersion)->toBe(TlsVersion::TLS_1_3);
});

it('sets preSharedKey and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->preSharedKey(true);

    expect($result)->toBe($builder);
    expect($result->build()->preSharedKey)->toBeTrue();
});

it('sets enableEchGrease and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->enableEchGrease(true);

    expect($result)->toBe($builder);
    expect($result->build()->enableEchGrease)->toBeTrue();
});

it('sets permuteExtensions and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->permuteExtensions(true);

    expect($result)->toBe($builder);
    expect($result->build()->permuteExtensions)->toBeTrue();
});

it('sets greaseEnabled and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->greaseEnabled(true);

    expect($result)->toBe($builder);
    expect($result->build()->greaseEnabled)->toBeTrue();
});

it('sets enableOcspStapling and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->enableOcspStapling(true);

    expect($result)->toBe($builder);
    expect($result->build()->enableOcspStapling)->toBeTrue();
});

it('sets enableSignedCertTimestamps and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->enableSignedCertTimestamps(true);

    expect($result)->toBe($builder);
    expect($result->build()->enableSignedCertTimestamps)->toBeTrue();
});

it('sets recordSizeLimit and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->recordSizeLimit(16384);

    expect($result)->toBe($builder);
    expect($result->build()->recordSizeLimit)->toBe(16384);
});

it('sets pskSkipSessionTicket and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->pskSkipSessionTicket(true);

    expect($result)->toBe($builder);
    expect($result->build()->pskSkipSessionTicket)->toBeTrue();
});

it('sets keyShares and returns self', function (): void {
    $builder = new TlsOptionsBuilder;
    $keyShares = [KeyShare::X25519, KeyShare::P256, KeyShare::P384];

    $result = $builder->keyShares($keyShares);

    expect($result)->toBe($builder);
    expect($result->build()->keyShares)->toBe($keyShares);
});

it('sets pskDheKe and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->pskDheKe(false);

    expect($result)->toBe($builder);
    expect($result->build()->pskDheKe)->toBeFalse();
});

it('sets renegotiation and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->renegotiation(false);

    expect($result)->toBe($builder);
    expect($result->build()->renegotiation)->toBeFalse();
});

it('sets delegatedCredentials and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->delegatedCredentials('test-credentials');

    expect($result)->toBe($builder);
    expect($result->build()->delegatedCredentials)->toBe('test-credentials');
});

it('sets curvesList and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->curvesList('X25519:P-256:P-384');

    expect($result)->toBe($builder);
    expect($result->build()->curvesList)->toBe('X25519:P-256:P-384');
});

it('sets sigalgsList and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->sigalgsList('ecdsa_secp256r1_sha256:rsa_pss_rsae_sha256');

    expect($result)->toBe($builder);
    expect($result->build()->sigalgsList)->toBe('ecdsa_secp256r1_sha256:rsa_pss_rsae_sha256');
});

it('sets cipherList and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->cipherList('TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384');

    expect($result)->toBe($builder);
    expect($result->build()->cipherList)->toBe('TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384');
});

it('sets preserveTls13CipherList and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->preserveTls13CipherList(true);

    expect($result)->toBe($builder);
    expect($result->build()->preserveTls13CipherList)->toBeTrue();
});

it('sets certificateCompressors and returns self', function (): void {
    $builder = new TlsOptionsBuilder;
    $compressors = [new BrotliCompressor, new ZlibCompressor, new ZstdCompressor];

    $result = $builder->certificateCompressors($compressors);

    expect($result)->toBe($builder);
    expect($result->build()->certificateCompressors)->toBe($compressors);
});

it('sets extensionPermutation and returns self', function (): void {
    $builder = new TlsOptionsBuilder;
    $permutation = [3, 1, 4, 1, 5, 9, 2, 6];

    $result = $builder->extensionPermutation($permutation);

    expect($result)->toBe($builder);
    expect($result->build()->extensionPermutation)->toBe($permutation);
});

it('sets aesHwOverride and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->aesHwOverride(true);

    expect($result)->toBe($builder);
    expect($result->build()->aesHwOverride)->toBeTrue();
});

it('sets randomAesHwOverride and returns self', function (): void {
    $builder = new TlsOptionsBuilder;

    $result = $builder->randomAesHwOverride(true);

    expect($result)->toBe($builder);
    expect($result->build()->randomAesHwOverride)->toBeTrue();
});

it('supports fluent chaining of all setters', function (): void {
    $alpnProtocols = [AlpnProtocol::Http1, AlpnProtocol::Http2];
    $alpsProtocols = [AlpsProtocol::Http2];
    $keyShares = [KeyShare::X25519, KeyShare::P256];
    $compressors = [new BrotliCompressor];
    $permutation = [1, 2, 3];

    $options = (new TlsOptionsBuilder)
        ->alpnProtocols($alpnProtocols)
        ->alpsProtocols($alpsProtocols)
        ->alpsUseNewCodepoint(true)
        ->sessionTicket(false)
        ->minTlsVersion(TlsVersion::TLS_1_2)
        ->maxTlsVersion(TlsVersion::TLS_1_3)
        ->preSharedKey(true)
        ->enableEchGrease(true)
        ->permuteExtensions(true)
        ->greaseEnabled(true)
        ->enableOcspStapling(true)
        ->enableSignedCertTimestamps(true)
        ->recordSizeLimit(16384)
        ->pskSkipSessionTicket(true)
        ->keyShares($keyShares)
        ->pskDheKe(false)
        ->renegotiation(false)
        ->delegatedCredentials('creds')
        ->curvesList('X25519:P-256')
        ->sigalgsList('ecdsa_secp256r1_sha256')
        ->cipherList('TLS_AES_128_GCM_SHA256')
        ->preserveTls13CipherList(true)
        ->certificateCompressors($compressors)
        ->extensionPermutation($permutation)
        ->aesHwOverride(true)
        ->randomAesHwOverride(true)
        ->build();

    expect($options)->toBeInstanceOf(TlsOptions::class)
        ->and($options->alpnProtocols)->toBe($alpnProtocols)
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
        ->and($options->delegatedCredentials)->toBe('creds')
        ->and($options->curvesList)->toBe('X25519:P-256')
        ->and($options->sigalgsList)->toBe('ecdsa_secp256r1_sha256')
        ->and($options->cipherList)->toBe('TLS_AES_128_GCM_SHA256')
        ->and($options->preserveTls13CipherList)->toBeTrue()
        ->and($options->certificateCompressors)->toBe($compressors)
        ->and($options->extensionPermutation)->toBe($permutation)
        ->and($options->aesHwOverride)->toBeTrue()
        ->and($options->randomAesHwOverride)->toBeTrue();
});

it('is a final class', function (): void {
    $reflection = new ReflectionClass(TlsOptionsBuilder::class);

    expect($reflection->isFinal())->toBeTrue();
});
