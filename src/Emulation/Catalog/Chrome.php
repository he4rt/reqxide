<?php

declare(strict_types=1);

namespace Reqxide\Emulation\Catalog;

use Reqxide\Emulation\ChromeSecChUa;
use Reqxide\Emulation\Profile;
use Reqxide\Http1\OriginalHeaderMap;
use Reqxide\Http2\Http2Options;
use Reqxide\Http2\PseudoHeader;
use Reqxide\Http2\PseudoHeaderOrder;
use Reqxide\Http2\SettingId;
use Reqxide\Http2\SettingsOrder;
use Reqxide\Http2\StreamDependency;
use Reqxide\Tls\AlpnProtocol;
use Reqxide\Tls\BrotliCompressor;
use Reqxide\Tls\KeyShare;
use Reqxide\Tls\TlsOptions;
use Reqxide\Tls\TlsVersion;

final class Chrome
{
    // wreq cipher list (9 ciphers) — used by existing v128-v131 profiles
    private const string CIPHER_LIST = 'TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305';

    // curl_impersonate cipher list (15 ciphers) — used by all curl_impersonate profiles
    private const string CI_CIPHER_LIST = 'TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-RSA-AES128-SHA:ECDHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA:AES256-SHA';

    private const string SIGALGS_LIST = 'ecdsa_secp256r1_sha256:rsa_pss_rsae_sha256:rsa_pkcs1_sha256:ecdsa_secp384r1_sha384:rsa_pss_rsae_sha384:rsa_pkcs1_sha384:rsa_pss_rsae_sha512:rsa_pkcs1_sha512';

    private const string CURVES_DEFAULT = 'X25519:P-256:P-384';

    // ─── wreq profiles (v128-v131) — unchanged ───

    public static function v131(): Profile
    {
        return self::buildProfile(
            version: '131.0.0.0',
            curvesList: 'X25519MLKEM768:X25519:P-256:P-384',
            keyShares: [KeyShare::X25519MLKEM768, KeyShare::X25519],
            secChUa: ChromeSecChUa::generate(131),
        );
    }

    public static function v130(): Profile
    {
        return self::buildProfile(
            version: '130.0.0.0',
            curvesList: 'X25519MLKEM768:X25519:P-256:P-384',
            keyShares: [KeyShare::X25519MLKEM768, KeyShare::X25519],
            secChUa: ChromeSecChUa::generate(130),
        );
    }

    public static function v129(): Profile
    {
        return self::buildProfile(
            version: '129.0.0.0',
            curvesList: 'X25519:P-256:P-384',
            keyShares: [KeyShare::X25519],
            secChUa: ChromeSecChUa::generate(129),
        );
    }

    public static function v128(): Profile
    {
        return self::buildProfile(
            version: '128.0.0.0',
            curvesList: 'X25519:P-256:P-384',
            keyShares: [KeyShare::X25519],
            secChUa: ChromeSecChUa::generate(128),
        );
    }

    // ─── curl_impersonate profiles ───

    // Era 1: Chrome 99-106 (Windows, !permute, !ech, H2 without EnablePush)
    public static function v99(): Profile
    {
        return self::ciProfile(
            version: '99.0.4844.51',
            secChUa: '" Not A;Brand";v="99", "Chromium";v="99", "Google Chrome";v="99"',
            platform: 'Windows',
            tls: self::ciTls(permute: false, ech: false, alpsNew: false),
            http2: self::ciHttp2(withEnablePush: false, withMaxConcurrent: true),
            acceptQuality: '0.9',
            encoding: 'gzip, deflate, br',
            priority: false,
        );
    }

    public static function v100(): Profile
    {
        return self::ciProfile(
            version: '100.0.4896.75',
            secChUa: '" Not A;Brand";v="99", "Chromium";v="100", "Google Chrome";v="100"',
            platform: 'Windows',
            tls: self::ciTls(permute: false, ech: false, alpsNew: false),
            http2: self::ciHttp2(withEnablePush: false, withMaxConcurrent: true),
            acceptQuality: '0.9',
            encoding: 'gzip, deflate, br',
            priority: false,
        );
    }

