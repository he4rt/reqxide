<?php

declare(strict_types=1);

namespace Reqxide\Emulation\Catalog;

use Reqxide\Emulation\Profile;
use Reqxide\Http1\OriginalHeaderMap;
use Reqxide\Http2\Http2Options;
use Reqxide\Http2\PseudoHeader;
use Reqxide\Http2\PseudoHeaderOrder;
use Reqxide\Http2\SettingId;
use Reqxide\Http2\SettingsOrder;
use Reqxide\Http2\StreamDependency;
use Reqxide\Tls\AlpnProtocol;
use Reqxide\Tls\CertificateCompressor;
use Reqxide\Tls\KeyShare;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsVersion;

final class Firefox
{
    // wreq cipher list (15 ciphers)
    private const string CIPHER_LIST = 'TLS_AES_128_GCM_SHA256:TLS_CHACHA20_POLY1305_SHA256:TLS_AES_256_GCM_SHA384:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-SHA:ECDHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA:AES256-SHA';

    // curl_impersonate cipher list (17 ciphers — adds ECDSA CBC variants)
    private const string CI_CIPHER_LIST = 'TLS_AES_128_GCM_SHA256:TLS_CHACHA20_POLY1305_SHA256:TLS_AES_256_GCM_SHA384:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES128-SHA:ECDHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA:AES256-SHA';

    private const string CURVES_LIST = 'X25519:P-256:P-384:P-521:ffdhe2048:ffdhe3072';

    private const string CI_CURVES_LIST = 'X25519MLKEM768:X25519:P-256:P-384:P-521:ffdhe2048:ffdhe3072';

    private const string SIGALGS_LIST = 'ecdsa_secp256r1_sha256:ecdsa_secp384r1_sha384:ecdsa_secp521r1_sha512:rsa_pss_rsae_sha256:rsa_pss_rsae_sha384:rsa_pss_rsae_sha512:rsa_pkcs1_sha256:rsa_pkcs1_sha384:rsa_pkcs1_sha512';

    private const string CI_SIGALGS_LIST = 'ecdsa_secp256r1_sha256:ecdsa_secp384r1_sha384:ecdsa_secp521r1_sha512:rsa_pss_rsae_sha256:rsa_pss_rsae_sha384:rsa_pss_rsae_sha512:rsa_pkcs1_sha256:rsa_pkcs1_sha384:rsa_pkcs1_sha512:ecdsa_sha1:rsa_pkcs1_sha1';

    private const string CI_DELEGATED_CREDENTIALS = 'ecdsa_secp256r1_sha256:ecdsa_secp384r1_sha384:ecdsa_secp521r1_sha512:ecdsa_sha1';

    // ─── wreq profiles (v135, v136) — unchanged ───

    public static function v136(): Profile
    {
        return self::buildProfile(version: '136.0');
    }

    public static function v135(): Profile
    {
        return self::buildProfile(version: '135.0');
    }

    // ─── curl_impersonate profiles ───

    public static function v133(): Profile
    {
        return self::ciProfile(
            version: '133.0',
            signedCertTimestamps: false,
            extensionOrder: [0, 23, 65281, 10, 11, 35, 16, 5, 34, 51, 43, 13, 45, 28, 27, 65037],
        );
    }

    // @todo verify TLS params against curl_impersonate source (--impersonate only)
    public static function v144(): Profile
    {
        return self::ciProfile(
            version: '144.0',
            signedCertTimestamps: true,
            extensionOrder: [0, 23, 65281, 10, 11, 35, 16, 5, 34, 18, 51, 43, 13, 45, 28, 27, 65037],
        );
    }

    // @todo verify TLS params against curl_impersonate source (--impersonate only)
    public static function v147(): Profile
    {
        return self::ciProfile(
            version: '147.0',
            signedCertTimestamps: true,
            extensionOrder: [0, 23, 65281, 10, 11, 35, 16, 5, 34, 18, 51, 43, 13, 45, 28, 27, 65037],
        );
    }

    // ─── wreq private helpers (unchanged) ───

    private static function baseTlsOptions(): TlsOptions
    {
        return TlsOptions::builder()
            ->minTlsVersion(TlsVersion::TLS_1_2)
            ->maxTlsVersion(TlsVersion::TLS_1_3)
            ->cipherList(self::CIPHER_LIST)
            ->sigalgsList(self::SIGALGS_LIST)
            ->curvesList(self::CURVES_LIST)
            ->alpnProtocols([AlpnProtocol::Http2, AlpnProtocol::Http1])
            ->keyShares([KeyShare::X25519, KeyShare::P256])
            ->enableOcspStapling(true)
            ->enableSignedCertTimestamps(true)
            ->greaseEnabled(false)
            ->permuteExtensions(false)
            ->enableEchGrease(true)
            ->sessionTicket(true)
            ->recordSizeLimit(16385)
            ->build();
    }

