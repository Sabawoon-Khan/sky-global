<?php

namespace App\Enums;

enum TrainingPath: string
{
    case Ministry = 'ministry';
    case Company = 'company';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
