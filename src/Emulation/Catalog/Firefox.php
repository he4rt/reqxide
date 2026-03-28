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
use Reqxide\Tls\AlpnProtocol;
use Reqxide\Tls\KeyShare;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsVersion;

final class Firefox
{
    private const string CIPHER_LIST = 'TLS_AES_128_GCM_SHA256:TLS_CHACHA20_POLY1305_SHA256:TLS_AES_256_GCM_SHA384:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-SHA:ECDHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA:AES256-SHA';

    private const string CURVES_LIST = 'X25519:P-256:P-384:P-521:ffdhe2048:ffdhe3072';

    private const string SIGALGS_LIST = 'ecdsa_secp256r1_sha256:ecdsa_secp384r1_sha384:ecdsa_secp521r1_sha512:rsa_pss_rsae_sha256:rsa_pss_rsae_sha384:rsa_pss_rsae_sha512:rsa_pkcs1_sha256:rsa_pkcs1_sha384:rsa_pkcs1_sha512';

    public static function v136(): Profile
    {
        return self::buildProfile(version: '136.0');
    }

    public static function v135(): Profile
    {
        return self::buildProfile(version: '135.0');
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
}
