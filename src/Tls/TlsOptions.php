<?php

declare(strict_types=1);

namespace Reqxide\Tls;

readonly class TlsOptions
{
    /**
     * @param  list<AlpnProtocol>|null  $alpnProtocols
     * @param  list<KeyShare>|null  $keyShares
     * @param  list<int>|null  $extensionPermutation
     */
    public function __construct(
        public ?array $alpnProtocols = null,
        public bool $sessionTicket = true,
        public ?TlsVersion $minTlsVersion = null,
        public ?TlsVersion $maxTlsVersion = null,
        public bool $enableEchGrease = false,
        public ?bool $permuteExtensions = null,
        public ?bool $greaseEnabled = null,
        public bool $enableOcspStapling = false,
        public bool $enableSignedCertTimestamps = false,
        public ?int $recordSizeLimit = null,
        public ?array $keyShares = null,
        public ?string $curvesList = null,
        public ?string $sigalgsList = null,
        public ?string $cipherList = null,
        public ?array $extensionPermutation = null,
        public ?bool $aesHwOverride = null,
    ) {}

    public static function builder(): TlsOptionsBuilder
    {
        return new TlsOptionsBuilder;
    }
}
