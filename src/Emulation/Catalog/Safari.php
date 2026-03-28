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

final class Safari
{
    private const string CIPHER_LIST = 'TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES256-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES256-SHA384:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES256-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA:ECDHE-RSA-AES128-SHA:AES256-GCM-SHA384:AES128-GCM-SHA256:AES256-SHA256:AES128-SHA256:AES256-SHA:AES128-SHA';

    private const string CURVES_LIST = 'X25519:P-256:P-384:P-521';

    private const string SIGALGS_LIST = 'ecdsa_secp256r1_sha256:rsa_pss_rsae_sha256:rsa_pkcs1_sha256:ecdsa_secp384r1_sha384:rsa_pss_rsae_sha384:rsa_pkcs1_sha384:rsa_pss_rsae_sha512:rsa_pkcs1_sha512:rsa_pkcs1_sha1';

    public static function v18(): Profile
    {
        return self::buildProfile(
            userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/605.1.15',
        );
    }

    public static function iPad18(): Profile
    {
        return self::buildProfile(
            userAgent: 'Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
        );
    }

    public static function iOS18(): Profile
    {
        return self::buildProfile(
            userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
        );
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

    private static function baseHttp2Options(): Http2Options
    {
        return Http2Options::builder()
            ->headerTableSize(4096)
            ->enablePush(false)
            ->maxConcurrentStreams(100)
            ->initialWindowSize(2097152)
            ->maxFrameSize(16384)
            ->initialConnWindowSize(10485760)
            ->headersPseudoOrder(new PseudoHeaderOrder([
                PseudoHeader::Method,
                PseudoHeader::Scheme,
                PseudoHeader::Path,
                PseudoHeader::Authority,
            ]))
            ->settingsOrder(new SettingsOrder([
                SettingId::HeaderTableSize,
                SettingId::EnablePush,
                SettingId::MaxConcurrentStreams,
                SettingId::InitialWindowSize,
                SettingId::MaxFrameSize,
            ]))
            ->build();
    }

    /**
     * @return array<string, string>
     */
    private static function baseHeaders(string $userAgent): array
    {
        return [
            'User-Agent' => $userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
        ];
    }

    private static function baseHeaderOrder(): OriginalHeaderMap
    {
        return new OriginalHeaderMap([
            'Host',
            'Accept',
            'User-Agent',
            'Accept-Language',
            'Accept-Encoding',
            'Connection',
            'Cookie',
        ]);
    }

    private static function buildProfile(string $userAgent): Profile
    {
        return new Profile(
            tlsOptions: self::baseTlsOptions(),
            http2Options: self::baseHttp2Options(),
            defaultHeaders: self::baseHeaders($userAgent),
            originalHeaderMap: self::baseHeaderOrder(),
        );
    }
}
