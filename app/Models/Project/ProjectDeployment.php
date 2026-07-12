<?php

namespace App\Models\Project;

use App\Concerns\HasStatusChangeLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProjectDeployment extends Model
{
    use HasStatusChangeLogs;

    protected $fillable = [
        'project_id',
        'project_site_id',
        'personnel_type',
        'personnel_id',
        'role',
        'shift_pattern',
        'status',
        'start_date',
        'end_date',
        'monthly_rate',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'monthly_rate' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class);
    }

    public function personnel(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'personnel_type', 'personnel_id');
    }
}
