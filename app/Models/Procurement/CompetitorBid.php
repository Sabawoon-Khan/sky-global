<?php

namespace App\Models\Procurement;

use App\Concerns\HasAttachments;
use App\Models\Project\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorBid extends Model
{
    use HasAttachments;

    protected $fillable = [
        'project_id',
        'procurement_opportunity_id',
        'competitor_name',
        'bid_amount',
        'currency',
        'is_winner',
        'is_estimated',
        'source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bid_amount' => 'decimal:2',
            'is_winner' => 'boolean',
            'is_estimated' => 'boolean',
        ];
    }

    public function procurementOpportunity(): BelongsTo
    {
        return $this->belongsTo(ProcurementOpportunity::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
