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
            'paid_from_cash_box' => $this->requestBoolean($request, 'paid_from_cash_box'),
        ]);
    }

    protected function requestBoolean(Request $request, string $key): bool
    {
        $value = $request->input($key);

        if (is_array($value)) {
            foreach ($value as $item) {
                if (filter_var($item, FILTER_VALIDATE_BOOLEAN)) {
                    return true;
                }
            }

            return false;
        }

        return $request->boolean($key);
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
