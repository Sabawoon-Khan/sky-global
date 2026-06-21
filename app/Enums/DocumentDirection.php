<?php

namespace App\Enums;

enum DocumentDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
    case Internal = 'internal';
}
