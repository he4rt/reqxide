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
use Reqxide\Tls\KeyShare;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsVersion;
use Reqxide\Tls\ZlibCompressor;

final class Safari
{
    // curl_impersonate Safari 15.3 cipher list (26 ciphers — with SHA384/SHA256 AND 3DES)
    private const string CI_CIPHER_LIST_15_3 = 'TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES256-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-ECDSA-AES256-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-RSA-AES128-SHA256:ECDHE-RSA-AES256-SHA:ECDHE-RSA-AES128-SHA:AES256-GCM-SHA384:AES128-GCM-SHA256:AES256-SHA256:AES128-SHA256:AES256-SHA:AES128-SHA:ECDHE-ECDSA-DES-CBC3-SHA:ECDHE-RSA-DES-CBC3-SHA:DES-CBC3-SHA';

    // curl_impersonate Safari 15.5-18.x cipher list (20 ciphers — no SHA384/SHA256 CBC, with 3DES)
    private const string CI_CIPHER_LIST = 'TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES256-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA:ECDHE-RSA-AES128-SHA:AES256-GCM-SHA384:AES128-GCM-SHA256:AES256-SHA:AES128-SHA:ECDHE-ECDSA-DES-CBC3-SHA:ECDHE-RSA-DES-CBC3-SHA:DES-CBC3-SHA';

    // curl_impersonate Safari 26.x cipher list (20 ciphers — AES_256 first)
    private const string CI_CIPHER_LIST_26 = 'TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256:TLS_AES_128_GCM_SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES256-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA:ECDHE-RSA-AES128-SHA:AES256-GCM-SHA384:AES128-GCM-SHA256:AES256-SHA:AES128-SHA:ECDHE-ECDSA-DES-CBC3-SHA:ECDHE-RSA-DES-CBC3-SHA:DES-CBC3-SHA';

    private const string CURVES_LIST = 'X25519:P-256:P-384:P-521';

    // curl_impersonate sigalgs for Safari 15.x-17.x (includes ecdsa_sha1 + duplicate rsa_pss_rsae_sha384)
    private const string CI_SIGALGS_LEGACY = 'ecdsa_secp256r1_sha256:rsa_pss_rsae_sha256:rsa_pkcs1_sha256:ecdsa_secp384r1_sha384:ecdsa_sha1:rsa_pss_rsae_sha384:rsa_pss_rsae_sha384:rsa_pkcs1_sha384:rsa_pss_rsae_sha512:rsa_pkcs1_sha512:rsa_pkcs1_sha1';

    // curl_impersonate sigalgs for Safari 18.x+ (no ecdsa_sha1, has duplicate rsa_pss_rsae_sha384)
    private const string CI_SIGALGS_MODERN = 'ecdsa_secp256r1_sha256:rsa_pss_rsae_sha256:rsa_pkcs1_sha256:ecdsa_secp384r1_sha384:rsa_pss_rsae_sha384:rsa_pss_rsae_sha384:rsa_pkcs1_sha384:rsa_pss_rsae_sha512:rsa_pkcs1_sha512:rsa_pkcs1_sha1';

    // Safari 18.0 — uses CI profile to match curl_safari180 binary
    public static function v18(): Profile
    {
        return new Profile(
            tlsOptions: self::ciTls18x(),
            http2Options: self::ciHttp2_180(),
            defaultHeaders: self::ciHeaders18x(
                userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/605.1.15',
                encoding: 'gzip, deflate, br',
            ),
            originalHeaderMap: self::ciHeaderOrder18x(),
        );
    }

    public static function iPad18(): Profile
    {
        return new Profile(
            tlsOptions: self::ciTls18x(),
            http2Options: self::ciHttp2_180(),
            defaultHeaders: self::ciHeaders18x(
                userAgent: 'Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
                encoding: 'gzip, deflate, br',
            ),
            originalHeaderMap: self::ciHeaderOrder18x(),
        );
    }

    public static function iOS18(): Profile
    {
        return new Profile(
            tlsOptions: self::ciTls18x(),
            http2Options: self::ciHttp2_180(),
            defaultHeaders: self::ciHeaders18x(
                userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
                encoding: 'gzip, deflate, br',
            ),
            originalHeaderMap: self::ciHeaderOrder18x(),
        );
    }

    // ─── curl_impersonate profiles ───

