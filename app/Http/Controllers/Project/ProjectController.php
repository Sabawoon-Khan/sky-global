<?php

namespace App\Http\Controllers\Project;

use App\Enums\ProjectActivityType;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\GeneratesMisReferenceNumbers;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Equipment\EquipmentCatalog;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Procurement\CompetitorBid;
use App\Models\Project\Project;
use App\Models\Project\ProjectDetail;
use App\Services\ProjectActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use AuthorizesMisPermissions, GeneratesMisReferenceNumbers, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'projects.view');

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $baseQuery = Project::query()->where('is_archived', false);

        $projects = (clone $baseQuery)
            ->with('organization.organizationType')
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $byStatus = (clone $baseQuery)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();

        $active = (int) ($byStatus[ProjectStatus::Active->value] ?? 0);
        $planning = (int) ($byStatus[ProjectStatus::Draft->value] ?? 0)
            + (int) ($byStatus[ProjectStatus::Submitted->value] ?? 0);
        $wonOrContracted = (int) ($byStatus[ProjectStatus::Won->value] ?? 0) + $active;

        return Inertia::render('mis/projects/Index', [
            'projects' => $projects,
            'statusOptions' => collect(ProjectStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => ucfirst($s->value),
            ]),
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => $active,
                'planning' => $planning,
                'won_or_contracted' => $wonOrContracted,
                'by_status' => $byStatus,
            ],
            'chart' => [
                'status' => collect(ProjectStatus::cases())->map(fn (ProjectStatus $case) => [
                    'key' => $case->value,
                    'label' => ucfirst(str_replace('_', ' ', $case->value)),
                    'value' => (int) ($byStatus[$case->value] ?? 0),
                ])->values()->all(),
                'monthly' => $this->countCreatedByMonth(clone $baseQuery),
            ],
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
            ],
        ]);
    }

    /**
     * @param  Builder<Project>  $query
     * @return list<array{key: string, label: string, value: int}>
     */
    protected function countCreatedByMonth($query): array
    {
        $to = Carbon::now()->endOfMonth();
        $from = Carbon::now()->subMonths(5)->startOfMonth();

        $buckets = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $buckets[$key] = [
                'key' => $key,
                'label' => $cursor->format('M'),
                'value' => 0,
            ];
            $cursor = $cursor->addMonth();
        }

        $rows = $query
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->get(['created_at']);

        foreach ($rows as $row) {
            if (! $row->created_at) {
                continue;
            }
            $key = $row->created_at->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['value']++;
            }
        }

        return array_values($buckets);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'projects.create');

        return Inertia::render('mis/projects/Create', [
            'organizations' => Organization::query()
                ->where('is_active', true)
                ->with('organizationType')
                ->orderBy('name')
                ->get(['id', 'name', 'organization_type_id', 'province']),
            'organizationTypes' => OrganizationType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $project = Project::query()->create([
            ...$validated,
            'code' => $this->generateProjectCode(),
            'currency' => $validated['currency'] ?? 'AFN',
            'status' => $validated['status'] ?? ProjectStatus::Draft->value,
            'project_manager_id' => $request->user()->id,
            'created_by' => $request->user()->id,
        ]);

        ProjectDetail::query()->create(['project_id' => $project->id]);
        $this->storeOptionalAttachment($request, $project);

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::ProjectCreated,
            'Project registered',
            'New project created in draft — add your bid and competitor intel here.',
        );

        $this->notifyMisCreated(
            'projects',
            $project->name,
            route('projects.show', $project, false),
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project created. Add your bid details on the project page.');
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorizePermission($request, 'projects.view');

        $project->load([
            'organization.organizationType',
            'detail',
            'bidLineItems',
            'competitorBids',
            'attachments',
            'activities' => fn ($q) => $q->latest()->limit(50),
            'issues' => fn ($q) => $q->where('is_archived', false)->latest(),
            'documents' => fn ($q) => $q->latest(),
            'sites',
            'deployments' => fn ($q) => $q->with(['projectSite', 'personnel'])->latest(),
            'incomes' => fn ($q) => $q->with('attachments')->latest('transaction_date')->limit(20),
            'expenses' => fn ($q) => $q->with('attachments')->latest('transaction_date')->limit(20),
            'shareholders' => fn ($q) => $q->with(['transactions' => fn ($tq) => $tq->latest('transaction_date')->limit(10)]),
            'equipmentIssues' => fn ($q) => $q
                ->with([
                    'equipmentCatalog',
                    'issuedBy',
                    'returns' => fn ($rq) => $rq->with('receivedBy')->latest(),
                ])
                ->latest(),
        ]);

        $income = $project->incomes()->sum('amount');
        $expense = $project->expenses()->sum('amount');
        $shareholderInvested = (float) $project->shareholders()->sum('invested_amount');
        $shareholderReturned = (float) $project->shareholders()->sum('returned_amount');

        return Inertia::render('mis/projects/Show', [
            'project' => $project,
            'finance' => [
                'income' => (float) $income,
                'expense' => (float) $expense,
                'margin' => (float) $income - (float) $expense,
                'currency' => 'AFN',
                'shareholder_invested' => $shareholderInvested,
                'shareholder_returned' => $shareholderReturned,
                'shareholder_outstanding' => max(0, $shareholderInvested - $shareholderReturned),
            ],
            'statusOptions' => $this->allowedStatusTransitions($project),
            'organizations' => Organization::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'employees' => Employee::query()
                ->where('status', 'active')
                ->where('is_permanent', false)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
            'contractors' => Contractor::query()
                ->where('status', 'active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
            'stockItems' => EquipmentCatalog::query()
                ->with('stock')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (EquipmentCatalog $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'category' => $item->category,
                    'unit' => $item->unit,
                    'quantity_on_hand' => (int) ($item->stock?->quantity_on_hand ?? 0),
                ]),
            'financeCategories' => FinanceCategory::options(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.edit');

        $validated = $request->validated();
        $detail = $validated['detail'] ?? null;
        unset($validated['detail']);

        if ($request->has('has_security_scope')) {
            $validated['security_scope'] = $request->input('security_scope', []);
        }

        $previousStatus = $project->status;
        $project->update($validated);

        if (is_array($detail)) {
            $project->detail()->updateOrCreate(['project_id' => $project->id], $detail);
        }

        if (isset($validated['status']) && $validated['status'] !== $previousStatus) {
            $this->handleStatusSideEffects($project, $previousStatus, $validated['status']);
        }
        $this->storeOptionalAttachment($request, $project);

        if (isset($validated['status']) && $validated['status'] !== $previousStatus) {
            $this->notifyMisStatus(
                'projects',
                $project->name,
                $validated['status'],
                route('projects.show', $project, false),
            );
        } else {
            $this->notifyMisUpdated(
                'projects',
                $project->name,
                route('projects.show', $project, false),
            );
        }

        return back()->with('success', 'Project saved.');
    }

    public function updateStatus(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.edit');

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:draft,submitted,won,lost,active,completed,closed'],
            'loss_reason' => ['nullable', 'string'],
            'winning_competitor_name' => ['nullable', 'string', 'max:255'],
            'winning_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $previousStatus = $project->status;
        $newStatus = $validated['status'];

        $updates = ['status' => $newStatus];

        if ($newStatus === ProjectStatus::Submitted->value) {
            $updates['bid_submitted_at'] = now();
        }

        if ($newStatus === ProjectStatus::Won->value) {
            $updates['won_at'] = now();
            $updates['total_contract_value'] = $project->our_bid_amount ?? $project->total_contract_value;
            $updates['started_at'] = $project->started_at ?? now();
        }

        if ($newStatus === ProjectStatus::Lost->value) {
            $updates['loss_reason'] = $validated['loss_reason'] ?? $project->loss_reason;
            $updates['winning_competitor_name'] = $validated['winning_competitor_name'] ?? $project->winning_competitor_name;
            $updates['winning_amount'] = $validated['winning_amount'] ?? $project->winning_amount;
        }

        if ($newStatus === ProjectStatus::Active->value && ! $project->won_at) {
            $updates['won_at'] = now();
            $updates['started_at'] = now();
        }

        $project->update($updates);
        $this->handleStatusSideEffects($project, $previousStatus, $newStatus);

        $this->notifyMisStatus(
            'projects',
            $project->name,
            $newStatus,
            route('projects.show', $project, false),
        );

        return back()->with('success', 'Status updated to '.ucfirst($newStatus).'.');
    }

    public function storeCompetitorBid(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.view_competitors');

        $validated = $request->validate([
            'competitor_name' => ['required', 'string', 'max:255'],
            'bid_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_winner' => ['boolean'],
            'is_estimated' => ['boolean'],
            'source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $competitorBid = $project->competitorBids()->create([
            ...$validated,
            'currency' => $validated['currency'] ?? 'AFN',
        ]);
        $this->storeOptionalAttachment($request, $competitorBid);

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::NoteAdded,
            'Competitor bid recorded',
            "Added competitor: {$validated['competitor_name']}",
        );

        return back()->with('success', 'Competitor bid added.');
    }

    public function destroyCompetitorBid(Request $request, Project $project, CompetitorBid $competitorBid): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.view_competitors');

        abort_unless($competitorBid->project_id === $project->id, 404);

        $competitorBid->delete();

        return back()->with('success', 'Competitor bid removed.');
    }

    public function storeIncome(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $income = ProjectIncome::query()->create([
            ...$validated,
            'project_id' => $project->id,
            'currency' => 'AFN',
            'created_by' => $request->user()->id,
        ]);
        $this->storeOptionalAttachment($request, $income);

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::IncomeReceived,
            'Payment received',
            $validated['description'] ?? 'Income recorded',
            ['amount' => $validated['amount']],
        );

        $this->notifyMisCreated(
            'finance',
            $validated['description'] ?: $project->name,
            route('projects.show', $project, false),
        );

        return back()->with('success', 'Payment recorded.');
    }

    public function storeExpense(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $expense = ProjectExpense::query()->create([
            ...$validated,
            'project_id' => $project->id,
            'currency' => 'AFN',
            'created_by' => $request->user()->id,
        ]);
        $this->storeOptionalAttachment($request, $expense);

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::ExpenseAdded,
            'Expense recorded',
            $validated['description'] ?? 'Expense added',
            ['amount' => $validated['amount']],
        );

        $this->notifyMisCreated(
            'finance',
            $validated['description'] ?: $project->name,
            route('projects.show', $project, false),
        );

        return back()->with('success', 'Expense recorded.');
    }

    public function updateDetails(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.edit');

        $validated = $request->validate([
            'client_requirements' => ['nullable', 'string'],
            'risk_notes' => ['nullable', 'string'],
            'special_instructions' => ['nullable', 'string'],
            'guards_required' => ['nullable', 'integer', 'min:0'],
            'supervisors_required' => ['nullable', 'integer', 'min:0'],
            'shift_details' => ['nullable', 'string'],
            'equipment_requirements' => ['nullable', 'string'],
            'training_requirements' => ['nullable', 'string'],
            'client_contact_on_site' => ['nullable', 'string', 'max:255'],
            'reporting_frequency' => ['nullable', 'string', 'max:255'],
            'internal_notes' => ['nullable', 'string'],
        ]);

        $project->detail()->updateOrCreate(['project_id' => $project->id], $validated);

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::NoteAdded,
            'Project details updated',
            'Optional project details were updated.',
        );

        return back()->with('success', 'Details saved.');
    }

    public function archive(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.archive');

        $project->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $request->user()->id,
            'status' => ProjectStatus::Closed->value,
        ]);

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::StatusChange,
            'Project archived',
            'Project was archived.',
        );

        $this->notifyMisStatus(
            'projects',
            $project->name,
            'archived',
            route('projects.index', [], false),
        );

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project archived.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.delete');

        $name = $project->name;
        $project->delete();

        $this->notifyMisDeleted('projects', $name, route('projects.index', [], false));

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project deleted.');
    }

    /** @return list<array{value: string, label: string}> */
    private function allowedStatusTransitions(Project $project): array
    {
        $current = $project->status;

        $map = [
            ProjectStatus::Draft->value => [ProjectStatus::Submitted, ProjectStatus::Lost],
            ProjectStatus::Submitted->value => [ProjectStatus::Won, ProjectStatus::Lost, ProjectStatus::Draft],
            ProjectStatus::Won->value => [ProjectStatus::Active, ProjectStatus::Completed],
            ProjectStatus::Lost->value => [ProjectStatus::Draft],
            ProjectStatus::Active->value => [ProjectStatus::Suspended, ProjectStatus::Completed],
            ProjectStatus::Suspended->value => [ProjectStatus::Active, ProjectStatus::Closed],
            ProjectStatus::Completed->value => [ProjectStatus::Closed],
        ];

        $allowed = $map[$current] ?? [];

        return collect($allowed)->map(fn (ProjectStatus $s) => [
            'value' => $s->value,
            'label' => ucfirst($s->value),
        ])->values()->all();
    }

    private function handleStatusSideEffects(Project $project, string $from, string $to): void
    {
        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::StatusChange,
            'Status changed',
            "Changed from {$from} to {$to}.",
            ['from' => $from, 'to' => $to],
        );
    }
}
