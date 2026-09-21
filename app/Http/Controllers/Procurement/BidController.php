<?php

namespace App\Http\Controllers\Procurement;

use App\Enums\BidStatus;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\GeneratesMisReferenceNumbers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreBidRequest;
use App\Http\Requests\Procurement\UpdateBidRequest;
use App\Models\Procurement\Bid;
use App\Models\Procurement\CompetitorBid;
use App\Services\BidToProjectService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BidController extends Controller
{
    use AuthorizesMisPermissions, GeneratesMisReferenceNumbers;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'bidding.view');

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $dateFrom = $request->filled('date_from') ? $request->date('date_from')?->toDateString() : null;
        $dateTo = $request->filled('date_to') ? $request->date('date_to')?->toDateString() : null;

        $bids = Bid::query()
            ->with(['procurementOpportunity.organization'])
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('bid_number', 'like', "%{$search}%")
                    ->orWhereHas('procurementOpportunity', fn ($oq) => $oq->where('title', 'like', "%{$search}%"));
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($dateFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('submitted_at', '<=', $dateTo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $byStatus = Bid::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();

        return Inertia::render('mis/bidding/Bids/Index', [
            'bids' => $bids,
            'stats' => [
                'total' => Bid::query()->count(),
                'draft' => (int) ($byStatus[BidStatus::Draft->value] ?? 0),
                'submitted' => (int) ($byStatus[BidStatus::Submitted->value] ?? 0)
                    + (int) ($byStatus[BidStatus::UnderReview->value] ?? 0),
                'won' => (int) ($byStatus[BidStatus::Won->value] ?? 0),
                'lost' => (int) ($byStatus[BidStatus::Lost->value] ?? 0),
                'cancelled' => (int) ($byStatus[BidStatus::Cancelled->value] ?? 0),
                'by_status' => $byStatus,
            ],
            'chart' => [
                'status' => collect(BidStatus::cases())->map(fn (BidStatus $case) => [
                    'key' => $case->value,
                    'label' => ucfirst(str_replace('_', ' ', $case->value)),
                    'value' => (int) ($byStatus[$case->value] ?? 0),
                ])->values()->all(),
                'monthly' => $this->countCreatedByMonth(Bid::query()),
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
     * @param  Builder<Bid>  $query
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

    public function store(StoreBidRequest $request): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.create');

        $validated = $request->validated();
        $lineItems = $validated['line_items'] ?? [];
        unset($validated['line_items']);

        $bid = Bid::query()->create([
            ...$validated,
            'bid_number' => filled($validated['bid_number'] ?? null)
                ? $validated['bid_number']
                : $this->generateBidNumber(),
            'status' => $validated['status'] ?? BidStatus::Draft->value,
            'created_by' => $request->user()->id,
        ]);

        foreach ($lineItems as $item) {
            $bid->lineItems()->create($item);
        }

        return redirect()
            ->route('bidding.bids.show', $bid)
            ->with('success', 'Bid created.');
    }

    public function show(Request $request, Bid $bid): Response
    {
        $this->authorizePermission($request, 'bidding.view');

        $bid->load([
            'procurementOpportunity.organization',
            'lineItems',
            'project',
        ]);

        $competitors = $request->user()->can('bidding.view_competitors')
            ? CompetitorBid::query()
                ->where('procurement_opportunity_id', $bid->procurement_opportunity_id)
                ->get()
            : collect();

        return Inertia::render('mis/bidding/Bids/Show', [
            'bid' => $bid,
            'competitors' => $competitors,
        ]);
    }

    public function update(UpdateBidRequest $request, Bid $bid): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.edit');

        $validated = $request->validated();
        $lineItems = $validated['line_items'] ?? null;
        unset($validated['line_items']);

        $bid->update($validated);

        if (is_array($lineItems)) {
            $bid->lineItems()->delete();
            foreach ($lineItems as $item) {
                unset($item['id']);
                $bid->lineItems()->create($item);
            }
        }

        return back()->with('success', 'Bid updated.');
    }

    public function destroy(Request $request, Bid $bid): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.delete');

        if ($bid->project_id) {
            return back()->withErrors(['bid' => 'Cannot delete a bid linked to a project.']);
        }

        $bid->delete();

        return redirect()
            ->route('bidding.bids.index')
            ->with('success', 'Bid deleted.');
    }

    public function markWon(Request $request, Bid $bid, BidToProjectService $bidToProjectService): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.edit');

        if ($bid->status === BidStatus::Won->value) {
            return back()->withErrors(['bid' => 'Bid is already marked as won.']);
        }

        $project = $bidToProjectService->promote($bid);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Bid marked as won and project created.');
    }

    public function markLost(Request $request, Bid $bid): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.edit');

        $validated = $request->validate([
            'loss_reason' => ['nullable', 'string'],
            'winning_competitor_name' => ['nullable', 'string', 'max:255'],
            'winning_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $bid->update([
            ...$validated,
            'status' => BidStatus::Lost->value,
        ]);

        return back()->with('success', 'Bid marked as lost.');
    }
}
