<?php

namespace App\Http\Controllers\Finance;

use App\Enums\ProjectActivityType;
use App\Http\Controllers\Concerns\AppliesListFilters;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\HandlesExpenseFundSpending;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\ProjectExpense;
use App\Models\Project\Project;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectExpenseController extends Controller
{
    use AppliesListFilters, AuthorizesMisPermissions, HandlesExpenseFundSpending, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $filters = $this->listFilters($request, ['pending', 'approved', 'rejected']);

        $query = ProjectExpense::query()
            ->with(['project', 'account', 'attachments']);
        $this->applyListFilters($query, $filters, [
            'search_columns' => ['description', 'reference_number'],
            'search_relations' => ['project' => ['code', 'name']],
            'pending_null' => true,
        ]);

        $expenses = (clone $query)
            ->latest('transaction_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ProjectExpense $expense) => [
                'id' => $expense->id,
                'description' => $expense->description,
                'category' => $expense->category,
                'amount' => (float) $expense->amount,
                'amount_usd' => $expense->amount_usd !== null ? (float) $expense->amount_usd : null,
                'currency' => $expense->currency,
                'transaction_date' => $expense->transaction_date?->toDateString(),
                'status' => $expense->status,
                'paid_from_cash_box' => (bool) $expense->paid_from_cash_box,
                'project' => $expense->project?->only(['id', 'code', 'name']),
                'attachments' => $expense->attachments,
            ]);

        return Inertia::render('mis/finance/Expenses/Index', [
            'expenses' => $expenses,
            'projects' => Project::query()
                ->where('is_archived', false)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'categories' => collect(FinanceCategory::options('expense'))
                ->pluck('name')
                ->concat(
                    ProjectExpense::query()
                        ->whereNotNull('category')
                        ->where('category', '!=', '')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category')
                )
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'filters' => $filters,
            'stats' => [
                'total' => (float) (clone $query)->sum('amount'),
                'count' => (clone $query)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        $this->mergePaidFromCashBox($request);

        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'paid_from_cash_box' => ['sometimes', 'boolean'],
            'account_id' => ['nullable', 'exists:chart_of_accounts,id'],
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

        $expense = ProjectExpense::query()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);
        $this->storeOptionalAttachment($request, $expense);

        $project = Project::query()->find($validated['project_id']);
        if ($project) {
            ProjectActivityLogger::log(
                $project,
                ProjectActivityType::ExpenseAdded,
                'Expense recorded',
                $validated['description'] ?? "Expense of {$validated['amount']} recorded.",
                ['expense_id' => $expense->id],
            );
        }

        $this->notifyMisCreated(
            'finance',
            $expense->description ?: __('Project expense'),
            route('finance.expenses', [], false),
        );

        return back()->with('success', 'Expense recorded.');
    }

    public function update(Request $request, ProjectExpense $expense): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.edit');

        $wasPaidFromCashBox = (bool) $expense->paid_from_cash_box;
        $previousAmount = (float) $expense->amount;

        $this->mergePaidFromCashBox($request);

        $validated = $request->validate([
            'paid_from_cash_box' => ['sometimes', 'boolean'],
            'account_id' => ['nullable', 'exists:chart_of_accounts,id'],
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
            $validated['amount'] = $expense->amount;
        }

        $validated = $this->assertExpenseWithinCashBox(
            $validated,
            $wasPaidFromCashBox,
            $previousAmount,
        );

        $expense->update($validated);

        $this->notifyMisUpdated(
            'finance',
            $expense->description ?: __('Project expense'),
            route('finance.expenses', [], false),
        );

        return back()->with('success', 'Expense updated.');
    }

    public function destroy(Request $request, ProjectExpense $expense): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.delete');

        $label = $expense->description ?: __('Project expense');
        $expense->delete();

        $this->notifyMisDeleted('finance', $label, route('finance.expenses', [], false));

        return back()->with('success', 'Expense deleted.');
    }
}
