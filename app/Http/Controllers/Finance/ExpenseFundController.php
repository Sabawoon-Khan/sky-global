<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Finance\ExpenseFund;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseFundController extends Controller
{
    use AuthorizesMisPermissions;

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        $validated = $request->validate([
            'amount_received' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'received_from' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'received_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $fund = ExpenseFund::query()->create([
            ...$validated,
            'currency' => $validated['currency'] ?? 'AFN',
            'created_by' => $request->user()->id,
        ]);

        $this->notifyMisCreated(
            'finance',
            $fund->displayLabel(),
            route('finance.general-expenses', [], false),
        );

        return back()->with('success', __('Expense fund recorded.'));
    }

    public function update(Request $request, ExpenseFund $expenseFund): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.edit');

        $validated = $request->validate([
            'amount_received' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'received_from' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'received_date' => ['sometimes', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $expenseFund->update($validated);

        $response = back()->with('success', __('Expense fund updated.'));

        $warning = $expenseFund->fresh()->overdrawWarningMessage();
        if ($warning !== null) {
            $response = $response->with('warning', $warning);
        }

        $this->notifyMisUpdated(
            'finance',
            $expenseFund->displayLabel(),
            route('finance.general-expenses', [], false),
        );

        return $response;
    }

    public function destroy(Request $request, ExpenseFund $expenseFund): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.delete');

        if ($expenseFund->generalExpenses()->exists()) {
            return back()->with('error', __('Cannot delete a fund that has linked expenses.'));
        }

        $label = $expenseFund->displayLabel();
        $expenseFund->delete();

        $this->notifyMisDeleted('finance', $label, route('finance.general-expenses', [], false));

        return back()->with('success', __('Expense fund deleted.'));
    }
}
