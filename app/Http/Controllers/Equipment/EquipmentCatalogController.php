<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Equipment\EquipmentCatalog;
use App\Models\Equipment\EquipmentStock;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\Project\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentCatalogController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'inventory.view');

        $search = $request->string('search')->trim()->toString();
        $category = $request->string('category')->trim()->toString();

        $equipment = EquipmentCatalog::query()
            ->with('stock')
            ->when($search, fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            }))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (EquipmentCatalog $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'category' => $item->category,
                'unit' => $item->unit,
                'description' => $item->description,
                'is_active' => $item->is_active,
                'quantity_on_hand' => (int) ($item->stock?->quantity_on_hand ?? 0),
                'quantity_reserved' => (int) ($item->stock?->quantity_reserved ?? 0),
            ]);

        $categories = EquipmentCatalog::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $total = EquipmentCatalog::query()->count();
        $active = EquipmentCatalog::query()->where('is_active', true)->count();
        $inactive = EquipmentCatalog::query()->where('is_active', false)->count();
        $lowStock = EquipmentCatalog::query()
            ->whereHas('stock', fn ($q) => $q->where('quantity_on_hand', '<=', 5))
            ->count();
        $inStock = EquipmentCatalog::query()
            ->whereHas('stock', fn ($q) => $q->where('quantity_on_hand', '>', 0))
            ->count();
        $empty = EquipmentCatalog::query()
            ->where(function ($q) {
                $q->whereDoesntHave('stock')
                    ->orWhereHas('stock', fn ($stock) => $stock->where('quantity_on_hand', '<=', 0));
            })
            ->count();

        return Inertia::render('mis/equipment/Catalog/Index', [
            'equipment' => $equipment,
            'categories' => $categories,
            'projects' => Project::query()
                ->where('is_archived', false)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'employees' => Employee::query()
                ->where('status', 'active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(200)
                ->get(['id', 'first_name', 'last_name']),
            'contractors' => Contractor::query()
                ->where('status', 'active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(200)
                ->get(['id', 'first_name', 'last_name']),
            'stats' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'low_stock' => $lowStock,
                'in_stock' => $inStock,
                'empty' => $empty,
            ],
            'chart' => [
                'status' => [
                    ['key' => 'active', 'label' => 'Active', 'value' => $active],
                    ['key' => 'inactive', 'label' => 'Inactive', 'value' => $inactive],
                    ['key' => 'in_stock', 'label' => 'In stock', 'value' => $inStock],
                    ['key' => 'low_stock', 'label' => 'Low stock', 'value' => $lowStock],
                    ['key' => 'empty', 'label' => 'Empty', 'value' => $empty],
                ],
                'monthly' => $this->countCreatedByMonth(EquipmentCatalog::query()),
            ],
            'filters' => [
                'search' => $search ?: null,
                'category' => $category ?: null,
            ],
        ]);
    }

    /**
     * @param  Builder<EquipmentCatalog>  $query
     * @return list<array{key: string, label: string, value: int}>
     */
    protected function countCreatedByMonth($query): array
    {
        $to = Carbon::now()->endOfMonth();
        $from = Carbon::now()->subMonths(5)->startOfMonth();

        $buckets = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $buckets[$key] = [
                'key' => $key,
                'label' => $cursor->format('M'),
                'value' => 0,
            ];
            $cursor = $cursor->addMonth();
        }

        $rows = $query
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->get(['created_at']);

        foreach ($rows as $row) {
            if (! $row->created_at) {
                continue;
            }
            $key = $row->created_at->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['value']++;
            }
        }

        return array_values($buckets);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'inventory.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:equipment_catalog,sku'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'initial_quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $initialQuantity = (int) ($validated['initial_quantity'] ?? 0);
        unset($validated['initial_quantity']);

        $catalog = EquipmentCatalog::query()->create([
            ...$validated,
            'unit' => filled($validated['unit'] ?? null) ? $validated['unit'] : 'pcs',
            'is_active' => $validated['is_active'] ?? true,
        ]);
        $this->storeOptionalAttachment($request, $catalog);

        EquipmentStock::query()->create([
            'equipment_catalog_id' => $catalog->id,
            'quantity_on_hand' => $initialQuantity,
        ]);

        $this->notifyMisCreated(
            'inventory',
            $catalog->name,
            route('equipment.index', [], false),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Stock item created.',
        ]);

        return back();
    }

    public function update(Request $request, EquipmentCatalog $equipmentCatalog): RedirectResponse
    {
        $this->authorizePermission($request, 'inventory.edit');

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:equipment_catalog,sku,'.$equipmentCatalog->id],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $equipmentCatalog->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Stock item updated.',
        ]);

        return back();
    }

    public function adjustStock(Request $request, EquipmentCatalog $equipmentCatalog): RedirectResponse
    {
        $this->authorizePermission($request, 'inventory.edit');

        $validated = $request->validate([
            'adjustment' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $stock = EquipmentStock::query()->firstOrCreate(
            ['equipment_catalog_id' => $equipmentCatalog->id],
            ['quantity_on_hand' => 0, 'quantity_reserved' => 0],
        );

        $nextQty = (int) $stock->quantity_on_hand + (int) $validated['adjustment'];

        if ($nextQty < 0) {
            return back()->withErrors(['adjustment' => 'Adjustment would make stock negative.']);
        }

        $stock->update(['quantity_on_hand' => $nextQty]);

        $this->notifyMisUpdated(
            'inventory',
            $equipmentCatalog->name,
            route('equipment.index', [], false),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Stock quantity updated.',
        ]);

        return back();
    }
}
