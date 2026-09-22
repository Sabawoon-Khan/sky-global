<?php

namespace App\Models\Finance;

use App\Concerns\LogsCrudActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

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

    public function projectExpenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }

    public static function queryWithSpentAggregates(): Builder
    {
        return static::query()
            ->withSum('generalExpenses as general_spent_amount', 'amount')
            ->withSum('projectExpenses as project_spent_amount', 'amount')
            ->withCount(['generalExpenses', 'projectExpenses']);
    }

    public function aggregatedSpentAmount(): float
    {
        if (isset($this->general_spent_amount) || isset($this->project_spent_amount)) {
            return (float) ($this->general_spent_amount ?? 0)
                + (float) ($this->project_spent_amount ?? 0);
        }

        return $this->spentAmount();
    }

    public function spentAmount(): float
    {
        return (float) $this->generalExpenses()->sum('amount')
            + (float) $this->projectExpenses()->sum('amount');
    }

    public function remainingAmount(): float
    {
        return (float) $this->amount_received - $this->aggregatedSpentAmount();
    }

    public function isOverdrawn(): bool
    {
        return $this->aggregatedSpentAmount() > (float) $this->amount_received;
    }

    public function overdrawAmount(): float
    {
        return max(0, $this->aggregatedSpentAmount() - (float) $this->amount_received);
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

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function inertiaSummaries(): Collection
    {
        return static::queryWithSpentAggregates()
            ->latest('received_date')
            ->get()
            ->map(function (ExpenseFund $fund) {
                $spent = $fund->aggregatedSpentAmount();
                $received = (float) $fund->amount_received;

                $remaining = round($received - $spent, 2);

                return [
                    'id' => $fund->id,
                    'label' => $fund->displayLabel(),
                    'received_from' => $fund->received_from,
                    'description' => $fund->description,
                    'amount_received' => $received,
                    'spent_amount' => $spent,
                    'remaining_amount' => $remaining,
                    'currency' => $fund->currency,
                    'received_date' => $fund->received_date?->toDateString(),
                    'reference_number' => $fund->reference_number,
                    'is_overdrawn' => $spent > $received,
                    'can_delete' => ($fund->general_expenses_count ?? 0) === 0
                        && ($fund->project_expenses_count ?? 0) === 0,
                ];
            });
    }

    /**
     * Funds available in "Spend from fund" selects (remaining balance only).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function inertiaPickerOptions(): Collection
    {
        return static::inertiaSummaries()
            ->filter(fn (array $fund) => ($fund['remaining_amount'] ?? 0) > 0)
            ->values();
    }
}
