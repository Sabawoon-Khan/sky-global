<?php

namespace App\Http\Controllers\Project;

use App\Enums\ProjectActivityType;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Project\Project;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectActivityController extends Controller
{
    use AuthorizesMisPermissions;

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.edit');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::NoteAdded,
            $validated['title'],
            $validated['description'] ?? null,
        );

        return back()->with('success', 'Note added to project activity.');
    }
}
