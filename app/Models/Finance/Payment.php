<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    protected $fillable = [
        'payable_type',
        'payable_id',
        'amount',
        'currency',
        'exchange_rate',
        'amount_usd',
        'direction',
        'payment_date',
        'reference_number',
        'payment_method',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'amount_usd' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
