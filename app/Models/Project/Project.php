<?php

namespace App\Models\Project;

use App\Models\Finance\ProjectBudget;
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use App\Models\Organization;
use App\Models\Procurement\Bid;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Project extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'bid_id',
        'organization_id',
        'code',
        'name',
        'contract_number',
        'contract_start',
        'contract_end',
        'scope_summary',
        'total_contract_value',
        'currency',
        'status',
        'project_manager_id',
        'won_at',
        'started_at',
        'completed_at',
        'is_archived',
        'archived_at',
        'archived_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'contract_start' => 'date',
            'contract_end' => 'date',
            'total_contract_value' => 'decimal:2',
            'won_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'project_manager_id', 'total_contract_value', 'is_archived'])
            ->logOnlyDirty();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function detail(): HasOne
    {
        return $this->hasOne(ProjectDetail::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(ProjectSite::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ProjectDocument::class, 'documentable');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(ProjectIssue::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(ProjectDeployment::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(ProjectIncome::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(ProjectBudget::class);
    }
}
