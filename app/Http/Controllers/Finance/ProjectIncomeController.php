<?php

namespace App\Http\Controllers\Finance;

use App\Enums\ProjectActivityType;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Finance\ProjectIncome;
use App\Models\Project\Project;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectIncomeController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $projectId = $request->integer('project_id') ?: null;

        $incomes = ProjectIncome::query()
            ->with(['project', 'account'])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->latest('transaction_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('mis/finance/Income/Index', [
            'incomes' => $incomes,
            'filters' => ['project_id' => $projectId],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'amount_usd' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
        ]);

        $income = ProjectIncome::query()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);
        $this->storeOptionalAttachment($request, $income);

        $project = Project::query()->find($validated['project_id']);
        if ($project) {
            ProjectActivityLogger::log(
                $project,
                ProjectActivityType::IncomeReceived,
                'Income recorded',
                $validated['description'] ?? "Income of {$validated['amount']} recorded.",
                ['income_id' => $income->id],
            );
        }

        return back()->with('success', 'Income recorded.');
    }

    public function update(Request $request, ProjectIncome $income): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.edit');

        $validated = $request->validate([
            'account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'amount_usd' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'transaction_date' => ['sometimes', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
        ]);

        $income->update($validated);

        return back()->with('success', 'Income updated.');
    }

    public function destroy(Request $request, ProjectIncome $income): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.delete');

        $income->delete();

        return back()->with('success', 'Income deleted.');
    }
}
