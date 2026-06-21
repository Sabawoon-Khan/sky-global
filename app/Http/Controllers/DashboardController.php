<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use AuthorizesMisPermissions;

    public function __invoke(Request $request, AnalyticsService $analytics): Response
    {
        if ($request->user()?->can('projects.view')) {
            return Inertia::render('Dashboard', [
                'stats' => $analytics->dashboard(),
                'projectProfitability' => $analytics->projectProfitability(),
                'expiringDocuments' => $analytics->expiringDocuments(),
            ]);
        }

        return Inertia::render('Dashboard', [
            'stats' => null,
            'projectProfitability' => [],
            'expiringDocuments' => [],
        ]);
    }
}
