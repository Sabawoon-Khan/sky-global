<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Procurement\CompetitorBid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompetitorBidController extends Controller
{
    use AuthorizesMisPermissions;

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.view_competitors');

        $validated = $request->validate([
            'procurement_opportunity_id' => ['required', 'exists:procurement_opportunities,id'],
            'competitor_name' => ['required', 'string', 'max:255'],
            'bid_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_winner' => ['boolean'],
            'is_estimated' => ['boolean'],
            'source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        CompetitorBid::query()->create($validated);

        return back()->with('success', 'Competitor bid recorded.');
    }

    public function update(Request $request, CompetitorBid $competitorBid): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.view_competitors');

        $validated = $request->validate([
            'competitor_name' => ['sometimes', 'required', 'string', 'max:255'],
            'bid_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_winner' => ['boolean'],
            'is_estimated' => ['boolean'],
            'source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $competitorBid->update($validated);

        return back()->with('success', 'Competitor bid updated.');
    }

    public function destroy(Request $request, CompetitorBid $competitorBid): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.view_competitors');

        $competitorBid->delete();

        return back()->with('success', 'Competitor bid removed.');
    }
}
