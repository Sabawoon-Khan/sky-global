<?php

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProjectActivity extends Model
{
    protected $fillable = [
        'project_id',
        'activity_type',
        'title',
        'description',
        'metadata',
        'causer_type',
        'causer_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }
}
