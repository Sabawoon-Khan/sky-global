<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthenticationLog extends Model
{
    public const EVENT_LOGIN_SUCCESS = 'login_success';

    public const EVENT_LOGIN_FAILED = 'login_failed';

    public const EVENT_LOGOUT = 'logout';

    public const EVENT_TWO_FACTOR_CHALLENGED = 'two_factor_challenged';

    public const EVENT_TWO_FACTOR_FAILED = 'two_factor_failed';

    public const EVENT_LOCKOUT = 'lockout';

    public const EVENT_SESSION_REVOKED_INACTIVE = 'session_revoked_inactive';

    protected $fillable = [
        'user_id',
        'email',
        'event',
        'success',
        'failure_reason',
        'ip_address',
        'ip_addresses',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'session_id',
        'guard',
        'request_method',
        'request_path',
        'referer',
        'accept_language',
        'metadata',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'ip_addresses' => 'array',
            'metadata' => 'array',
            'logged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
