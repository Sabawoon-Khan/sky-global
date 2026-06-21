<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Project\Project;
use App\Models\Project\ProjectSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectSiteController extends Controller
{
    use AuthorizesMisPermissions;

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'province' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $project->sites()->create($validated);

        return back()->with('success', 'Site added.');
    }

    public function update(Request $request, Project $project, ProjectSite $site): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.edit');

        abort_unless($site->project_id === $project->id, 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'province' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $site->update($validated);

        return back()->with('success', 'Site updated.');
    }

    public function destroy(Request $request, Project $project, ProjectSite $site): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.delete');

        abort_unless($site->project_id === $project->id, 404);

        if ($site->deployments()->exists()) {
            return back()->withErrors(['site' => 'Cannot delete a site with active deployments.']);
        }

        $site->delete();

        return back()->with('success', 'Site removed.');
    }
}
