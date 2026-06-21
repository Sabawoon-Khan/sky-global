<?php

namespace App\Models\Finance;

use App\Models\Project\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBudget extends Model
{
    protected $fillable = [
        'project_id',
        'category',
        'budgeted_amount',
        'currency',
        'period',
    ];

    protected function casts(): array
    {
        return [
            'budgeted_amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
