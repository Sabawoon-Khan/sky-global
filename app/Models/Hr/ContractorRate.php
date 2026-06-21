<?php

namespace App\Models\Hr;

use App\Models\Project\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorRate extends Model
{
    protected $fillable = [
        'contractor_id',
        'project_id',
        'daily_rate',
        'monthly_rate',
        'currency',
        'effective_from',
        'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'decimal:2',
            'monthly_rate' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
