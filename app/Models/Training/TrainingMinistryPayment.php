<?php

namespace App\Models\Training;

use App\Concerns\LogsCrudActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingMinistryPayment extends Model
{
    use LogsCrudActivity, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'batch_number',
        'payment_date',
        'period_start',
        'period_end',
        'total_amount',
        'currency',
        'receipt_path',
        'original_filename',
        'notes',
        'created_by',
    ];

    protected $appends = ['receipt_url'];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date:Y-m-d',
            'period_start' => 'date:Y-m-d',
            'period_end' => 'date:Y-m-d',
            'total_amount' => 'decimal:2',
        ];
    }

    public function guards(): HasMany
    {
        return $this->hasMany(TrainingGuard::class, 'ministry_payment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getReceiptUrlAttribute(): ?string
    {
        if (! $this->receipt_path) {
            return null;
        }

        return route('training.payments.receipt', $this);
    }
}
