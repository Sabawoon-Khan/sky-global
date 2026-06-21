<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Equipment\TrainingCatalog;
use App\Models\Equipment\TrainingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingSessionController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $sessions = TrainingSession::query()
            ->with(['trainingCatalog', 'instructor'])
            ->latest('session_date')
            ->paginate(20);

        return Inertia::render('mis/equipment/Training/Index', [
            'sessions' => $sessions,
            'trainingCatalog' => TrainingCatalog::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        $validated = $request->validate([
            'training_catalog_id' => ['required', 'exists:training_catalog,id'],
            'title' => ['required', 'string', 'max:255'],
            'session_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'instructor_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        TrainingSession::query()->create($validated);

        return back()->with('success', 'Training session scheduled.');
    }
}
