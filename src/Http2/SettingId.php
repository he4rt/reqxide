<?php

declare(strict_types=1);

namespace Reqxide\Http2;

enum SettingId: int
{
    case HeaderTableSize = 0x1;
    case EnablePush = 0x2;
    case MaxConcurrentStreams = 0x3;
    case InitialWindowSize = 0x4;
    case MaxFrameSize = 0x5;
    case MaxHeaderListSize = 0x6;
    case EnableConnectProtocol = 0x8;
    case NoRfc7540Priorities = 0x9;
}
