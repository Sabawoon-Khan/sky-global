<?php

namespace App\Http\Controllers\Finance;

use App\Enums\ProjectActivityType;
use App\Http\Controllers\Concerns\AppliesListFilters;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\ProjectIncome;
use App\Models\Project\Project;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectIncomeController extends Controller
{
    use AppliesListFilters, AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $filters = $this->listFilters($request, ['pending', 'approved', 'rejected']);

        $query = ProjectIncome::query()
            ->with(['project', 'account', 'attachments']);
        $this->applyListFilters($query, $filters, [
            'search_columns' => ['description', 'reference_number'],
            'search_relations' => ['project' => ['code', 'name']],
            'pending_null' => true,
        ]);

        $incomes = (clone $query)
            ->latest('transaction_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ProjectIncome $income) => [
                'id' => $income->id,
                'description' => $income->description,
                'category' => $income->category,
                'amount' => (float) $income->amount,
                'amount_usd' => $income->amount_usd !== null ? (float) $income->amount_usd : null,
                'currency' => $income->currency,
                'transaction_date' => $income->transaction_date?->toDateString(),
                'status' => $income->status,
                'project' => $income->project?->only(['id', 'code', 'name']),
                'attachments' => $income->attachments,
            ]);

        $chartBase = (clone $query);

        $monthly = collect(range(5, 0))->map(function (int $offset) use ($query) {
            $start = now()->subMonths($offset)->startOfMonth();
            $end = (clone $start)->endOfMonth();

            $amount = (float) (clone $query)
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount');

            return [
                'label' => $start->format('M'),
                'value' => $amount,
            ];
        })->values()->all();

        $byStatus = (clone $chartBase)
            ->selectRaw("COALESCE(status, 'pending') as status, SUM(amount) as total, COUNT(*) as count")
            ->groupByRaw("COALESCE(status, 'pending')")
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'key' => (string) $row->status,
                'value' => (float) $row->total,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        $byProjectRows = (clone $chartBase)
            ->selectRaw('project_id, SUM(amount) as total, COUNT(*) as count')
            ->whereNotNull('project_id')
            ->groupBy('project_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $projectsById = Project::query()
            ->whereIn('id', $byProjectRows->pluck('project_id')->filter()->all())
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        $byProject = $byProjectRows
            ->map(function ($row) use ($projectsById) {
                $project = $projectsById->get($row->project_id);

                return [
                    'key' => $project?->code ?? ('#'.$row->project_id),
                    'label' => $project?->name ?? ('#'.$row->project_id),
                    'value' => (float) $row->total,
                    'count' => (int) $row->count,
                ];
            })
            ->values()
            ->all();

        $approved = (float) (clone $chartBase)->where('status', 'approved')->sum('amount');
        $pending = (float) (clone $chartBase)->where(function ($q) {
            $q->whereNull('status')->orWhere('status', 'pending');
        })->sum('amount');

        return Inertia::render('mis/finance/Income/Index', [
            'incomes' => $incomes,
            'projects' => Project::query()
                ->where('is_archived', false)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'categories' => $this->incomeCategoryOptions(),
            'filters' => $filters,
            'stats' => [
                'total' => (float) (clone $query)->sum('amount'),
                'count' => (clone $query)->count(),
                'approved' => $approved,
                'pending' => $pending,
            ],
            'charts' => [
                'monthly' => $monthly,
                'by_status' => $byStatus,
                'by_project' => $byProject,
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    private function incomeCategoryOptions(): array
    {
        return collect(FinanceCategory::options('income'))
            ->pluck('name')
            ->concat(
                ProjectIncome::query()
                    ->whereNotNull('category')
                    ->where('category', '!=', '')
                    ->distinct()
                    ->orderBy('category')
                    ->pluck('category')
            )
            ->unique()
            ->sort()
            ->values()
            ->all();
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
            'category' => ['nullable', 'string', 'max:100'],
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

        $this->notifyMisCreated(
            'finance',
            $income->description ?: __('Project income'),
            route('finance.income', [], false),
        );

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
            'category' => ['nullable', 'string', 'max:100'],
            'transaction_date' => ['sometimes', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
        ]);

        $income->update($validated);

        $this->notifyMisUpdated(
            'finance',
            $income->description ?: __('Project income'),
            route('finance.income', [], false),
        );

        return back()->with('success', 'Income updated.');
    }

    public function destroy(Request $request, ProjectIncome $income): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.delete');

        $label = $income->description ?: __('Project income');
        $income->delete();

        $this->notifyMisDeleted('finance', $label, route('finance.income', [], false));

        return back()->with('success', 'Income deleted.');
    }
}
