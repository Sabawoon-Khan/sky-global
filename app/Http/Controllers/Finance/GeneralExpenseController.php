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

        $expenseFunds = ExpenseFund::inertiaSummaries();

        $openFundsCount = $expenseFunds->filter(fn (array $fund) => $fund['remaining_amount'] > 0)->count();

        return Inertia::render('mis/finance/GeneralExpenses/Index', [
            'generalExpenses' => $generalExpenses,
            'expenseFunds' => $expenseFunds,
            'expenseFundPickerOptions' => ExpenseFund::inertiaPickerOptions(),
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

        $this->mergeEmptyExpenseFundId($request);

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

        $validated = $this->applyExpenseFundCurrency($validated);
        $validated = $this->assertExpenseWithinFundBalance($validated);

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

        $previousFundId = $generalExpense->expense_fund_id;
        $previousAmount = (float) $generalExpense->amount;

        $this->mergeEmptyExpenseFundId($request);

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

        $validated = $this->applyExpenseFundCurrency($validated, $generalExpense->expense_fund_id);

        if (! array_key_exists('expense_fund_id', $validated)) {
            $validated['expense_fund_id'] = $generalExpense->expense_fund_id;
        }
        if (! array_key_exists('amount', $validated)) {
            $validated['amount'] = $generalExpense->amount;
        }

        $validated = $this->assertExpenseWithinFundBalance(
            $validated,
            $previousFundId,
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
