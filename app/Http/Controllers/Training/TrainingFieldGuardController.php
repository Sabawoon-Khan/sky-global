<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Training\TrainingFieldGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainingFieldGuardController extends Controller
{
    use AuthorizesMisPermissions;

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'training.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'grandfather_name' => ['nullable', 'string', 'max:255'],
            'tazkira_number' => ['nullable', 'string', 'max:50'],
            'id_card_number' => ['nullable', 'string', 'max:50'],
            'site' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        TrainingFieldGuard::query()->create([
            ...$validated,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('training.field.roster.index')
            ->with('success', __('Field guard added to roster.'));
    }

    public function destroy(Request $request, TrainingFieldGuard $trainingFieldGuard): RedirectResponse
    {
        $this->authorizePermission($request, 'training.delete');

        $trainingFieldGuard->delete();

        return redirect()
            ->route('training.field.roster.index')
            ->with('success', __('Field guard removed from roster.'));
    }
}
