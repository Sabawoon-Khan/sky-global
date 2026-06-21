<?php

namespace App\Http\Controllers\Project;

use App\Enums\ProjectActivityType;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project\Project;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'projects.view');

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $projects = Project::query()
            ->with('organization')
            ->where('is_archived', false)
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('mis/projects/Index', [
            'projects' => $projects,
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
            ],
        ]);
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorizePermission($request, 'projects.view');

        $project->load([
            'organization',
            'detail',
            'activities' => fn ($q) => $q->latest()->limit(50),
            'issues' => fn ($q) => $q->where('is_archived', false)->latest(),
            'documents' => fn ($q) => $q->latest(),
            'sites',
            'deployments.projectSite',
        ]);

        $income = $project->incomes()->sum('amount_usd') ?: $project->incomes()->sum('amount');
        $expense = $project->expenses()->sum('amount_usd') ?: $project->expenses()->sum('amount');

        $project->setAttribute('finance', [
            'income' => (float) $income,
            'expense' => (float) $expense,
            'margin' => (float) $income - (float) $expense,
            'currency' => $project->currency ?? 'USD',
        ]);

        return Inertia::render('mis/projects/Show', [
            'project' => $project,
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
            $project->detail()->updateOrCreate(
                ['project_id' => $project->id],
                $detail,
            );
        }

        if (isset($validated['status']) && $validated['status'] !== $previousStatus) {
            ProjectActivityLogger::log(
                $project,
                ProjectActivityType::StatusChange,
                'Project status changed',
                "Status changed from {$previousStatus} to {$validated['status']}.",
                ['from' => $previousStatus, 'to' => $validated['status']],
            );
        }

        return back()->with('success', 'Project updated.');
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

        $project->detail()->updateOrCreate(
            ['project_id' => $project->id],
            $validated,
        );

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::NoteAdded,
            'Project details updated',
            'Optional project details were updated.',
        );

        return back()->with('success', 'Project details saved.');
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
}
