<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Equipment\EquipmentStock;
use App\Models\Equipment\PersonnelEquipmentIssue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PersonnelEquipmentIssueController extends Controller
{
    use AuthorizesMisPermissions;

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        $validated = $request->validate([
            'personnel_type' => ['required', 'string'],
            'personnel_id' => ['required', 'integer'],
            'equipment_catalog_id' => ['required', 'exists:equipment_catalog,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'issued_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $stock = EquipmentStock::query()
            ->where('equipment_catalog_id', $validated['equipment_catalog_id'])
            ->first();

        if ($stock && $stock->quantity_on_hand < $validated['quantity']) {
            return back()->withErrors(['quantity' => 'Insufficient stock available.']);
        }

        PersonnelEquipmentIssue::query()->create([
            ...$validated,
            'issued_by' => $request->user()->id,
            'issued_at' => $validated['issued_at'] ?? now()->toDateString(),
        ]);

        if ($stock) {
            $stock->decrement('quantity_on_hand', $validated['quantity']);
        }

        return back()->with('success', 'Equipment issued.');
    }
}
