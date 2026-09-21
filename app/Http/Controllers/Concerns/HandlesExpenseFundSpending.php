<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Finance\ExpenseFund;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
     * @return list<int>
     */
    protected function fundIdsToCheck(?int $previousFundId, ?int $currentFundId): array
    {
        return array_values(array_unique(array_filter([$previousFundId, $currentFundId])));
    }

    /**
     * @param  list<int>  $fundIds
     */
    protected function redirectWithFundWarnings(RedirectResponse $response, array $fundIds): RedirectResponse
    {
        foreach ($fundIds as $fundId) {
            $fund = ExpenseFund::query()->find($fundId);
            if ($fund === null) {
                continue;
            }

            $warning = $fund->overdrawWarningMessage();
            if ($warning !== null) {
                return $response->with('warning', $warning);
            }
        }

        return $response;
    }
}
