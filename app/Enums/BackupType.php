<?php

namespace App\Enums;

enum BackupType: string
{
    case Storage = 'storage';
    case Database = 'database';
}
