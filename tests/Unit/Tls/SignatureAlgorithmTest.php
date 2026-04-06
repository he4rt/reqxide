<?php

declare(strict_types=1);

use Reqxide\Tls\SignatureAlgorithm;

it('has the correct number of cases', function (): void {
    expect(SignatureAlgorithm::cases())->toHaveCount(11);
});

it('has correct backed values', function (SignatureAlgorithm $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [SignatureAlgorithm::EcdsaSecp256r1Sha256, 'ecdsa_secp256r1_sha256'],
    [SignatureAlgorithm::EcdsaSecp384r1Sha384, 'ecdsa_secp384r1_sha384'],
    [SignatureAlgorithm::EcdsaSecp521r1Sha512, 'ecdsa_secp521r1_sha512'],
    [SignatureAlgorithm::RsaPssRsaeSha256, 'rsa_pss_rsae_sha256'],
    [SignatureAlgorithm::RsaPssRsaeSha384, 'rsa_pss_rsae_sha384'],
    [SignatureAlgorithm::RsaPssRsaeSha512, 'rsa_pss_rsae_sha512'],
    [SignatureAlgorithm::RsaPkcs1Sha256, 'rsa_pkcs1_sha256'],
    [SignatureAlgorithm::RsaPkcs1Sha384, 'rsa_pkcs1_sha384'],
    [SignatureAlgorithm::RsaPkcs1Sha512, 'rsa_pkcs1_sha512'],
    [SignatureAlgorithm::EcdsaSha1, 'ecdsa_sha1'],
    [SignatureAlgorithm::RsaPkcs1Sha1, 'rsa_pkcs1_sha1'],
]);

it('can be created from backed value', function (string $value, SignatureAlgorithm $expected): void {
    expect(SignatureAlgorithm::from($value))->toBe($expected);
})->with([
    ['ecdsa_secp256r1_sha256', SignatureAlgorithm::EcdsaSecp256r1Sha256],
    ['ecdsa_secp384r1_sha384', SignatureAlgorithm::EcdsaSecp384r1Sha384],
    ['ecdsa_secp521r1_sha512', SignatureAlgorithm::EcdsaSecp521r1Sha512],
    ['rsa_pss_rsae_sha256', SignatureAlgorithm::RsaPssRsaeSha256],
    ['rsa_pss_rsae_sha384', SignatureAlgorithm::RsaPssRsaeSha384],
    ['rsa_pss_rsae_sha512', SignatureAlgorithm::RsaPssRsaeSha512],
    ['rsa_pkcs1_sha256', SignatureAlgorithm::RsaPkcs1Sha256],
    ['rsa_pkcs1_sha384', SignatureAlgorithm::RsaPkcs1Sha384],
    ['rsa_pkcs1_sha512', SignatureAlgorithm::RsaPkcs1Sha512],
    ['ecdsa_sha1', SignatureAlgorithm::EcdsaSha1],
    ['rsa_pkcs1_sha1', SignatureAlgorithm::RsaPkcs1Sha1],
]);

it('returns null for invalid tryFrom', function (): void {
    expect(SignatureAlgorithm::tryFrom('invalid'))->toBeNull();
    expect(SignatureAlgorithm::tryFrom(''))->toBeNull();
});
