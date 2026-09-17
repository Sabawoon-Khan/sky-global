<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\AppliesListFilters;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Finance\ExpenseFund;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\GeneralExpense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneralExpenseController extends Controller
{
    use AppliesListFilters, AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $filters = $this->listFilters($request, ['pending', 'approved', 'rejected']);
        $filters['expense_fund_id'] = $request->string('expense_fund_id')->toString();

        $query = GeneralExpense::query()->with(['account', 'attachments', 'expenseFund']);
        $this->applyListFilters($query, $filters, [
            'search_columns' => ['description', 'reference_number'],
            'pending_null' => true,
        ]);

        if ($filters['expense_fund_id'] !== '') {
            $query->where('expense_fund_id', (int) $filters['expense_fund_id']);
        }

        $generalExpenses = (clone $query)
            ->latest('transaction_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (GeneralExpense $expense) => [
                'id' => $expense->id,
                'description' => $expense->description,
                'category' => $expense->category,
                'amount' => (float) $expense->amount,
                'amount_usd' => $expense->amount_usd !== null ? (float) $expense->amount_usd : null,
                'currency' => $expense->currency,
                'transaction_date' => $expense->transaction_date?->toDateString(),
                'status' => $expense->status,
                'expense_fund_id' => $expense->expense_fund_id,
                'expense_fund_label' => $expense->expenseFund?->displayLabel(),
                'attachments' => $expense->attachments,
            ]);

        $expenseFunds = ExpenseFund::query()
            ->withSum('generalExpenses as spent_amount', 'amount')
            ->withCount('generalExpenses')
            ->latest('received_date')
            ->get()
            ->map(function (ExpenseFund $fund) {
                $spent = (float) ($fund->spent_amount ?? 0);
                $received = (float) $fund->amount_received;

                return [
                    'id' => $fund->id,
                    'label' => $fund->displayLabel(),
                    'received_from' => $fund->received_from,
                    'description' => $fund->description,
                    'amount_received' => $received,
                    'spent_amount' => $spent,
                    'remaining_amount' => $received - $spent,
                    'currency' => $fund->currency,
                    'received_date' => $fund->received_date?->toDateString(),
                    'reference_number' => $fund->reference_number,
                    'is_overdrawn' => $spent > $received,
                    'can_delete' => ($fund->general_expenses_count ?? 0) === 0,
                ];
            });

        $openFundsCount = $expenseFunds->filter(fn (array $fund) => $fund['remaining_amount'] > 0)->count();

        return Inertia::render('mis/finance/GeneralExpenses/Index', [
            'generalExpenses' => $generalExpenses,
            'expenseFunds' => $expenseFunds,
            'categories' => FinanceCategory::options(),
            'filters' => $filters,
            'stats' => [
                'total' => (float) (clone $query)->sum('amount'),
                'count' => (clone $query)->count(),
                'funds_received_total' => (float) $expenseFunds->sum('amount_received'),
                'open_funds_count' => $openFundsCount,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        if ($request->input('expense_fund_id') === '') {
            $request->merge(['expense_fund_id' => null]);
        }

        $validated = $request->validate([
            'account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'expense_fund_id' => ['nullable', 'exists:expense_funds,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'amount_usd' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
        ]);

        $validated = $this->applyFundCurrency($validated);

        $expense = GeneralExpense::query()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);
        $this->storeOptionalAttachment($request, $expense);

        $this->notifyMisCreated(
            'finance',
            $expense->description ?: __('General expense'),
            route('finance.general-expenses', [], false),
        );

        return $this->redirectWithFundWarnings(
            back()->with('success', 'General expense recorded.'),
            $this->fundIdsToCheck(null, $validated['expense_fund_id'] ?? null),
        );
    }

    public function update(Request $request, GeneralExpense $generalExpense): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.edit');

        $previousFundId = $generalExpense->expense_fund_id;

        if ($request->input('expense_fund_id') === '') {
            $request->merge(['expense_fund_id' => null]);
        }

        $validated = $request->validate([
            'account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'expense_fund_id' => ['nullable', 'exists:expense_funds,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'amount_usd' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'transaction_date' => ['sometimes', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
        ]);

        $validated = $this->applyFundCurrency($validated, $generalExpense);

        $generalExpense->update($validated);

        $this->notifyMisUpdated(
            'finance',
            $generalExpense->description ?: __('General expense'),
            route('finance.general-expenses', [], false),
        );

        $newFundId = $generalExpense->expense_fund_id;

        return $this->redirectWithFundWarnings(
            back()->with('success', 'General expense updated.'),
            $this->fundIdsToCheck($previousFundId, $newFundId),
        );
    }

    public function destroy(Request $request, GeneralExpense $generalExpense): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.delete');

        $fundId = $generalExpense->expense_fund_id;
        $label = $generalExpense->description ?: __('General expense');
        $generalExpense->delete();

        $this->notifyMisDeleted('finance', $label, route('finance.general-expenses', [], false));

        return $this->redirectWithFundWarnings(
            back()->with('success', 'General expense deleted.'),
            $this->fundIdsToCheck($fundId, null),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function applyFundCurrency(array $validated, ?GeneralExpense $existing = null): array
    {
        $fundId = $validated['expense_fund_id'] ?? $existing?->expense_fund_id;
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
    private function fundIdsToCheck(?int $previousFundId, ?int $currentFundId): array
    {
        return array_values(array_unique(array_filter([$previousFundId, $currentFundId])));
    }

    /**
     * @param  list<int>  $fundIds
     */
    private function redirectWithFundWarnings(RedirectResponse $response, array $fundIds): RedirectResponse
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
