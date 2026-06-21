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
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Procurement\CompetitorBid;
use App\Models\Project\Project;
use App\Models\Project\ProjectDetail;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $projects = Project::query()
            ->with('organization.organizationType')
            ->where('is_archived', false)
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('mis/projects/Index', [
            'projects' => $projects,
            'statusOptions' => collect(ProjectStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => ucfirst($s->value),
            ]),
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
            ],
        ]);
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
            'currency' => $validated['currency'] ?? 'USD',
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
            'incomes' => fn ($q) => $q->latest('transaction_date')->limit(20),
            'expenses' => fn ($q) => $q->latest('transaction_date')->limit(20),
        ]);

        $income = $project->incomes()->sum('amount_usd') ?: $project->incomes()->sum('amount');
        $expense = $project->expenses()->sum('amount_usd') ?: $project->expenses()->sum('amount');

        return Inertia::render('mis/projects/Show', [
            'project' => $project,
            'finance' => [
                'income' => (float) $income,
                'expense' => (float) $expense,
                'margin' => (float) $income - (float) $expense,
                'currency' => $project->currency ?? 'USD',
            ],
            'statusOptions' => $this->allowedStatusTransitions($project),
            'organizations' => Organization::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.edit');

        $validated = $request->validated();
        $detail = $validated['detail'] ?? null;
        unset($validated['detail']);

        $previousStatus = $project->status;
        $project->update($validated);

        if (is_array($detail)) {
            $project->detail()->updateOrCreate(['project_id' => $project->id], $detail);
        }

        if (isset($validated['status']) && $validated['status'] !== $previousStatus) {
            $this->handleStatusSideEffects($project, $previousStatus, $validated['status']);
        }
        $this->storeOptionalAttachment($request, $project);

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
            'currency' => $validated['currency'] ?? $project->currency ?? 'USD',
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
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $income = ProjectIncome::query()->create([
            ...$validated,
            'project_id' => $project->id,
            'currency' => $validated['currency'] ?? $project->currency ?? 'USD',
            'amount_usd' => ($validated['currency'] ?? $project->currency ?? 'USD') === 'USD'
                ? $validated['amount']
                : null,
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

        return back()->with('success', 'Income recorded.');
    }

    public function storeExpense(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $expense = ProjectExpense::query()->create([
            ...$validated,
            'project_id' => $project->id,
            'currency' => $validated['currency'] ?? $project->currency ?? 'USD',
            'amount_usd' => ($validated['currency'] ?? $project->currency ?? 'USD') === 'USD'
                ? $validated['amount']
                : null,
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

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project archived.');
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
