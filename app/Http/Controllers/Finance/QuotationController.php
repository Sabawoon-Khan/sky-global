<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Finance\Quotation;
use App\Models\Organization;
use App\Services\DocumentNumberService;
use App\Support\CompanyDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuotationController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $quotations = Quotation::query()
            ->with(['organization:id,name,address,email,phone'])
            ->latest('quote_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Quotation $quotation) => [
                'id' => $quotation->id,
                'quote_number' => $quotation->quote_number,
                'quote_date' => $quotation->quote_date?->toDateString(),
                'valid_until' => $quotation->valid_until?->toDateString(),
                'status' => $quotation->status,
                'total' => (float) $quotation->total,
                'currency' => $quotation->currency ?: 'USD',
                'organization' => $quotation->organization?->only(['id', 'name']),
            ]);

        return Inertia::render('mis/finance/Quotations/Index', [
            'quotations' => $quotations,
            'organizations' => Organization::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        if (blank($request->input('organization_id'))) {
            $request->merge(['organization_id' => null]);
        }

        if (blank($request->input('tax'))) {
            $request->merge(['tax' => 0]);
        }

        $validated = $request->validate([
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'quote_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quote_date'],
            'description_of_work' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'in:draft,sent,accepted,declined,expired'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['nullable', 'string', 'max:255'],
            'line_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lineItems = $this->normalizedLines($validated['line_items'] ?? []);
        unset($validated['line_items']);

        $subtotal = round(array_sum(array_column($lineItems, 'total')), 2);
        $tax = (float) ($validated['tax'] ?? 0);

        $quotation = DB::transaction(function () use ($request, $validated, $lineItems, $subtotal, $tax) {
            $quotation = Quotation::query()->create([
                ...$validated,
                'quote_number' => DocumentNumberService::nextQuoteNumber(),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => round($subtotal + $tax, 2),
                'currency' => strtoupper($validated['currency'] ?? 'USD'),
                'status' => $validated['status'] ?? 'draft',
                'created_by' => $request->user()->id,
            ]);

            foreach ($lineItems as $item) {
                $quotation->lineItems()->create($item);
            }

            return $quotation;
        });

        $this->notifyMisCreated(
            'finance',
            $quotation->quote_number ?: __('Quotation'),
            route('finance.quotations.print', $quotation, false),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Quotation created.',
        ]);

        return back();
    }

    public function destroy(Request $request, Quotation $quotation): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.delete');

        $label = $quotation->quote_number ?: __('Quotation');
        $quotation->delete();

        $this->notifyMisDeleted('finance', $label, route('finance.quotations', [], false));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Quotation deleted.',
        ]);

        return back();
    }

    public function print(Request $request, Quotation $quotation): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $quotation->load(['organization', 'lineItems']);

        return Inertia::render('mis/finance/QuotationPrint', [
            'quotation' => [
                'id' => $quotation->id,
                'quote_number' => $quotation->quote_number,
                'quote_date' => $quotation->quote_date?->format('d M Y'),
                'valid_until' => $quotation->valid_until?->format('d M Y'),
                'description_of_work' => $quotation->description_of_work,
                'notes' => $quotation->notes,
                'subtotal' => (float) $quotation->subtotal,
                'tax' => (float) $quotation->tax,
                'total' => (float) $quotation->total,
                'currency' => $quotation->currency ?: 'USD',
                'status' => $quotation->status,
                'organization' => $quotation->organization ? [
                    'id' => $quotation->organization->id,
                    'name' => $quotation->organization->name,
                    'address' => $quotation->organization->address,
                    'email' => $quotation->organization->email,
                    'phone' => $quotation->organization->phone,
                ] : null,
                'line_items' => $quotation->lineItems->map(fn ($item) => [
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) $item->total,
                ])->values()->all(),
            ],
            'company' => CompanyDocument::profile(),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array{description: string, quantity: float, unit_price: float, total: float}>
     */
    private function normalizedLines(array $lines): array
    {
        $items = [];

        foreach ($lines as $line) {
            $description = trim((string) ($line['description'] ?? ''));

            if ($description === '') {
                continue;
            }

            $quantity = (float) ($line['quantity'] ?? 1);
            $unitPrice = (float) ($line['unit_price'] ?? 0);

            $items[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
            ];
        }

        return $items;
    }
}
