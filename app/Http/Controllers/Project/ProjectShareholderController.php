<?php

namespace App\Http\Controllers\Project;

use App\Enums\ProjectActivityType;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Project\Project;
use App\Models\Project\ProjectShareholder;
use App\Models\Project\ProjectShareholderTransaction;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProjectShareholderController extends Controller
{
    use AuthorizesMisPermissions;

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'share_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'invested_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $invested = (float) ($validated['invested_amount'] ?? 0);
        $currency = $validated['currency'] ?? 'AFN';

        $shareholder = DB::transaction(function () use ($project, $validated, $invested, $currency, $request) {
            $shareholder = $project->shareholders()->create([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'share_percent' => $validated['share_percent'],
                'invested_amount' => $invested,
                'returned_amount' => 0,
                'currency' => $currency,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($invested > 0) {
                $shareholder->transactions()->create([
                    'type' => ProjectShareholderTransaction::TYPE_CONTRIBUTION,
                    'amount' => $invested,
                    'currency' => $currency,
                    'transaction_date' => $validated['transaction_date'] ?? now()->toDateString(),
                    'notes' => 'Initial capital contribution',
                    'created_by' => $request->user()?->id,
                ]);
            }

            return $shareholder;
        });

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::MemberAdded,
            'Shareholder added',
            "{$shareholder->name} added with {$shareholder->share_percent}% share.",
            ['shareholder_id' => $shareholder->id],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Shareholder added.',
        ]);

        $this->notifyMisCreated(
            'projects',
            $shareholder->name,
            route('projects.show', $project, false),
        );

        return back();
    }

    public function update(Request $request, Project $project, ProjectShareholder $shareholder): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.edit');

        abort_unless($shareholder->project_id === $project->id, 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'share_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $shareholder->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Shareholder updated.',
        ]);

        return back();
    }

    public function destroy(Request $request, Project $project, ProjectShareholder $shareholder): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.delete');

        abort_unless($shareholder->project_id === $project->id, 404);

        $name = $shareholder->name;
        $shareholder->delete();

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::NoteAdded,
            'Shareholder removed',
            "{$name} removed from project shareholders.",
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Shareholder removed.',
        ]);

        return back();
    }

    public function contribute(Request $request, Project $project, ProjectShareholder $shareholder): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.edit');

        abort_unless($shareholder->project_id === $project->id, 404);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($shareholder, $validated, $request) {
            $shareholder->transactions()->create([
                'type' => ProjectShareholderTransaction::TYPE_CONTRIBUTION,
                'amount' => $validated['amount'],
                'currency' => $shareholder->currency,
                'transaction_date' => $validated['transaction_date'] ?? now()->toDateString(),
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $shareholder->increment('invested_amount', $validated['amount']);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Capital contribution recorded.',
        ]);

        return back();
    }

    public function distribute(Request $request, Project $project, ProjectShareholder $shareholder): RedirectResponse
    {
        $this->authorizePermission($request, 'projects.edit');

        abort_unless($shareholder->project_id === $project->id, 404);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $outstanding = $shareholder->outstandingAmount();

        if ((float) $validated['amount'] > $outstanding + 0.0001) {
            return back()->withErrors([
                'amount' => "Cannot return more than outstanding capital ({$outstanding}).",
            ]);
        }

        DB::transaction(function () use ($shareholder, $validated, $request) {
            $shareholder->transactions()->create([
                'type' => ProjectShareholderTransaction::TYPE_DISTRIBUTION,
                'amount' => $validated['amount'],
                'currency' => $shareholder->currency,
                'transaction_date' => $validated['transaction_date'] ?? now()->toDateString(),
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $shareholder->increment('returned_amount', $validated['amount']);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Share return / distribution recorded.',
        ]);

        return back();
    }
}
