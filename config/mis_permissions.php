<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Standard permission verbs
    |--------------------------------------------------------------------------
    |
    | Every module below receives {module}.{verb} permissions automatically.
    | Add a new module to "modules" when you introduce a new app section.
    |
    */

    'verbs' => [
        'view',
        'create',
        'edit',
        'delete',
        'archive',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application modules
    |--------------------------------------------------------------------------
    |
    | Each key becomes a permission group on /settings/roles.
    | Use "extra" for module-specific permissions beyond the standard verbs.
    |
    */

    'modules' => [
        'bidding' => [
            'extra' => [
                'view_competitors',
            ],
        ],
        'projects' => [
            'extra' => [],
        ],
        'finance' => [
            'extra' => [],
        ],
        'hr' => [
            'extra' => [],
        ],
        'settings' => [
            'extra' => [
                'manage_users',
                'view_login_logs',
            ],
        ],
    ],

];
