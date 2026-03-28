<?php

declare(strict_types=1);

namespace Reqxide\Http2;

enum PseudoHeader: string
{
    case Method = ':method';
    case Path = ':path';
    case Authority = ':authority';
    case Scheme = ':scheme';
}
