<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Backups
    |--------------------------------------------------------------------------
    |
    | Configure automated and manual backups of application storage and database.
    |
    */

    'enabled' => env('BACKUP_ENABLED', true),

    'schedule_time' => env('BACKUP_SCHEDULE_TIME', '02:00'),

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),

    'retention_count' => (int) env('BACKUP_RETENTION_COUNT', 10),

    'disk' => env('BACKUP_DISK', 'backups'),

    'remote_disk' => env('BACKUP_REMOTE_DISK'),

    'storage' => [
        'enabled' => env('BACKUP_STORAGE_ENABLED', true),

        'paths' => [
            storage_path('app/private'),
            storage_path('app/public'),
        ],
    ],

    'database' => [
        'enabled' => env('BACKUP_DATABASE_ENABLED', true),

        'connection' => env('BACKUP_DATABASE_CONNECTION'),
    ],

];
