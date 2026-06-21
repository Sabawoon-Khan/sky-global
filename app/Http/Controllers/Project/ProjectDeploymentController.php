<?php

namespace App\Http\Controllers\Project;

use App\Enums\ProjectActivityType;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Project\Project;
use App\Models\Project\ProjectDeployment;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectDeploymentController extends Controller
{
    use AuthorizesMisPermissions;

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.create');

        $validated = $request->validate([
            'project_site_id' => ['nullable', 'exists:project_sites,id'],
            'personnel_type' => ['required', 'string'],
            'personnel_id' => ['required', 'integer'],
            'role' => ['nullable', 'string', 'max:100'],
            'shift_pattern' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'monthly_rate' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $deployment = $project->deployments()->create($validated);

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::DeploymentChanged,
            'Personnel deployed',
            "Deployment #{$deployment->id} created.",
            ['deployment_id' => $deployment->id],
        );

        return back()->with('success', 'Deployment created.');
    }

    public function update(Request $request, Project $project, ProjectDeployment $deployment): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.edit');

        abort_unless($deployment->project_id === $project->id, 404);

        $validated = $request->validate([
            'project_site_id' => ['nullable', 'exists:project_sites,id'],
            'role' => ['nullable', 'string', 'max:100'],
            'shift_pattern' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'monthly_rate' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $deployment->update($validated);

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::DeploymentChanged,
            'Deployment updated',
            "Deployment #{$deployment->id} updated.",
            ['deployment_id' => $deployment->id],
        );

        return back()->with('success', 'Deployment updated.');
    }

    public function destroy(Request $request, Project $project, ProjectDeployment $deployment): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.delete');

        abort_unless($deployment->project_id === $project->id, 404);

        $deploymentId = $deployment->id;
        $deployment->delete();

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::DeploymentChanged,
            'Deployment removed',
            "Deployment #{$deploymentId} removed.",
            ['deployment_id' => $deploymentId],
        );

        return back()->with('success', 'Deployment removed.');
    }
}
