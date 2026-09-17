<?php

namespace App\Models\Finance;

use App\Concerns\LogsCrudActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseFund extends Model
{
    use LogsCrudActivity, SoftDeletes;

    protected $fillable = [
        'amount_received',
        'currency',
        'received_from',
        'description',
        'received_date',
        'reference_number',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_received' => 'decimal:2',
            'received_date' => 'date',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function generalExpenses(): HasMany
    {
        return $this->hasMany(GeneralExpense::class);
    }

    public function spentAmount(): float
    {
        return (float) $this->generalExpenses()->sum('amount');
    }

    public function remainingAmount(): float
    {
        return (float) $this->amount_received - $this->spentAmount();
    }

    public function isOverdrawn(): bool
    {
        return $this->spentAmount() > (float) $this->amount_received;
    }

    public function overdrawAmount(): float
    {
        return max(0, $this->spentAmount() - (float) $this->amount_received);
    }

    public function overdrawWarningMessage(): ?string
    {
        if (! $this->isOverdrawn()) {
            return null;
        }

        return __('This fund is overdrawn by :amount :currency.', [
            'amount' => number_format($this->overdrawAmount(), 2),
            'currency' => $this->currency,
        ]);
    }

    public function displayLabel(): string
    {
        $parts = array_filter([
            $this->received_from,
            $this->description,
        ]);

        if ($parts !== []) {
            return implode(' — ', $parts);
        }

        return __('Fund :date', [
            'date' => $this->received_date?->toDateString() ?? (string) $this->id,
        ]);
    }
}
