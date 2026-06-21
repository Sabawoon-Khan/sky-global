<?php

namespace App\Models\Hr;

use App\Concerns\HasAttachments;
use App\Models\Project\Project;
use App\Models\Project\ProjectSite;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PersonnelAttendance extends Model
{
    use HasAttachments;

    protected $table = 'personnel_attendances';

    protected $attributes = [
        'days_present' => 0,
        'days_absent' => 0,
        'days_leave' => 0,
        'overtime_hours' => 0,
    ];

    protected $fillable = [
        'personnel_type',
        'personnel_id',
        'project_id',
        'project_site_id',
        'year',
        'month',
        'days_present',
        'days_absent',
        'days_leave',
        'overtime_hours',
        'status',
        'approved_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'overtime_hours' => 'decimal:2',
        ];
    }

    public function personnel(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'personnel_type', 'personnel_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
