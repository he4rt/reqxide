<?php

declare(strict_types=1);

namespace Reqxide\Tls;

final class TlsOptionsBuilder
{
    /** @var list<AlpnProtocol>|null */
    private ?array $alpnProtocols = null;

    /** @var list<AlpsProtocol>|null */
    private ?array $alpsProtocols = null;

    private bool $alpsUseNewCodepoint = false;

    private bool $sessionTicket = true;

    private ?TlsVersion $minTlsVersion = null;

    private ?TlsVersion $maxTlsVersion = null;

    private bool $preSharedKey = false;

    private bool $enableEchGrease = false;

    private ?bool $permuteExtensions = null;

    private ?bool $greaseEnabled = null;

    private bool $enableOcspStapling = false;

    private bool $enableSignedCertTimestamps = false;

    private ?int $recordSizeLimit = null;

    private bool $pskSkipSessionTicket = false;

    /** @var list<KeyShare>|null */
    private ?array $keyShares = null;

    private bool $pskDheKe = true;

    private bool $renegotiation = true;

    private ?string $delegatedCredentials = null;

    private ?string $curvesList = null;

    private ?string $sigalgsList = null;

    private ?string $cipherList = null;

    private ?bool $preserveTls13CipherList = null;

    /** @var list<CertificateCompressor>|null */
    private ?array $certificateCompressors = null;

    /** @var list<int>|null */
    private ?array $extensionPermutation = null;

    private ?bool $aesHwOverride = null;

    private bool $randomAesHwOverride = false;

    /** @param  list<AlpnProtocol>  $protocols */
    public function alpnProtocols(array $protocols): self
    {
        $this->alpnProtocols = $protocols;

        return $this;
    }

    /** @param  list<AlpsProtocol>  $protocols */
    public function alpsProtocols(array $protocols): self
    {
        $this->alpsProtocols = $protocols;

        return $this;
    }

    public function alpsUseNewCodepoint(bool $enabled): self
    {
        $this->alpsUseNewCodepoint = $enabled;

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

    public function preSharedKey(bool $enabled): self
    {
        $this->preSharedKey = $enabled;

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

    public function pskSkipSessionTicket(bool $skip): self
    {
        $this->pskSkipSessionTicket = $skip;

        return $this;
    }

    /** @param  list<KeyShare>  $keyShares */
    public function keyShares(array $keyShares): self
    {
        $this->keyShares = $keyShares;

        return $this;
    }

    public function pskDheKe(bool $enabled): self
    {
        $this->pskDheKe = $enabled;

        return $this;
    }

    public function renegotiation(bool $enabled): self
    {
        $this->renegotiation = $enabled;

        return $this;
    }

    public function delegatedCredentials(string $credentials): self
    {
        $this->delegatedCredentials = $credentials;

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

    public function preserveTls13CipherList(bool $preserve): self
    {
        $this->preserveTls13CipherList = $preserve;

        return $this;
    }

    /** @param  list<CertificateCompressor>  $compressors */
    public function certificateCompressors(array $compressors): self
    {
        $this->certificateCompressors = $compressors;

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

    public function randomAesHwOverride(bool $override): self
    {
        $this->randomAesHwOverride = $override;

        return $this;
    }

    public function build(): TlsOptions
    {
        return new TlsOptions(
            alpnProtocols: $this->alpnProtocols,
            alpsProtocols: $this->alpsProtocols,
            alpsUseNewCodepoint: $this->alpsUseNewCodepoint,
            sessionTicket: $this->sessionTicket,
            minTlsVersion: $this->minTlsVersion,
            maxTlsVersion: $this->maxTlsVersion,
            preSharedKey: $this->preSharedKey,
            enableEchGrease: $this->enableEchGrease,
            permuteExtensions: $this->permuteExtensions,
            greaseEnabled: $this->greaseEnabled,
            enableOcspStapling: $this->enableOcspStapling,
            enableSignedCertTimestamps: $this->enableSignedCertTimestamps,
            recordSizeLimit: $this->recordSizeLimit,
            pskSkipSessionTicket: $this->pskSkipSessionTicket,
            keyShares: $this->keyShares,
            pskDheKe: $this->pskDheKe,
            renegotiation: $this->renegotiation,
            delegatedCredentials: $this->delegatedCredentials,
            curvesList: $this->curvesList,
            sigalgsList: $this->sigalgsList,
            cipherList: $this->cipherList,
            preserveTls13CipherList: $this->preserveTls13CipherList,
            certificateCompressors: $this->certificateCompressors,
            extensionPermutation: $this->extensionPermutation,
            aesHwOverride: $this->aesHwOverride,
            randomAesHwOverride: $this->randomAesHwOverride,
        );
    }
}
