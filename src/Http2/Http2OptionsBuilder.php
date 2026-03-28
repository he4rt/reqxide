<?php

declare(strict_types=1);

namespace Reqxide\Http2;

final class Http2OptionsBuilder
{
    private bool $adaptiveWindow = false;

    private ?int $initialStreamId = null;

    private int $initialWindowSize = 65535;

    private int $initialConnWindowSize = 65535;

    private ?int $maxFrameSize = null;

    private ?int $headerTableSize = null;

    private ?bool $enablePush = null;

    private ?int $maxHeaderListSize = null;

    private ?int $maxConcurrentStreams = null;

    private ?bool $enableConnectProtocol = null;

    private ?bool $noRfc7540Priorities = null;

    private ?PseudoHeaderOrder $headersPseudoOrder = null;

    private ?SettingsOrder $settingsOrder = null;

    private ?StreamDependency $headersStreamDependency = null;

    /** @var list<Priority>|null */
    private ?array $priorities = null;

    public function adaptiveWindow(bool $enabled): self
    {
        $this->adaptiveWindow = $enabled;

        return $this;
    }

    public function initialStreamId(int $id): self
    {
        $this->initialStreamId = $id;

        return $this;
    }

    public function initialWindowSize(int $size): self
    {
        $this->initialWindowSize = $size;

        return $this;
    }

    public function initialConnWindowSize(int $size): self
    {
        $this->initialConnWindowSize = $size;

        return $this;
    }

    public function maxFrameSize(int $size): self
    {
        $this->maxFrameSize = $size;

        return $this;
    }

    public function headerTableSize(int $size): self
    {
        $this->headerTableSize = $size;

        return $this;
    }

    public function enablePush(bool $enabled): self
    {
        $this->enablePush = $enabled;

        return $this;
    }

    public function maxHeaderListSize(int $size): self
    {
        $this->maxHeaderListSize = $size;

        return $this;
    }

    public function maxConcurrentStreams(int $streams): self
    {
        $this->maxConcurrentStreams = $streams;

        return $this;
    }

    public function enableConnectProtocol(bool $enabled): self
    {
        $this->enableConnectProtocol = $enabled;

        return $this;
    }

    public function noRfc7540Priorities(bool $disabled): self
    {
        $this->noRfc7540Priorities = $disabled;

        return $this;
    }

    public function headersPseudoOrder(PseudoHeaderOrder $order): self
    {
        $this->headersPseudoOrder = $order;

        return $this;
    }

    public function settingsOrder(SettingsOrder $order): self
    {
        $this->settingsOrder = $order;

        return $this;
    }

    public function headersStreamDependency(StreamDependency $dependency): self
    {
        $this->headersStreamDependency = $dependency;

        return $this;
    }

    /** @param  list<Priority>  $priorities */
    public function priorities(array $priorities): self
    {
        $this->priorities = $priorities;

        return $this;
    }

    public function build(): Http2Options
    {
        return new Http2Options(
            adaptiveWindow: $this->adaptiveWindow,
            initialStreamId: $this->initialStreamId,
            initialWindowSize: $this->initialWindowSize,
            initialConnWindowSize: $this->initialConnWindowSize,
            maxFrameSize: $this->maxFrameSize,
            headerTableSize: $this->headerTableSize,
            enablePush: $this->enablePush,
            maxHeaderListSize: $this->maxHeaderListSize,
            maxConcurrentStreams: $this->maxConcurrentStreams,
            enableConnectProtocol: $this->enableConnectProtocol,
            noRfc7540Priorities: $this->noRfc7540Priorities,
            headersPseudoOrder: $this->headersPseudoOrder,
            settingsOrder: $this->settingsOrder,
            headersStreamDependency: $this->headersStreamDependency,
            priorities: $this->priorities,
        );
    }
}
