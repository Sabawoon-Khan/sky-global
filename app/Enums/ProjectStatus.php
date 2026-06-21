<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Won = 'won';
    case Lost = 'lost';
    case Active = 'active';
    case Suspended = 'suspended';
    case Completed = 'completed';
    case Closed = 'closed';

    /** @return list<string> */
    public static function biddingPhases(): array
    {
        return [self::Draft->value, self::Submitted->value, self::Won->value, self::Lost->value];
    }

    /** @return list<string> */
    public static function operationalPhases(): array
    {
        return [self::Won->value, self::Active->value, self::Completed->value];
    }
}
