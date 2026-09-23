<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Finance\ExpenseFund;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait HandlesExpenseFundSpending
{
    protected function mergePaidFromCashBox(Request $request): void
    {
        if (! $request->exists('paid_from_cash_box')) {
            return;
        }

        $request->merge([
            'paid_from_cash_box' => $request->boolean('paid_from_cash_box'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function assertExpenseWithinCashBox(
        array $validated,
        bool $wasPaidFromCashBox = false,
        ?float $previousAmount = null,
    ): array {
        $paidFromCashBox = (bool) ($validated['paid_from_cash_box'] ?? false);
        if (! $paidFromCashBox) {
            return $validated;
        }

        $available = ExpenseFund::cashBox()['remaining'];
        if ($wasPaidFromCashBox && $previousAmount !== null) {
            $available += $previousAmount;
        }

        if ($available <= 0) {
            throw ValidationException::withMessages([
                'paid_from_cash_box' => __('The cash box has no remaining balance. Record received money first.'),
            ]);
        }

        if (array_key_exists('amount', $validated)) {
            $amount = (float) $validated['amount'];
            if ($amount > $available + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => __('Amount exceeds the remaining cash box balance of :amount.', [
                        'amount' => number_format($available, 2),
                    ]),
                ]);
            }
        }

        return $validated;
    }
}
