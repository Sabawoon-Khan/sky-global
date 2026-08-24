<?php

namespace App\Models;

use App\Concerns\HasStatusChangeLogs;
use App\Models\Hr\Employee;
use App\Models\Project\Project;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasStatusChangeLogs, LogsActivity, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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
        $fromStatus = $this->is_active ? 'active' : 'disabled';

        $this->update([
            'is_active' => false,
            'disabled_at' => now(),
            'disabled_by' => $by?->id ?? auth()->id(),
        ]);

        $this->logStatusChange('disabled', $fromStatus, $by);
    }

    public function enable(?User $by = null): void
    {
        $fromStatus = $this->is_active ? 'active' : 'disabled';

        $this->update([
            'is_active' => true,
            'disabled_at' => null,
            'disabled_by' => null,
        ]);

        $this->logStatusChange('active', $fromStatus, $by);
    }

    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'project_manager_id');
    }

    public function authenticationLogs(): HasMany
    {
        return $this->hasMany(AuthenticationLog::class);
    }

    /**
     * Whether this user has business activity that should block deletion.
     * Auth/audit-only records (login logs, status logs, sessions) do not count.
     */
    public function hasSystemActivity(): bool
    {
        return in_array($this->id, self::idsWithSystemActivity([$this->id]), true);
    }

    /**
     * @param  iterable<int>  $ids
     * @return list<int>
     */
    public static function idsWithSystemActivity(iterable $ids): array
    {
        $ids = array_values(array_unique(array_map(
            static fn ($id): int => (int) $id,
            is_array($ids) ? $ids : iterator_to_array($ids),
        )));

        if ($ids === []) {
            return [];
        }

        $found = self::query()
            ->whereIn('id', $ids)
            ->whereNotNull('employee_id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        foreach (self::systemActivityReferences() as [$table, $column]) {
            $remaining = array_values(array_diff($ids, $found));

            if ($remaining === []) {
                break;
            }

            $matched = DB::table($table)
                ->whereIn($column, $remaining)
                ->distinct()
                ->pluck($column)
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $found = array_values(array_unique([...$found, ...$matched]));
        }

        $remaining = array_values(array_diff($ids, $found));

        if ($remaining !== []) {
            $matched = DB::table('project_activities')
                ->where('causer_type', self::class)
                ->whereIn('causer_id', $remaining)
                ->distinct()
                ->pluck('causer_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $found = array_values(array_unique([...$found, ...$matched]));
        }

        return $found;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private static function systemActivityReferences(): array
    {
        return [
            ['employees', 'user_id'],
            ['projects', 'created_by'],
            ['projects', 'project_manager_id'],
            ['projects', 'archived_by'],
            ['project_members', 'user_id'],
            ['project_documents', 'uploaded_by'],
            ['project_issues', 'reported_by'],
            ['project_issues', 'assigned_to'],
            ['project_status_histories', 'changed_by'],
            ['procurement_opportunities', 'created_by'],
            ['bids', 'created_by'],
            ['bid_documents', 'uploaded_by'],
            ['bid_status_histories', 'changed_by'],
            ['archived_documents', 'uploaded_by'],
            ['attachments', 'uploaded_by'],
            ['project_incomes', 'created_by'],
            ['project_incomes', 'approved_by'],
            ['project_expenses', 'created_by'],
            ['project_expenses', 'approved_by'],
            ['general_incomes', 'created_by'],
            ['general_incomes', 'approved_by'],
            ['general_expenses', 'created_by'],
            ['general_expenses', 'approved_by'],
            ['invoices', 'created_by'],
            ['payments', 'created_by'],
            ['attendance_sheets', 'created_by'],
            ['personnel_attendances', 'approved_by'],
            ['payroll_runs', 'processed_by'],
            ['payroll_runs', 'created_by'],
            ['form_submissions', 'submitted_by'],
            ['form_submissions', 'verified_by'],
            ['personnel_attachments', 'verified_by'],
            ['personnel_equipment_issues', 'issued_by'],
            ['personnel_equipment_returns', 'received_by'],
            ['training_sessions', 'instructor_id'],
            ['storage_backups', 'created_by'],
        ];
    }
}
