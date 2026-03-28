<?php

declare(strict_types=1);

namespace Reqxide\Tls;

final class TlsOptionsBuilder
{
    /** @var list<AlpnProtocol>|null */
    private ?array $alpnProtocols = null;

    private bool $sessionTicket = true;

    private ?TlsVersion $minTlsVersion = null;

    private ?TlsVersion $maxTlsVersion = null;

    private bool $enableEchGrease = false;

    private ?bool $permuteExtensions = null;

    private ?bool $greaseEnabled = null;

    private bool $enableOcspStapling = false;

    private bool $enableSignedCertTimestamps = false;

    private ?int $recordSizeLimit = null;

    /** @var list<KeyShare>|null */
    private ?array $keyShares = null;

    private ?string $curvesList = null;

    private ?string $sigalgsList = null;

    private ?string $cipherList = null;

    /** @var list<int>|null */
    private ?array $extensionPermutation = null;

    private ?bool $aesHwOverride = null;

    /** @param  list<AlpnProtocol>  $protocols */
    public function alpnProtocols(array $protocols): self
    {
        $this->alpnProtocols = $protocols;

        return $this;
    }

    public function sessionTicket(bool $enabled): self
    {
        $this->sessionTicket = $enabled;

        return $this;
    }

    public function minTlsVersion(TlsVersion $version): self
    {
        $this->minTlsVersion = $version;

        return $this;
    }

    public function maxTlsVersion(TlsVersion $version): self
    {
        $this->maxTlsVersion = $version;

        return $this;
    }

    public function enableEchGrease(bool $enabled): self
    {
        $this->enableEchGrease = $enabled;

        return $this;
    }

    public function permuteExtensions(bool $enabled): self
    {
        $this->permuteExtensions = $enabled;

        return $this;
    }

    public function greaseEnabled(bool $enabled): self
    {
        $this->greaseEnabled = $enabled;

        return $this;
    }

    public function enableOcspStapling(bool $enabled): self
    {
        $this->enableOcspStapling = $enabled;

        return $this;
    }

    public function enableSignedCertTimestamps(bool $enabled): self
    {
        $this->enableSignedCertTimestamps = $enabled;

        return $this;
    }

    public function recordSizeLimit(int $limit): self
    {
        $this->recordSizeLimit = $limit;

        return $this;
    }

    /** @param  list<KeyShare>  $keyShares */
    public function keyShares(array $keyShares): self
    {
        $this->keyShares = $keyShares;

        return $this;
    }

    public function curvesList(string $curves): self
    {
        $this->curvesList = $curves;

        return $this;
    }

    public function sigalgsList(string $sigalgs): self
    {
        $this->sigalgsList = $sigalgs;

        return $this;
    }

    public function cipherList(string $ciphers): self
    {
        $this->cipherList = $ciphers;

        return $this;
    }

    /** @param  list<int>  $permutation */
    public function extensionPermutation(array $permutation): self
    {
        $this->extensionPermutation = $permutation;

        return $this;
    }

    public function aesHwOverride(bool $override): self
    {
        $this->aesHwOverride = $override;

        return $this;
    }

    public function build(): TlsOptions
    {
        return new TlsOptions(
            alpnProtocols: $this->alpnProtocols,
            sessionTicket: $this->sessionTicket,
            minTlsVersion: $this->minTlsVersion,
            maxTlsVersion: $this->maxTlsVersion,
            enableEchGrease: $this->enableEchGrease,
            permuteExtensions: $this->permuteExtensions,
            greaseEnabled: $this->greaseEnabled,
            enableOcspStapling: $this->enableOcspStapling,
            enableSignedCertTimestamps: $this->enableSignedCertTimestamps,
            recordSizeLimit: $this->recordSizeLimit,
            keyShares: $this->keyShares,
            curvesList: $this->curvesList,
            sigalgsList: $this->sigalgsList,
            cipherList: $this->cipherList,
            extensionPermutation: $this->extensionPermutation,
            aesHwOverride: $this->aesHwOverride,
        );
    }
}
