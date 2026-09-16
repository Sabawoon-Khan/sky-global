<?php

namespace App\Enums;

enum TrainingStatus: string
{
    case Registered = 'registered';
    case Ministry = 'ministry';
    case Company = 'company';
    case Completed = 'completed';
    case Certified = 'certified';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<string> */
    public static function inTraining(): array
    {
        return [self::Ministry->value, self::Company->value];
    }

    /** @return list<string> */
    public static function certifiable(): array
    {
        return [
            self::Ministry->value,
            self::Company->value,
            self::Completed->value,
        ];
    }
}
