<?php

declare(strict_types=1);

namespace Reqxide\Tls;

enum Curve: string
{
    case X25519 = 'X25519';
    case X25519MLKEM768 = 'X25519MLKEM768';
    case P256 = 'P-256';
    case P384 = 'P-384';
    case P521 = 'P-521';
    case FFDHE2048 = 'ffdhe2048';
    case FFDHE3072 = 'ffdhe3072';
}
