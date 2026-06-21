<?php

namespace App\Models\Project;

use App\Concerns\HasAttachments;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectIssue extends Model
{
    use HasAttachments;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'severity',
        'status',
        'category',
        'reported_by',
        'assigned_to',
        'opened_at',
        'resolved_at',
        'resolution_notes',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'resolved_at' => 'datetime',
            'is_archived' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
