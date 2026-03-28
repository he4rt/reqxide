<?php

declare(strict_types=1);

namespace Reqxide\Tls;

enum SignatureAlgorithm: string
{
    case EcdsaSecp256r1Sha256 = 'ecdsa_secp256r1_sha256';
    case EcdsaSecp384r1Sha384 = 'ecdsa_secp384r1_sha384';
    case EcdsaSecp521r1Sha512 = 'ecdsa_secp521r1_sha512';
    case RsaPssRsaeSha256 = 'rsa_pss_rsae_sha256';
    case RsaPssRsaeSha384 = 'rsa_pss_rsae_sha384';
    case RsaPssRsaeSha512 = 'rsa_pss_rsae_sha512';
    case RsaPkcs1Sha256 = 'rsa_pkcs1_sha256';
    case RsaPkcs1Sha384 = 'rsa_pkcs1_sha384';
    case RsaPkcs1Sha512 = 'rsa_pkcs1_sha512';
}
