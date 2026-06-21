<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidStatusHistory extends Model
{
    protected $fillable = [
        'bid_id',
        'from_status',
        'to_status',
        'notes',
        'changed_by',
    ];

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
