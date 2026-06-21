<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    use AuthorizesMisPermissions;

    public function bidding(Request $request, AnalyticsService $analytics): Response
    {
        $this->authorizePermission($request, 'bidding.view');

        $stats = $analytics->dashboard();

        return Inertia::render('mis/analytics/Bidding', [
            'stats' => $stats['bidding'] ?? [],
            'organizationTypes' => $stats['organization_types'] ?? [],
            'competitorIntel' => $stats['competitor_intel'] ?? 0,
            'bids' => $analytics->bidAnalytics(),
        ]);
    }

    public function finance(Request $request, AnalyticsService $analytics): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $stats = $analytics->dashboard();

        return Inertia::render('mis/analytics/Finance', [
            'stats' => $stats['finance'] ?? [],
            'projectProfitability' => $analytics->projectProfitability(),
        ]);
    }
}
