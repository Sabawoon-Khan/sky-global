<?php

namespace App\Models\Procurement;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcurementOpportunity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'reference_number',
        'title',
        'description',
        'source',
        'published_at',
        'submission_deadline',
        'estimated_value',
        'currency',
        'security_scope',
        'location',
        'duration_months',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
            'submission_deadline' => 'date',
            'estimated_value' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    public function competitorBids(): HasMany
    {
        return $this->hasMany(CompetitorBid::class);
    }
}
