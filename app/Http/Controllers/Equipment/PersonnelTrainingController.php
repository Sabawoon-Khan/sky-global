<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Equipment\PersonnelTraining;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PersonnelTrainingController extends Controller
{
    use AuthorizesMisPermissions;

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        $validated = $request->validate([
            'personnel_type' => ['required', 'string'],
            'personnel_id' => ['required', 'integer'],
            'training_catalog_id' => ['required', 'exists:training_catalog,id'],
            'training_session_id' => ['nullable', 'exists:training_sessions,id'],
            'completed_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:completed_at'],
            'notes' => ['nullable', 'string'],
            'certificate' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($request->hasFile('certificate')) {
            $validated['certificate_path'] = $request->file('certificate')->store('training-certificates', 'local');
        }

        unset($validated['certificate']);

        PersonnelTraining::query()->create($validated);

        return back()->with('success', 'Training record added.');
    }
}
