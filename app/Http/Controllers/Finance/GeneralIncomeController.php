<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Finance\GeneralIncome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GeneralIncomeController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

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

        return back()->with('success', 'General income updated.');
    }

    public function destroy(Request $request, GeneralIncome $generalIncome): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.delete');

        $generalIncome->delete();

        return back()->with('success', 'General income deleted.');
    }
}
