<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Equipment\EquipmentCatalog;
use App\Models\Equipment\EquipmentStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentCatalogController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $search = $request->string('search')->trim()->toString();

        $equipment = EquipmentCatalog::query()
            ->with('stock')
            ->when($search, fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('mis/equipment/Catalog/Index', [
            'equipment' => $equipment,
            'filters' => ['search' => $search ?: null],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:equipment_catalog,sku'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'initial_quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $initialQuantity = $validated['initial_quantity'] ?? 0;
        unset($validated['initial_quantity']);

        $catalog = EquipmentCatalog::query()->create($validated);
        $this->storeOptionalAttachment($request, $catalog);

        if ($initialQuantity > 0) {
            EquipmentStock::query()->create([
                'equipment_catalog_id' => $catalog->id,
                'quantity_on_hand' => $initialQuantity,
            ]);
        }

        return back()->with('success', 'Equipment catalog item created.');
    }

    public function update(Request $request, EquipmentCatalog $equipmentCatalog): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.edit');

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:equipment_catalog,sku,'.$equipmentCatalog->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $equipmentCatalog->update($validated);

        return back()->with('success', 'Equipment catalog item updated.');
    }
}