    // Era 1: Safari 15.x (TLS 1.0, no session ticket, GREASE, pseudo mspa)
    public static function v153(): Profile
    {
        return new Profile(
            tlsOptions: self::ciTls15x(cipherList: self::CI_CIPHER_LIST_15_3),
            http2Options: self::ciHttp2Legacy(enablePush: false, windowSize: 4194304),
            defaultHeaders: self::ciHeaders15x(
                userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.3 Safari/605.1.15',
                acceptLanguage: 'en-us',
            ),
            originalHeaderMap: self::ciHeaderOrder15x(),
        );
    }

    public static function v155(): Profile
    {
        return new Profile(
            tlsOptions: self::ciTls15x(certCompression: true),
            http2Options: self::ciHttp2Legacy(enablePush: false, windowSize: 4194304),
            defaultHeaders: self::ciHeaders15x(
                userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.5 Safari/605.1.15',
                acceptLanguage: 'en-GB,en-US;q=0.9,en;q=0.8',
            ),
            originalHeaderMap: self::ciHeaderOrder15x(),
        );
    }

    // Era 2: Safari 17.x (TLS 1.0, Sec-Fetch headers, pseudo mspa)
    public static function v170(): Profile
    {
        return new Profile(
            tlsOptions: self::ciTls15x(certCompression: true),
            http2Options: self::ciHttp2Legacy(enablePush: true, windowSize: 4194304),
            defaultHeaders: self::ciHeaders17x(
                userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
            ),
            originalHeaderMap: self::ciHeaderOrder17x(),
        );
    }

    public static function v172iOS(): Profile
    {
        return new Profile(
            tlsOptions: self::ciTls15x(certCompression: true),
            http2Options: self::ciHttp2Legacy(enablePush: true, windowSize: 2097152),
            defaultHeaders: self::ciHeaders17x(
                userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
            ),
            originalHeaderMap: self::ciHeaderOrder17x(),
        );
    }

    // Era 3: Safari 18.x (pseudo msap, Priority header, settings 8:1 9:1)
    public static function v184(): Profile
    {
        return new Profile(
            tlsOptions: self::ciTls18x(),
            http2Options: self::ciHttp2_184(),
            defaultHeaders: self::ciHeaders18x(
                userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Safari/605.1.15',
                encoding: 'gzip, deflate, br',
            ),
            originalHeaderMap: self::ciHeaderOrder18x(),
        );
    }

    public static function v184iOS(): Profile
    {
        return new Profile(
            tlsOptions: self::ciTls18x(),
            http2Options: self::ciHttp2_184(),
            defaultHeaders: self::ciHeaders18x(
                userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1',
                encoding: 'gzip, deflate, br',
            ),
            originalHeaderMap: self::ciHeaderOrder18x(),
        );
    }

    // Era 4: Safari 26.x (TLS 1.2, MLKEM, zstd, noPriority)
    public static function v260(): Profile
    {
        return new Profile(
            tlsOptions: self::ciTls26x(curves: 'X25519MLKEM768:X25519:P-256:P-384:P-521'),
            http2Options: self::ciHttp2_26x(),
            defaultHeaders: self::ciHeaders18x(
                userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Safari/605.1.15',
                encoding: 'gzip, deflate, br, zstd',
            ),
            originalHeaderMap: self::ciHeaderOrder18x(),
        );
    }

    public static function v260iOS(): Profile
    {
        return new Profile(
            tlsOptions: self::ciTls26x(curves: self::CURVES_LIST),
            http2Options: self::ciHttp2_26x(),
            defaultHeaders: self::ciHeaders18x(
                userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1',
                encoding: 'gzip, deflate, br, zstd',
            ),
            originalHeaderMap: self::ciHeaderOrder18x(),
        );
    }

    // ─── curl_impersonate private helpers ───

    // TLS: Safari 15.x-17.x (TLS 1.0, no session ticket, GREASE, signedCertTS)
    private static function ciTls15x(bool $certCompression = false, string $cipherList = self::CI_CIPHER_LIST): TlsOptions
    {
        $builder = TlsOptions::builder()
            ->minTlsVersion(TlsVersion::TLS_1_0)
            ->maxTlsVersion(TlsVersion::TLS_1_3)
            ->cipherList($cipherList)
            ->sigalgsList(self::CI_SIGALGS_LEGACY)
            ->curvesList(self::CURVES_LIST)
            ->alpnProtocols([AlpnProtocol::Http2, AlpnProtocol::Http1])
            ->keyShares([KeyShare::X25519])
            ->enableOcspStapling(true)
            ->enableSignedCertTimestamps(true)
            ->greaseEnabled(true)
            ->permuteExtensions(false)
            ->enableEchGrease(false)
            ->sessionTicket(false);

        if ($certCompression) {
            $builder->certificateCompressors([new ZlibCompressor]);
        }

        return $builder->build();
    }

