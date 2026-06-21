<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidDocument extends Model
{
    protected $fillable = [
        'bid_id',
        'title',
        'category',
        'file_path',
        'uploaded_by',
    ];

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