    private static function baseHttp2Options(): Http2Options
    {
        return Http2Options::builder()
            ->headerTableSize(65536)
            ->enablePush(false)
            ->maxConcurrentStreams(100)
            ->initialWindowSize(131072)
            ->maxFrameSize(16384)
            ->maxHeaderListSize(65536)
            ->initialConnWindowSize(12582912)
            ->headersPseudoOrder(new PseudoHeaderOrder([
                PseudoHeader::Method,
                PseudoHeader::Path,
                PseudoHeader::Authority,
                PseudoHeader::Scheme,
            ]))
            ->settingsOrder(new SettingsOrder([
                SettingId::HeaderTableSize,
                SettingId::EnablePush,
                SettingId::MaxConcurrentStreams,
                SettingId::InitialWindowSize,
                SettingId::MaxFrameSize,
                SettingId::MaxHeaderListSize,
            ]))
            ->build();
    }

    /**
     * @return array<string, string>
     */
    private static function baseHeaders(string $version): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:'.$version.') Gecko/20100101 Firefox/'.$version,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Accept-Encoding' => 'gzip, deflate, br, zstd',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Priority' => 'u=0, i',
        ];
    }

    private static function baseHeaderOrder(): OriginalHeaderMap
    {
        return new OriginalHeaderMap([
            'Host',
            'User-Agent',
            'Accept',
            'Accept-Language',
            'Accept-Encoding',
            'Upgrade-Insecure-Requests',
            'Sec-Fetch-Dest',
            'Sec-Fetch-Mode',
            'Sec-Fetch-Site',
            'Sec-Fetch-User',
            'Priority',
            'Connection',
            'Cookie',
        ]);
    }

    private static function buildProfile(string $version): Profile
    {
        return new Profile(
            tlsOptions: self::baseTlsOptions(),
            http2Options: self::baseHttp2Options(),
            defaultHeaders: self::baseHeaders($version),
            originalHeaderMap: self::baseHeaderOrder(),
        );
    }

    // ─── curl_impersonate private helpers ───

    /**
     * @param  list<int>  $extensionOrder
     */
    private static function ciTls(bool $signedCertTimestamps, array $extensionOrder): TlsOptions
    {
        return TlsOptions::builder()
            ->minTlsVersion(TlsVersion::TLS_1_2)
            ->maxTlsVersion(TlsVersion::TLS_1_3)
            ->cipherList(self::CI_CIPHER_LIST)
            ->sigalgsList(self::CI_SIGALGS_LIST)
            ->curvesList(self::CI_CURVES_LIST)
            ->alpnProtocols([AlpnProtocol::Http2, AlpnProtocol::Http1])
            ->keyShares([KeyShare::X25519MLKEM768, KeyShare::X25519, KeyShare::P256])
            ->enableOcspStapling(true)
            ->enableSignedCertTimestamps($signedCertTimestamps)
            ->greaseEnabled(false)
            ->permuteExtensions(false)
            ->enableEchGrease(true)
            ->sessionTicket(true)
            ->recordSizeLimit(4001)
            ->delegatedCredentials(self::CI_DELEGATED_CREDENTIALS)
            ->certificateCompressors([CertificateCompressor::Zlib, CertificateCompressor::Brotli, CertificateCompressor::Zstd])
            ->extensionPermutation($extensionOrder)
            ->build();
    }

    private static function ciHttp2(): Http2Options
    {
        return Http2Options::builder()
            ->headerTableSize(65536)
            ->enablePush(false)
            ->initialWindowSize(131072)
            ->maxFrameSize(16384)
            ->initialConnWindowSize(12517377)
            ->headersStreamDependency(new StreamDependency(0, 42, false))
            ->headersPseudoOrder(new PseudoHeaderOrder([
                PseudoHeader::Method,
                PseudoHeader::Path,
                PseudoHeader::Authority,
                PseudoHeader::Scheme,
            ]))
            ->settingsOrder(new SettingsOrder([
                SettingId::HeaderTableSize,
                SettingId::EnablePush,
                SettingId::InitialWindowSize,
                SettingId::MaxFrameSize,
            ]))
            ->build();
    }

    /**
     * @return array<string, string>
     */
    private static function ciHeaders(string $version): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:'.$version.') Gecko/20100101 Firefox/'.$version,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Accept-Encoding' => 'gzip, deflate, br, zstd',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Priority' => 'u=0, i',
            'TE' => 'trailers',
        ];
    }

    private static function ciHeaderOrder(): OriginalHeaderMap
    {
        return new OriginalHeaderMap([
            'Host',
            'User-Agent',
            'Accept',
            'Accept-Language',
            'Accept-Encoding',
            'Upgrade-Insecure-Requests',
            'Sec-Fetch-Dest',
            'Sec-Fetch-Mode',
            'Sec-Fetch-Site',
            'Sec-Fetch-User',
            'Priority',
            'TE',
            'Connection',
            'Cookie',
        ]);
    }

    /**
     * @param  list<int>  $extensionOrder
     */
    private static function ciProfile(string $version, bool $signedCertTimestamps, array $extensionOrder): Profile
    {
        return new Profile(
            tlsOptions: self::ciTls($signedCertTimestamps, $extensionOrder),
            http2Options: self::ciHttp2(),
            defaultHeaders: self::ciHeaders($version),
            originalHeaderMap: self::ciHeaderOrder(),
        );
    }
}