    // TLS: Safari 18.x (same as 15.x but modern sigalgs)
    private static function ciTls18x(): TlsOptions
    {
        return TlsOptions::builder()
            ->minTlsVersion(TlsVersion::TLS_1_0)
            ->maxTlsVersion(TlsVersion::TLS_1_3)
            ->cipherList(self::CI_CIPHER_LIST)
            ->sigalgsList(self::CI_SIGALGS_MODERN)
            ->curvesList(self::CURVES_LIST)
            ->alpnProtocols([AlpnProtocol::Http2, AlpnProtocol::Http1])
            ->keyShares([KeyShare::X25519])
            ->enableOcspStapling(true)
            ->enableSignedCertTimestamps(true)
            ->greaseEnabled(true)
            ->permuteExtensions(false)
            ->enableEchGrease(false)
            ->sessionTicket(false)
            ->certificateCompressors([new ZlibCompressor])
            ->build();
    }

    // TLS: Safari 26.x (TLS 1.2, session ticket enabled, GREASE)
    private static function ciTls26x(string $curves): TlsOptions
    {
        return TlsOptions::builder()
            ->minTlsVersion(TlsVersion::TLS_1_2)
            ->maxTlsVersion(TlsVersion::TLS_1_3)
            ->cipherList(self::CI_CIPHER_LIST_26)
            ->sigalgsList(self::CI_SIGALGS_MODERN)
            ->curvesList($curves)
            ->alpnProtocols([AlpnProtocol::Http2, AlpnProtocol::Http1])
            ->keyShares([KeyShare::X25519])
            ->enableOcspStapling(true)
            ->enableSignedCertTimestamps(true)
            ->greaseEnabled(true)
            ->permuteExtensions(false)
            ->enableEchGrease(false)
            ->sessionTicket(true)
            ->certificateCompressors([new ZlibCompressor])
            ->build();
    }

    // HTTP/2: Safari 15.x-17.x (pseudo mspa, weight 255)
    private static function ciHttp2Legacy(bool $enablePush, int $windowSize): Http2Options
    {
        $settings = [];
        if ($enablePush) {
            $settings[] = SettingId::EnablePush;
        }

        $settings[] = SettingId::InitialWindowSize;
        $settings[] = SettingId::MaxConcurrentStreams;

        $builder = Http2Options::builder()
            ->maxConcurrentStreams(100)
            ->initialWindowSize($windowSize)
            ->initialConnWindowSize(10485760)
            ->headersStreamDependency(new StreamDependency(0, 255, false))
            ->headersPseudoOrder(new PseudoHeaderOrder([
                PseudoHeader::Method,
                PseudoHeader::Scheme,
                PseudoHeader::Path,
                PseudoHeader::Authority,
            ]))
            ->settingsOrder(new SettingsOrder($settings));

        if ($enablePush) {
            $builder->enablePush(false);
        }

        return $builder->build();
    }

    // HTTP/2: Safari 18.0 (2:0;3:100;4:2097152;8:1;9:1, pseudo msap, weight 256)
    private static function ciHttp2_180(): Http2Options
    {
        return Http2Options::builder()
            ->enablePush(false)
            ->maxConcurrentStreams(100)
            ->initialWindowSize(2097152)
            ->enableConnectProtocol(true)
            ->noRfc7540Priorities(true)
            ->initialConnWindowSize(10420225)
            ->headersStreamDependency(new StreamDependency(0, 256, false))
            ->headersPseudoOrder(new PseudoHeaderOrder([
                PseudoHeader::Method,
                PseudoHeader::Scheme,
                PseudoHeader::Authority,
                PseudoHeader::Path,
            ]))
            ->settingsOrder(new SettingsOrder([
                SettingId::EnablePush,
                SettingId::MaxConcurrentStreams,
                SettingId::InitialWindowSize,
                SettingId::EnableConnectProtocol,
                SettingId::NoRfc7540Priorities,
            ]))
            ->build();
    }

