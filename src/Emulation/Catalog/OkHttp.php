<?php

declare(strict_types=1);

namespace Reqxide\Emulation\Catalog;

use Reqxide\Emulation\Profile;
use Reqxide\Http1\OriginalHeaderMap;
use Reqxide\Tls\AlpnProtocol;
use Reqxide\Tls\KeyShare;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsVersion;

final class OkHttp
{
    private const string CIPHER_LIST = 'TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305';

    private const string CURVES_LIST = 'X25519:P-256:P-384';

    private const string SIGALGS_LIST = 'ecdsa_secp256r1_sha256:rsa_pss_rsae_sha256:rsa_pkcs1_sha256:ecdsa_secp384r1_sha384:rsa_pss_rsae_sha384:rsa_pkcs1_sha384:rsa_pss_rsae_sha512:rsa_pkcs1_sha512';

    public static function v5(): Profile
    {
        return self::buildProfile(version: 'okhttp/5.0.0-alpha.14');
    }

    public static function v4(): Profile
    {
        return self::buildProfile(version: 'okhttp/4.12.0');
    }

    private static function baseTlsOptions(): TlsOptions
    {
        return TlsOptions::builder()
            ->minTlsVersion(TlsVersion::TLS_1_2)
            ->maxTlsVersion(TlsVersion::TLS_1_3)
            ->cipherList(self::CIPHER_LIST)
            ->sigalgsList(self::SIGALGS_LIST)
            ->curvesList(self::CURVES_LIST)
            ->alpnProtocols([AlpnProtocol::Http2, AlpnProtocol::Http1])
            ->keyShares([KeyShare::X25519])
            ->enableOcspStapling(true)
            ->enableSignedCertTimestamps(false)
            ->greaseEnabled(false)
            ->permuteExtensions(false)
            ->enableEchGrease(false)
            ->sessionTicket(true)
            ->build();
    }

    /**
     * @return array<string, string>
     */
    private static function baseHeaders(string $version): array
    {
        return [
            'User-Agent' => $version,
            'Accept-Encoding' => 'gzip, deflate, br',
        ];
    }

    private static function baseHeaderOrder(): OriginalHeaderMap
    {
        return new OriginalHeaderMap([
            'Host',
            'User-Agent',
            'Accept-Encoding',
            'Connection',
            'Cookie',
        ]);
    }

    private static function buildProfile(string $version): Profile
    {
        return new Profile(
            tlsOptions: self::baseTlsOptions(),
            http2Options: null,
            defaultHeaders: self::baseHeaders($version),
            originalHeaderMap: self::baseHeaderOrder(),
        );
    }
}
