<?php

namespace App\Models\Finance;

use App\Concerns\HasAttachments;
use App\Concerns\LogsCrudActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxPayment extends Model
{
    use HasAttachments, LogsCrudActivity;

    protected $fillable = [
        'period_type',
        'year',
        'quarter',
        'period_start',
        'period_end',
        'rate',
        'rate_percent',
        'tax_due',
        'amount',
        'their_amount',
        'company_amount',
        'currency',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'quarter' => 'integer',
            'rate' => 'decimal:4',
            'rate_percent' => 'integer',
            'tax_due' => 'decimal:2',
            'amount' => 'decimal:2',
            'their_amount' => 'decimal:2',
            'company_amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'payment_date' => 'date',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
