<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreProcurementOpportunityRequest;
use App\Http\Requests\Procurement\UpdateProcurementOpportunityRequest;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Procurement\ProcurementOpportunity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $dateFrom = $request->filled('date_from') ? $request->date('date_from')?->toDateString() : null;
        $dateTo = $request->filled('date_to') ? $request->date('date_to')?->toDateString() : null;

        $opportunities = ProcurementOpportunity::query()
            ->with('organization')
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($dateFrom, fn ($query) => $query->whereDate('submission_deadline', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('submission_deadline', '<=', $dateTo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('mis/bidding/Opportunities/Index', [
            'opportunities' => $opportunities,
            'stats' => [
                'total' => ProcurementOpportunity::query()->count(),
                'open' => ProcurementOpportunity::query()->where('status', 'open')->count(),
                'closed' => ProcurementOpportunity::query()->where('status', 'closed')->count(),
                'awarded' => ProcurementOpportunity::query()->where('status', 'awarded')->count(),
                'cancelled' => ProcurementOpportunity::query()->where('status', 'cancelled')->count(),
            ],
            'chart' => [
                'status' => [
                    ['key' => 'open', 'label' => 'Open', 'value' => ProcurementOpportunity::query()->where('status', 'open')->count()],
                    ['key' => 'closed', 'label' => 'Closed', 'value' => ProcurementOpportunity::query()->where('status', 'closed')->count()],
                    ['key' => 'awarded', 'label' => 'Awarded', 'value' => ProcurementOpportunity::query()->where('status', 'awarded')->count()],
                    ['key' => 'cancelled', 'label' => 'Cancelled', 'value' => ProcurementOpportunity::query()->where('status', 'cancelled')->count()],
                ],
                'monthly' => $this->countCreatedByMonth(ProcurementOpportunity::query()),
            ],
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * @param  Builder<ProcurementOpportunity>  $query
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
            'currency' => $request->validated('currency') ?? 'AFN',
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
