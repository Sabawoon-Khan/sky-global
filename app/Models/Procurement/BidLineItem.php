<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidLineItem extends Model
{
    protected $fillable = [
        'bid_id',
        'category',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total',
        'currency',
        'cost_basis',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'cost_basis' => 'decimal:2',
        ];
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }
}
