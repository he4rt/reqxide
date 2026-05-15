<?php

declare(strict_types=1);

namespace Reqxide\Tls;

readonly class TlsOptions
{
    /**
     * @param  list<AlpnProtocol>|null  $alpnProtocols
     * @param  list<AlpsProtocol>|null  $alpsProtocols
     * @param  list<KeyShare>|null  $keyShares
     * @param  list<CertificateCompressorInterface>|null  $certificateCompressors
     * @param  list<int>|null  $extensionPermutation
     */
    public function __construct(
        public ?array $alpnProtocols = null,
        public ?array $alpsProtocols = null,
        public bool $alpsUseNewCodepoint = false,
        public bool $sessionTicket = true,
        public ?TlsVersion $minTlsVersion = null,
        public ?TlsVersion $maxTlsVersion = null,
        public bool $preSharedKey = false,
        public bool $enableEchGrease = false,
        public ?bool $permuteExtensions = null,
        public ?bool $greaseEnabled = null,
        public bool $enableOcspStapling = false,
        public bool $enableSignedCertTimestamps = false,
        public ?int $recordSizeLimit = null,
        public bool $pskSkipSessionTicket = false,
        public ?array $keyShares = null,
        public bool $pskDheKe = true,
        public bool $renegotiation = true,
        public ?string $delegatedCredentials = null,
        public ?string $curvesList = null,
        public ?string $sigalgsList = null,
        public ?string $cipherList = null,
        public ?bool $preserveTls13CipherList = null,
        public ?array $certificateCompressors = null,
        public ?array $extensionPermutation = null,
        public ?bool $aesHwOverride = null,
        public bool $randomAesHwOverride = false,
    ) {}

    public static function builder(): TlsOptionsBuilder
    {
        return new TlsOptionsBuilder;
    }
}
