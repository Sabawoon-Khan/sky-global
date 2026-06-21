<?php

namespace App\Http\Controllers\Project;

use App\Enums\ProjectActivityType;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Project\Project;
use App\Models\Project\ProjectIssue;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectIssueController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request, Project $project): Response
    {
        $this->authorizePermission($request, 'projects.view');

        $issues = $project->issues()
            ->where('is_archived', false)
            ->with(['reportedBy', 'assignedTo'])
            ->latest()
            ->paginate(20);

        return Inertia::render('mis/projects/Issues/Index', [
            'project' => $project->only(['id', 'code', 'name']),
            'issues' => $issues,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.create');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'severity' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'category' => ['nullable', 'string', 'max:100'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $issue = $project->issues()->create([
            ...$validated,
            'status' => 'open',
            'reported_by' => $request->user()->id,
            'opened_at' => now(),
        ]);

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::IssueOpened,
            'Issue opened',
            $issue->title,
            ['issue_id' => $issue->id],
        );

        return back()->with('success', 'Issue reported.');
    }

    public function update(Request $request, Project $project, ProjectIssue $issue): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.edit');

        abort_unless($issue->project_id === $project->id, 404);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'severity' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'status' => ['sometimes', 'string', 'in:open,in_progress,resolved,closed'],
            'category' => ['nullable', 'string', 'max:100'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'resolution_notes' => ['nullable', 'string'],
        ]);

        $wasResolved = $issue->status !== 'resolved' && ($validated['status'] ?? null) === 'resolved';

        if ($wasResolved) {
            $validated['resolved_at'] = now();
        }

        $issue->update($validated);

        if ($wasResolved) {
            ProjectActivityLogger::log(
                $project,
                ProjectActivityType::IssueResolved,
                'Issue resolved',
                $issue->title,
                ['issue_id' => $issue->id],
            );
        }

        return back()->with('success', 'Issue updated.');
    }
}
