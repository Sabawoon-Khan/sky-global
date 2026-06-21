<?php

namespace App\Models;

use App\Models\Hr\Employee;
use App\Models\Project\Project;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /** @use HasFactory<UserFactory> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'disabled_at',
        'disabled_by',
        'employee_id',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'disabled_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email', 'is_active'])->logOnlyDirty();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function disabledBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'disabled_by');
    }

    public function disable(?User $by = null): void
    {
        $this->update([
            'is_active' => false,
            'disabled_at' => now(),
            'disabled_by' => $by?->id ?? auth()->id(),
        ]);
    }

    public function enable(): void
    {
        $this->update([
            'is_active' => true,
            'disabled_at' => null,
            'disabled_by' => null,
        ]);
    }

    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'project_manager_id');
    }
}