    // HTTP/2: Safari 18.4 (2:0;3:100;4:2097152;9:1, pseudo msap, weight 256)
    private static function ciHttp2_184(): Http2Options
    {
        return Http2Options::builder()
            ->enablePush(false)
            ->maxConcurrentStreams(100)
            ->initialWindowSize(2097152)
            ->noRfc7540Priorities(true)
            ->initialConnWindowSize(10420225)
            ->headersStreamDependency(new StreamDependency(0, 256, false))
            ->headersPseudoOrder(new PseudoHeaderOrder([
                PseudoHeader::Method,
                PseudoHeader::Scheme,
                PseudoHeader::Authority,
                PseudoHeader::Path,
            ]))
            ->settingsOrder(new SettingsOrder([
                SettingId::EnablePush,
                SettingId::MaxConcurrentStreams,
                SettingId::InitialWindowSize,
                SettingId::NoRfc7540Priorities,
            ]))
            ->build();
    }

    // HTTP/2: Safari 26.x (same as 18.4 + noRfc7540Priorities in HTTP/2 layer)
    private static function ciHttp2_26x(): Http2Options
    {
        return Http2Options::builder()
            ->enablePush(false)
            ->maxConcurrentStreams(100)
            ->initialWindowSize(2097152)
            ->noRfc7540Priorities(true)
            ->initialConnWindowSize(10420225)
            ->headersPseudoOrder(new PseudoHeaderOrder([
                PseudoHeader::Method,
                PseudoHeader::Scheme,
                PseudoHeader::Authority,
                PseudoHeader::Path,
            ]))
            ->settingsOrder(new SettingsOrder([
                SettingId::EnablePush,
                SettingId::MaxConcurrentStreams,
                SettingId::InitialWindowSize,
                SettingId::NoRfc7540Priorities,
            ]))
            ->build();
    }

    // Headers: Safari 15.x (no Sec-Fetch)
    /**
     * @return array<string, string>
     */
    private static function ciHeaders15x(string $userAgent, string $acceptLanguage): array
    {
        return [
            'User-Agent' => $userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => $acceptLanguage,
            'Accept-Encoding' => 'gzip, deflate, br',
        ];
    }

    private static function ciHeaderOrder15x(): OriginalHeaderMap
    {
        return new OriginalHeaderMap([
            'Host',
            'User-Agent',
            'Accept',
            'Accept-Language',
            'Accept-Encoding',
            'Connection',
            'Cookie',
        ]);
    }

    // Headers: Safari 17.x (with Sec-Fetch, no Priority)
    /**
     * @return array<string, string>
     */
    private static function ciHeaders17x(string $userAgent): array
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Sec-Fetch-Site' => 'none',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Sec-Fetch-Mode' => 'navigate',
            'User-Agent' => $userAgent,
            'Accept-Language' => 'en-US,en;q=0.9',
            'Sec-Fetch-Dest' => 'document',
        ];
    }

    private static function ciHeaderOrder17x(): OriginalHeaderMap
    {
        return new OriginalHeaderMap([
            'Host',
            'Accept',
            'Sec-Fetch-Site',
            'Accept-Encoding',
            'Sec-Fetch-Mode',
            'User-Agent',
            'Accept-Language',
            'Sec-Fetch-Dest',
            'Connection',
            'Cookie',
        ]);
    }

    // Headers: Safari 18.x+ (Sec-Fetch + Priority, lowercase header names)
    /**
     * @return array<string, string>
     */
    private static function ciHeaders18x(string $userAgent, string $encoding): array
    {
        return [
            'sec-fetch-dest' => 'document',
            'User-Agent' => $userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'sec-fetch-site' => 'none',
            'sec-fetch-mode' => 'navigate',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Priority' => 'u=0, i',
            'Accept-Encoding' => $encoding,
        ];
    }

    private static function ciHeaderOrder18x(): OriginalHeaderMap
    {
        return new OriginalHeaderMap([
            'Host',
            'sec-fetch-dest',
            'User-Agent',
            'Accept',
            'sec-fetch-site',
            'sec-fetch-mode',
            'Accept-Language',
            'Priority',
            'Accept-Encoding',
            'Connection',
            'Cookie',
        ]);
    }
}
