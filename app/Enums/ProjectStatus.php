<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planning = 'planning';
    case Active = 'active';
    case Suspended = 'suspended';
    case Completed = 'completed';
    case Closed = 'closed';
}
