<?php

namespace App\Http\Controllers\Equipment;

use App\Enums\ProjectActivityType;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Equipment\EquipmentStock;
use App\Models\Equipment\ProjectEquipmentIssue;
use App\Models\Equipment\ProjectEquipmentReturn;
use App\Models\Project\Project;
use App\Services\ProjectActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProjectEquipmentIssueController extends Controller
{
    use AuthorizesMisPermissions;

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizePermission($request, 'inventory.create');

        $validated = $request->validate([
            'equipment_catalog_id' => ['required', 'exists:equipment_catalog,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'issued_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $stock = EquipmentStock::query()
            ->where('equipment_catalog_id', $validated['equipment_catalog_id'])
            ->first();

        if (! $stock || $stock->quantity_on_hand < $validated['quantity']) {
            return back()->withErrors(['quantity' => 'Insufficient stock available.']);
        }

        $issue = ProjectEquipmentIssue::query()->create([
            'project_id' => $project->id,
            'equipment_catalog_id' => $validated['equipment_catalog_id'],
            'quantity' => $validated['quantity'],
            'issued_at' => $validated['issued_at'] ?? now()->toDateString(),
            'issued_by' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        $stock->decrement('quantity_on_hand', $validated['quantity']);

        ProjectActivityLogger::log(
            $project,
            ProjectActivityType::NoteAdded,
            'Equipment issued from stock',
            "Issued {$issue->quantity} unit(s) to project.",
            ['project_equipment_issue_id' => $issue->id],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Items issued to project from stock.',
        ]);

        return back();
    }

    public function returnItems(Request $request, Project $project, ProjectEquipmentIssue $issue): RedirectResponse
    {
        $this->authorizePermission($request, 'inventory.edit');

        abort_unless($issue->project_id === $project->id, 404);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'returned_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $outstanding = $issue->quantityOutstanding();

        if ($validated['quantity'] > $outstanding) {
            return back()->withErrors(['quantity' => 'Cannot return more than outstanding quantity.']);
        }

        $returnedAt = $validated['returned_at'] ?? now()->toDateString();

        DB::transaction(function () use ($request, $project, $issue, $validated, $returnedAt) {
            ProjectEquipmentReturn::query()->create([
                'project_equipment_issue_id' => $issue->id,
                'quantity' => $validated['quantity'],
                'returned_at' => $returnedAt,
                'notes' => $validated['notes'] ?? null,
                'received_by' => $request->user()->id,
            ]);

            $issue->increment('quantity_returned', $validated['quantity']);

            $stock = EquipmentStock::query()->firstOrCreate(
                ['equipment_catalog_id' => $issue->equipment_catalog_id],
                ['quantity_on_hand' => 0, 'quantity_reserved' => 0],
            );
            $stock->increment('quantity_on_hand', $validated['quantity']);

            ProjectActivityLogger::log(
                $project,
                ProjectActivityType::NoteAdded,
                'Equipment returned to stock',
                "Returned {$validated['quantity']} unit(s) from project.",
                ['project_equipment_issue_id' => $issue->id],
            );
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Items returned to stock.',
        ]);

        return back();
    }
}
