<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\AppliesListFilters;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\GeneralIncome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneralIncomeController extends Controller
{
    use AppliesListFilters, AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $filters = $this->listFilters($request, ['pending', 'approved', 'rejected', 'recorded']);

        $query = GeneralIncome::query()->with('attachments');
        $this->applyListFilters($query, $filters, [
            'search_columns' => ['description', 'reference_number'],
            'pending_null' => true,
        ]);

        $generalIncomes = (clone $query)
            ->latest('transaction_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (GeneralIncome $income) => [
                'id' => $income->id,
                'description' => $income->description,
                'category' => $income->category,
                'amount' => (float) $income->amount,
                'amount_usd' => $income->amount_usd !== null ? (float) $income->amount_usd : null,
                'currency' => $income->currency,
                'transaction_date' => $income->transaction_date?->toDateString(),
                'status' => $income->status,
                'attachments' => $income->attachments,
            ]);

        return Inertia::render('mis/finance/GeneralIncome/Index', [
            'generalIncomes' => $generalIncomes,
            'categories' => FinanceCategory::options(),
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

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected,recorded'],
        ]);

        $income = GeneralIncome::query()->create([
            ...$validated,
            'amount_usd' => $validated['amount'],
            'created_by' => $request->user()->id,
        ]);
        $this->storeOptionalAttachment($request, $income);

        $this->notifyMisCreated(
            'finance',
            $income->description ?: __('General income'),
            route('finance.general-income', [], false),
        );

        return back()->with('success', 'General income recorded.');
    }

    public function update(Request $request, GeneralIncome $generalIncome): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.edit');

        $validated = $request->validate([
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'transaction_date' => ['sometimes', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected,recorded'],
        ]);

        $generalIncome->update($validated);

        $this->notifyMisUpdated(
            'finance',
            $generalIncome->description ?: __('General income'),
            route('finance.general-income', [], false),
        );

        return back()->with('success', 'General income updated.');
    }

    public function destroy(Request $request, GeneralIncome $generalIncome): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.delete');

        $label = $generalIncome->description ?: __('General income');
        $generalIncome->delete();

        $this->notifyMisDeleted('finance', $label, route('finance.general-income', [], false));

        return back()->with('success', 'General income deleted.');
    }
}
