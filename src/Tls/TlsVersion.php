<?php

declare(strict_types=1);

namespace Reqxide\Tls;

enum TlsVersion: string
{
    case TLS_1_0 = '1.0';
    case TLS_1_1 = '1.1';
    case TLS_1_2 = '1.2';
    case TLS_1_3 = '1.3';
}
