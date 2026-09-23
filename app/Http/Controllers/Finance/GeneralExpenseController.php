<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\AppliesListFilters;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\HandlesExpenseFundSpending;
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
    use AppliesListFilters, AuthorizesMisPermissions, HandlesExpenseFundSpending, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $filters = $this->listFilters($request, ['pending', 'approved', 'rejected']);
        $filters['paid_from_cash_box'] = $request->string('paid_from_cash_box')->toString();

        $query = GeneralExpense::query()->with(['account', 'attachments']);
        $this->applyListFilters($query, $filters, [
            'search_columns' => ['description', 'reference_number'],
            'pending_null' => true,
        ]);

        if ($filters['paid_from_cash_box'] === '1') {
            $query->where('paid_from_cash_box', true);
        } elseif ($filters['paid_from_cash_box'] === '0') {
            $query->where('paid_from_cash_box', false);
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
                'paid_from_cash_box' => (bool) $expense->paid_from_cash_box,
                'attachments' => $expense->attachments,
            ]);

        $cashBox = ExpenseFund::cashBox();

        return Inertia::render('mis/finance/GeneralExpenses/Index', [
            'generalExpenses' => $generalExpenses,
            'expenseFunds' => ExpenseFund::inertiaSummaries(),
            'cashBox' => $cashBox,
            'categories' => FinanceCategory::options(),
            'filters' => $filters,
            'stats' => [
                'total' => (float) (clone $query)->sum('amount'),
                'count' => (clone $query)->count(),
                'cash_remaining' => $cashBox['remaining'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        $this->mergePaidFromCashBox($request);

        $validated = $request->validate([
            'account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'paid_from_cash_box' => ['sometimes', 'boolean'],
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

        $validated['paid_from_cash_box'] = (bool) ($validated['paid_from_cash_box'] ?? false);
        $validated = $this->assertExpenseWithinCashBox($validated);

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

        return back()->with('success', 'General expense recorded.');
    }

    public function update(Request $request, GeneralExpense $generalExpense): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.edit');

        $wasPaidFromCashBox = (bool) $generalExpense->paid_from_cash_box;
        $previousAmount = (float) $generalExpense->amount;

        $this->mergePaidFromCashBox($request);

        $validated = $request->validate([
            'account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'paid_from_cash_box' => ['sometimes', 'boolean'],
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

        if (! array_key_exists('paid_from_cash_box', $validated)) {
            $validated['paid_from_cash_box'] = $wasPaidFromCashBox;
        }
        if (! array_key_exists('amount', $validated)) {
            $validated['amount'] = $generalExpense->amount;
        }

        $validated = $this->assertExpenseWithinCashBox(
            $validated,
            $wasPaidFromCashBox,
            $previousAmount,
        );

        $generalExpense->update($validated);

        $this->notifyMisUpdated(
            'finance',
            $generalExpense->description ?: __('General expense'),
            route('finance.general-expenses', [], false),
        );

        return back()->with('success', 'General expense updated.');
    }

    public function destroy(Request $request, GeneralExpense $generalExpense): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.delete');

        $label = $generalExpense->description ?: __('General expense');
        $generalExpense->delete();

        $this->notifyMisDeleted('finance', $label, route('finance.general-expenses', [], false));

        return back()->with('success', 'General expense deleted.');
    }
}
