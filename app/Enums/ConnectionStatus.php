<?php

namespace App\Enums;

enum ConnectionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Error = 'error';
    case Disconnected = 'disconnected';
    case Revoked = 'revoked';
}