    public static function v101(): Profile
    {
        return self::ciProfile(
            version: '101.0.4951.67',
            secChUa: '" Not A;Brand";v="99", "Chromium";v="101", "Google Chrome";v="101"',
            platform: 'Windows',
            tls: self::ciTls(permute: false, ech: false, alpsNew: false),
            http2: self::ciHttp2(withEnablePush: false, withMaxConcurrent: true),
            acceptQuality: '0.9',
            encoding: 'gzip, deflate, br',
            priority: false,
        );
    }

    public static function v104(): Profile
    {
        return self::ciProfile(
            version: '104.0.0.0',
            secChUa: '"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
            platform: 'Windows',
            tls: self::ciTls(permute: false, ech: false, alpsNew: false),
            http2: self::ciHttp2(withEnablePush: false, withMaxConcurrent: true),
            acceptQuality: '0.9',
            encoding: 'gzip, deflate, br',
            priority: false,
        );
    }

    // Era 1b: Chrome 107 (Windows, !permute, !ech, H2 with EnablePush:0)
    public static function v107(): Profile
    {
        return self::ciProfile(
            version: '107.0.0.0',
            secChUa: ChromeSecChUa::generate(107),
            platform: 'Windows',
            tls: self::ciTls(permute: false, ech: false, alpsNew: false),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: true),
            acceptQuality: '0.9',
            encoding: 'gzip, deflate, br',
            priority: false,
        );
    }

    // Era 2: Chrome 110-116 (Windows, +permute, !ech)
    public static function v110(): Profile
    {
        return self::ciProfile(
            version: '110.0.0.0',
            secChUa: ChromeSecChUa::generate(110),
            platform: 'Windows',
            tls: self::ciTls(permute: true, ech: false, alpsNew: false),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: true),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br',
            priority: false,
        );
    }

    public static function v116(): Profile
    {
        return self::ciProfile(
            version: '116.0.0.0',
            secChUa: ChromeSecChUa::generate(116),
            platform: 'Windows',
            tls: self::ciTls(permute: true, ech: false, alpsNew: false),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: true),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br',
            priority: false,
        );
    }

    // Era 3: Chrome 119-123 (macOS, +permute, +ech, no maxConcurrent)
    public static function v119(): Profile
    {
        return self::ciProfile(
            version: '119.0.0.0',
            secChUa: ChromeSecChUa::generate(119),
            platform: 'macOS',
            tls: self::ciTls(permute: true, ech: true, alpsNew: false),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: false),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br',
            priority: false,
        );
    }

    public static function v120(): Profile
    {
        return self::ciProfile(
            version: '120.0.0.0',
            secChUa: ChromeSecChUa::generate(120),
            platform: 'macOS',
            tls: self::ciTls(permute: true, ech: true, alpsNew: false),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: false),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br',
            priority: false,
        );
    }

    public static function v123(): Profile
    {
        return self::ciProfile(
            version: '123.0.0.0',
            secChUa: ChromeSecChUa::generate(123),
            platform: 'macOS',
            tls: self::ciTls(permute: true, ech: true, alpsNew: false),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: false),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br, zstd',
            priority: false,
        );
    }

    // Era 4: Chrome 124 (macOS, Kyber curves, +Priority)
    public static function v124(): Profile
    {
        return self::ciProfile(
            version: '124.0.0.0',
            secChUa: ChromeSecChUa::generate(124),
            platform: 'macOS',
            tls: self::ciTls(
                permute: true,
                ech: true,
                alpsNew: false,
                curves: 'X25519Kyber768Draft00:X25519:P-256:P-384',
                keyShares: [KeyShare::X25519Kyber768Draft00, KeyShare::X25519],
            ),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: false),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br, zstd',
            priority: true,
        );
    }

    // Era 5: Chrome 131+ from curl_impersonate (macOS, MLKEM, !alpsNew for 131, +alpsNew for 133a+)
    public static function v133a(): Profile
    {
        return self::ciProfile(
            version: '133.0.0.0',
            secChUa: ChromeSecChUa::generate(133),
            platform: 'macOS',
            tls: self::ciTls(
                permute: true,
                ech: true,
                alpsNew: true,
                curves: 'X25519MLKEM768:X25519:P-256:P-384',
                keyShares: [KeyShare::X25519MLKEM768, KeyShare::X25519],
            ),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: false),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br, zstd',
            priority: true,
        );
    }

    public static function v136(): Profile
    {
        return self::ciProfile(
            version: '136.0.0.0',
            secChUa: ChromeSecChUa::generate(136),
            platform: 'macOS',
            tls: self::ciTls(
                permute: true,
                ech: true,
                alpsNew: true,
                curves: 'X25519MLKEM768:X25519:P-256:P-384',
                keyShares: [KeyShare::X25519MLKEM768, KeyShare::X25519],
            ),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: false),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br, zstd',
            priority: true,
        );
    }

    // @todo verify TLS params against curl_impersonate source (--impersonate only)
    public static function v142(): Profile
    {
        return self::ciProfile(
            version: '142.0.0.0',
            secChUa: ChromeSecChUa::generate(142),
            platform: 'macOS',
            tls: self::ciTls(
                permute: true,
                ech: true,
                alpsNew: true,
                curves: 'X25519MLKEM768:X25519:P-256:P-384',
                keyShares: [KeyShare::X25519MLKEM768, KeyShare::X25519],
            ),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: false),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br, zstd',
            priority: true,
        );
    }

    // @todo verify TLS params against curl_impersonate source (--impersonate only)
    public static function v145(): Profile
    {
        return self::ciProfile(
            version: '145.0.0.0',
            secChUa: ChromeSecChUa::generate(145),
            platform: 'macOS',
            tls: self::ciTls(
                permute: true,
                ech: true,
                alpsNew: true,
                curves: 'X25519MLKEM768:X25519:P-256:P-384',
                keyShares: [KeyShare::X25519MLKEM768, KeyShare::X25519],
            ),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: false),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br, zstd',
            priority: true,
        );
    }

    // @todo verify TLS params against curl_impersonate source (--impersonate only)
    public static function v146(): Profile
    {
        return self::ciProfile(
            version: '146.0.0.0',
            secChUa: ChromeSecChUa::generate(146),
            platform: 'macOS',
            tls: self::ciTls(
                permute: true,
                ech: true,
                alpsNew: true,
                curves: 'X25519MLKEM768:X25519:P-256:P-384',
                keyShares: [KeyShare::X25519MLKEM768, KeyShare::X25519],
            ),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: false),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br, zstd',
            priority: true,
        );
    }

    public static function v147(): Profile
    {
        return self::ciProfile(
            version: '147.0.0.0',
            secChUa: ChromeSecChUa::generate(147),
            platform: 'macOS',
            tls: self::ciTls(
                permute: true,
                ech: true,
                alpsNew: true,
                curves: 'X25519MLKEM768:X25519:P-256:P-384',
                keyShares: [KeyShare::X25519MLKEM768, KeyShare::X25519],
            ),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: false),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br, zstd',
            priority: true,
        );
    }

    // ─── Chrome Android ───

    public static function v99Android(): Profile
    {
        return self::ciProfile(
            version: '99.0.4844.58',
            secChUa: '" Not A;Brand";v="99", "Chromium";v="99", "Google Chrome";v="99"',
            platform: 'Android',
            tls: self::ciTls(permute: false, ech: false, alpsNew: false),
            http2: self::ciHttp2(withEnablePush: false, withMaxConcurrent: true),
            acceptQuality: '0.9',
            encoding: 'gzip, deflate, br',
            priority: false,
            mobile: true,
            userAgent: 'Mozilla/5.0 (Linux; Android 12; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.58 Mobile Safari/537.36',
        );
    }

    public static function v131Android(): Profile
    {
        return self::ciProfile(
            version: '131.0.0.0',
            secChUa: ChromeSecChUa::generate(131),
            platform: 'Android',
            tls: self::ciTls(permute: true, ech: true, alpsNew: false),
            http2: self::ciHttp2(withEnablePush: true, withMaxConcurrent: false),
            acceptQuality: '0.7',
            encoding: 'gzip, deflate, br, zstd',
            priority: true,
            mobile: false,
            userAgent: 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36',
        );
    }

    // ─── wreq private helpers (unchanged) ───

    /**
     * @param  list<KeyShare>  $keyShares
     */
    private static function baseTlsOptions(string $curvesList, array $keyShares): TlsOptions
    {
        return TlsOptions::builder()
            ->minTlsVersion(TlsVersion::TLS_1_2)
            ->maxTlsVersion(TlsVersion::TLS_1_3)
            ->cipherList(self::CIPHER_LIST)
            ->sigalgsList(self::SIGALGS_LIST)
            ->alpnProtocols([AlpnProtocol::Http2, AlpnProtocol::Http1])
            ->enableOcspStapling(true)
            ->enableSignedCertTimestamps(true)
            ->greaseEnabled(true)
            ->permuteExtensions(true)
            ->enableEchGrease(true)
            ->sessionTicket(true)
            ->curvesList($curvesList)
            ->keyShares($keyShares)
            ->build();
    }

    private static function baseHttp2Options(): Http2Options
    {
        return Http2Options::builder()
            ->headerTableSize(65536)
            ->enablePush(false)
            ->initialWindowSize(6291456)
            ->maxHeaderListSize(262144)
            ->initialConnWindowSize(15663105)
            ->headersPseudoOrder(new PseudoHeaderOrder([
                PseudoHeader::Method,
                PseudoHeader::Authority,
                PseudoHeader::Scheme,
                PseudoHeader::Path,
            ]))
            ->settingsOrder(new SettingsOrder([
                SettingId::HeaderTableSize,
                SettingId::EnablePush,
                SettingId::InitialWindowSize,
                SettingId::MaxHeaderListSize,
            ]))
            ->build();
    }

    /**
     * @return array<string, string>
     */
    private static function baseHeaders(string $version, string $secChUa): array
    {
        return [
            'sec-ch-ua' => $secChUa,
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/'.$version.' Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, br, zstd',
            'Accept-Language' => 'en-US,en;q=0.9',
        ];
    }

    private static function baseHeaderOrder(): OriginalHeaderMap
    {
        return new OriginalHeaderMap([
            'Host',
            'Connection',
            'sec-ch-ua',
            'sec-ch-ua-mobile',
            'sec-ch-ua-platform',
            'Upgrade-Insecure-Requests',
            'User-Agent',
            'Accept',
            'Accept-Encoding',
            'Accept-Language',
            'Cookie',
        ]);
    }

    /**
     * @param  list<KeyShare>  $keyShares
     */
    private static function buildProfile(
        string $version,
        string $curvesList,
        array $keyShares,
        string $secChUa,
    ): Profile {
        return new Profile(
            tlsOptions: self::baseTlsOptions($curvesList, $keyShares),
            http2Options: self::baseHttp2Options(),
            defaultHeaders: self::baseHeaders($version, $secChUa),
            originalHeaderMap: self::baseHeaderOrder(),
        );
    }

    // ─── curl_impersonate private helpers ───

    /**
     * @param  list<KeyShare>|null  $keyShares
     */
    private static function ciTls(
        bool $permute,
        bool $ech,
        bool $alpsNew,
        string $curves = self::CURVES_DEFAULT,
        ?array $keyShares = null,
    ): TlsOptions {
        $builder = TlsOptions::builder()
            ->minTlsVersion(TlsVersion::TLS_1_2)
            ->maxTlsVersion(TlsVersion::TLS_1_3)
            ->cipherList(self::CI_CIPHER_LIST)
            ->sigalgsList(self::SIGALGS_LIST)
            ->curvesList($curves)
            ->alpnProtocols([AlpnProtocol::Http2, AlpnProtocol::Http1])
            ->enableOcspStapling(true)
            ->enableSignedCertTimestamps(true)
            ->greaseEnabled(true)
            ->permuteExtensions($permute)
            ->enableEchGrease($ech)
            ->alpsUseNewCodepoint($alpsNew)
            ->sessionTicket(true)
            ->certificateCompressors([new BrotliCompressor]);

        if ($keyShares !== null) {
            $builder->keyShares($keyShares);
        } else {
            $builder->keyShares([KeyShare::X25519]);
        }

        return $builder->build();
    }

    private static function ciHttp2(bool $withEnablePush, bool $withMaxConcurrent): Http2Options
    {
        $builder = Http2Options::builder()
            ->headerTableSize(65536)
            ->initialWindowSize(6291456)
            ->maxHeaderListSize(262144)
            ->maxFrameSize(16384)
            ->initialConnWindowSize(15663105)
            ->headersStreamDependency(new StreamDependency(0, 256, true))
            ->headersPseudoOrder(new PseudoHeaderOrder([
                PseudoHeader::Method,
                PseudoHeader::Authority,
                PseudoHeader::Scheme,
                PseudoHeader::Path,
            ]));

        if ($withEnablePush) {
            $builder->enablePush(false);
        }

        if ($withMaxConcurrent) {
            $builder->maxConcurrentStreams(1000);
        }

        // Build settings order to match curl_impersonate
        $settings = [SettingId::HeaderTableSize];
        if ($withEnablePush) {
            $settings[] = SettingId::EnablePush;
        }

        if ($withMaxConcurrent) {
            $settings[] = SettingId::MaxConcurrentStreams;
        }

        $settings[] = SettingId::InitialWindowSize;
        $settings[] = SettingId::MaxHeaderListSize;

        $builder->settingsOrder(new SettingsOrder($settings));

        return $builder->build();
    }

    /**
     * @return array<string, string>
     */
    private static function ciHeaders(
        string $version,
        string $secChUa,
        string $platform,
        string $acceptQuality,
        string $encoding,
        bool $priority,
        bool $mobile,
        ?string $userAgent,
    ): array {
        $ua = $userAgent ?? match ($platform) {
            'macOS' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/'.$version.' Safari/537.36',
            default => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/'.$version.' Safari/537.36',
        };

        $headers = [
            'sec-ch-ua' => $secChUa,
            'sec-ch-ua-mobile' => $mobile ? '?1' : '?0',
            'sec-ch-ua-platform' => '"'.$platform.'"',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => $ua,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q='.$acceptQuality,
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-User' => '?1',
            'Sec-Fetch-Dest' => 'document',
            'Accept-Encoding' => $encoding,
            'Accept-Language' => 'en-US,en;q=0.9',
        ];

        if ($priority) {
            $headers['Priority'] = 'u=0, i';
        }

        return $headers;
    }

    private static function ciHeaderOrder(bool $priority): OriginalHeaderMap
    {
        $order = [
            'Host',
            'Connection',
            'sec-ch-ua',
            'sec-ch-ua-mobile',
            'sec-ch-ua-platform',
            'Upgrade-Insecure-Requests',
            'User-Agent',
            'Accept',
            'Sec-Fetch-Site',
            'Sec-Fetch-Mode',
            'Sec-Fetch-User',
            'Sec-Fetch-Dest',
            'Accept-Encoding',
            'Accept-Language',
        ];

        if ($priority) {
            $order[] = 'Priority';
        }

        $order[] = 'Cookie';

        return new OriginalHeaderMap($order);
    }

    private static function ciProfile(
        string $version,
        string $secChUa,
        string $platform,
        TlsOptions $tls,
        Http2Options $http2,
        string $acceptQuality,
        string $encoding,
        bool $priority,
        bool $mobile = false,
        ?string $userAgent = null,
    ): Profile {
        return new Profile(
            tlsOptions: $tls,
            http2Options: $http2,
            defaultHeaders: self::ciHeaders($version, $secChUa, $platform, $acceptQuality, $encoding, $priority, $mobile, $userAgent),
            originalHeaderMap: self::ciHeaderOrder($priority),
        );
    }
}
