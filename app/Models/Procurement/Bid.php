<?php

namespace App\Models\Procurement;

use App\Models\Project\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Bid extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'procurement_opportunity_id',
        'bid_number',
        'status',
        'submitted_at',
        'our_total_amount',
        'currency',
        'loss_reason',
        'winning_competitor_name',
        'winning_amount',
        'project_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'our_total_amount' => 'decimal:2',
            'winning_amount' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'submitted_at', 'our_total_amount', 'project_id'])
            ->logOnlyDirty();
    }

    public function procurementOpportunity(): BelongsTo
    {
        return $this->belongsTo(ProcurementOpportunity::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(BidLineItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BidDocument::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BidStatusHistory::class);
    }
}
