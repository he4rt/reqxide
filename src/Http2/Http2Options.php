<?php

declare(strict_types=1);

namespace Reqxide\Http2;

readonly class Http2Options
{
    /** @param  list<Priority>|null  $priorities */
    public function __construct(
        public bool $adaptiveWindow = false,
        public ?int $initialStreamId = null,
        public int $initialWindowSize = 65535,
        public int $initialConnWindowSize = 65535,
        public ?int $maxFrameSize = null,
        public ?int $headerTableSize = null,
        public ?bool $enablePush = null,
        public ?int $maxHeaderListSize = null,
        public ?int $maxConcurrentStreams = null,
        public ?bool $enableConnectProtocol = null,
        public ?bool $noRfc7540Priorities = null,
        public ?PseudoHeaderOrder $headersPseudoOrder = null,
        public ?SettingsOrder $settingsOrder = null,
        public ?StreamDependency $headersStreamDependency = null,
        public ?array $priorities = null,
    ) {}

    public static function builder(): Http2OptionsBuilder
    {
        return new Http2OptionsBuilder;
    }
}
