<?php

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDetail extends Model
{
    protected $fillable = [
        'project_id',
        'client_requirements',
        'risk_notes',
        'special_instructions',
        'guards_required',
        'supervisors_required',
        'shift_details',
        'equipment_requirements',
        'training_requirements',
        'client_contact_on_site',
        'reporting_frequency',
        'internal_notes',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
