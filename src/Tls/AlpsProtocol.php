<?php

declare(strict_types=1);

namespace Reqxide\Tls;

enum AlpsProtocol: string
{
    case Http1 = 'http/1.1';
    case Http2 = 'h2';
    case Http3 = 'h3';
}
