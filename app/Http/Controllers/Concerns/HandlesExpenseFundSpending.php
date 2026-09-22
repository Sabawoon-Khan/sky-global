<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Finance\ExpenseFund;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait HandlesExpenseFundSpending
{
    protected function mergeEmptyExpenseFundId(Request $request): void
    {
        if ($request->input('expense_fund_id') === '') {
            $request->merge(['expense_fund_id' => null]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function applyExpenseFundCurrency(array $validated, ?int $fallbackFundId = null): array
    {
        $fundId = $validated['expense_fund_id'] ?? $fallbackFundId;
        if ($fundId === null) {
            return $validated;
        }

        $fund = ExpenseFund::query()->find($fundId);
        if ($fund === null) {
            return $validated;
        }

        if (! array_key_exists('currency', $validated) || $validated['currency'] === null) {
            $validated['currency'] = $fund->currency;
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function assertExpenseWithinFundBalance(
        array $validated,
        ?int $previousFundId = null,
        ?float $previousAmount = null,
    ): array {
        $fundId = $validated['expense_fund_id'] ?? null;
        if ($fundId === null) {
            return $validated;
        }

        $fund = ExpenseFund::query()->find($fundId);
        if ($fund === null) {
            return $validated;
        }

        $available = $fund->remainingAmount();
        if ($previousFundId === $fundId && $previousAmount !== null) {
            $available += $previousAmount;
        }

        if ($available <= 0) {
            throw ValidationException::withMessages([
                'expense_fund_id' => __('This fund has no remaining balance. Record a new fund first.'),
            ]);
        }

        if (array_key_exists('amount', $validated)) {
            $amount = (float) $validated['amount'];
            if ($amount > $available + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => __('Amount exceeds the remaining fund balance of :amount :currency.', [
                        'amount' => number_format($available, 2),
                        'currency' => $fund->currency,
                    ]),
                ]);
            }
        }

        return $validated;
    }
}
