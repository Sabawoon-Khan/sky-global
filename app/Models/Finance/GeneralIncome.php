<?php

namespace App\Models\Finance;

use App\Concerns\HasAttachments;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneralIncome extends Model
{
    use HasAttachments, SoftDeletes;

    protected $fillable = [
        'account_id',
        'amount',
        'currency',
        'exchange_rate',
        'amount_usd',
        'description',
        'category',
        'transaction_date',
        'reference_number',
        'payment_method',
        'status',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'amount_usd' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
