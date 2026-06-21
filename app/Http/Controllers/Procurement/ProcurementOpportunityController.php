<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreProcurementOpportunityRequest;
use App\Http\Requests\Procurement\UpdateProcurementOpportunityRequest;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Procurement\ProcurementOpportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProcurementOpportunityController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'bidding.view');

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $opportunities = ProcurementOpportunity::query()
            ->with('organization')
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('mis/bidding/Opportunities/Index', [
            'opportunities' => $opportunities,
            'stats' => [
                'total' => ProcurementOpportunity::query()->count(),
                'open' => ProcurementOpportunity::query()->where('status', 'open')->count(),
                'closed' => ProcurementOpportunity::query()->where('status', 'closed')->count(),
            ],
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'bidding.create');

        return Inertia::render('mis/bidding/Opportunities/Create', [
            'organizations' => Organization::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'organization_type_id']),
            'organizationTypes' => OrganizationType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreProcurementOpportunityRequest $request): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.create');

        $opportunity = ProcurementOpportunity::query()->create([
            ...$request->validated(),
            'currency' => $request->validated('currency') ?? 'USD',
            'status' => $request->validated('status') ?? 'open',
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('bidding.opportunities.show', $opportunity)
            ->with('success', 'Opportunity created.');
    }

    public function show(Request $request, ProcurementOpportunity $opportunity): Response
    {
        $this->authorizePermission($request, 'bidding.view');

        $opportunity->load([
            'organization',
            'bids' => fn ($q) => $q->latest(),
            'competitorBids',
        ]);

        return Inertia::render('mis/bidding/Opportunities/Show', [
            'opportunity' => $opportunity,
            'organizations' => Organization::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProcurementOpportunityRequest $request, ProcurementOpportunity $opportunity): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.edit');

        $opportunity->update($request->validated());

        return back()->with('success', 'Opportunity updated.');
    }

    public function destroy(Request $request, ProcurementOpportunity $opportunity): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.delete');

        if ($opportunity->bids()->exists()) {
            return back()->withErrors(['opportunity' => 'Cannot delete an opportunity with bids.']);
        }

        $opportunity->delete();

        return redirect()
            ->route('bidding.opportunities.index')
            ->with('success', 'Opportunity deleted.');
    }
}
