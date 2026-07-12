<?php

namespace App\Enums;

enum SecurityScopeType: string
{
    case Static = 'static';
    case Mobile = 'mobile';
    case Vip = 'vip';
    case Event = 'event';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
