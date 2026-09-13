<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinanceCategoryController extends Controller
{
    use AuthorizesMisPermissions;

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'applies_to' => ['required', 'in:income,expense,both'],
        ]);

        $name = trim($validated['name']);

        $exists = FinanceCategory::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->where('applies_to', $validated['applies_to'])
            ->exists();

        if ($exists) {
            return back()->with('success', 'Category already exists.');
        }

        FinanceCategory::query()->create([
            'name' => $name,
            'applies_to' => $validated['applies_to'],
            'is_active' => true,
            'sort_order' => (int) FinanceCategory::query()->max('sort_order') + 1,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Category added.',
        ]);

        return back();
    }

    public function destroy(Request $request, FinanceCategory $financeCategory): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.delete');

        $financeCategory->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Category removed.',
        ]);

        return back();
    }
}
