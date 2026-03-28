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

final class Chrome
{
    private const CIPHER_LIST = 'TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305';

    private const SIGALGS_LIST = 'ecdsa_secp256r1_sha256:rsa_pss_rsae_sha256:rsa_pkcs1_sha256:ecdsa_secp384r1_sha384:rsa_pss_rsae_sha384:rsa_pkcs1_sha384:rsa_pss_rsae_sha512:rsa_pkcs1_sha512';

    public static function v131(): Profile
    {
        return self::buildProfile(
            version: '131.0.0.0',
            curvesList: 'X25519MLKEM768:X25519:P-256:P-384',
            keyShares: [KeyShare::X25519MLKEM768, KeyShare::X25519],
            secChUa: '"Chromium";v="131", "Not_A Brand";v="24"',
        );
    }

    public static function v130(): Profile
    {
        return self::buildProfile(
            version: '130.0.0.0',
            curvesList: 'X25519MLKEM768:X25519:P-256:P-384',
            keyShares: [KeyShare::X25519MLKEM768, KeyShare::X25519],
            secChUa: '"Chromium";v="130", "Not_A Brand";v="24"',
        );
    }

    public static function v129(): Profile
    {
        return self::buildProfile(
            version: '129.0.0.0',
            curvesList: 'X25519:P-256:P-384',
            keyShares: [KeyShare::X25519],
            secChUa: '"Chromium";v="129", "Not_A Brand";v="24"',
        );
    }

    public static function v128(): Profile
    {
        return self::buildProfile(
            version: '128.0.0.0',
            curvesList: 'X25519:P-256:P-384',
            keyShares: [KeyShare::X25519],
            secChUa: '"Chromium";v="128", "Not_A Brand";v="24"',
        );
    }

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
            ->maxConcurrentStreams(1000)
            ->initialWindowSize(6291456)
            ->maxFrameSize(16384)
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
}
