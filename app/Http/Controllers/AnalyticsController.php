<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Services\AnalyticsService;
use App\Services\MisSystemReportService;
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
            'charts' => $analytics->chartData(),
        ]);
    }

    public function finance(Request $request, AnalyticsService $analytics): Response
    {
        $this->authorizePermission($request, 'finance.view');

        return Inertia::render(
            'mis/analytics/Finance',
            $analytics->financeReport($this->resolveYear($request)),
        );
    }

    public function financePrint(Request $request, AnalyticsService $analytics): Response
    {
        $this->authorizePermission($request, 'finance.view');

        return Inertia::render(
            'mis/analytics/FinancePrint',
            $analytics->financeReport($this->resolveYear($request)),
        );
    }

    public function reports(Request $request, MisSystemReportService $systemReport): Response
    {
        return Inertia::render(
            'mis/analytics/Reports',
            $systemReport->buildForUser(
                $request->user(),
                $this->resolveReportYear($request),
            ),
        );
    }

    public function reportsPrint(Request $request, MisSystemReportService $systemReport): Response
    {
        $module = $request->string('module')->toString();

        return Inertia::render(
            'mis/analytics/ReportsPrint',
            $systemReport->buildForUser(
                $request->user(),
                $this->resolveReportYear($request),
                $module !== '' ? $module : null,
            ),
        );
    }

    private function resolveReportYear(Request $request): int
    {
        $year = $this->resolveYear($request);

        return $year ?? now()->year;
    }

    private function resolveYear(Request $request): ?int
    {
        $year = $request->string('year')->toString();

        if ($year === 'all') {
            return null;
        }

        if ($year === '' || ! ctype_digit($year)) {
            return now()->year;
        }

        $value = (int) $year;

        if ($value < 2000 || $value > 2100) {
            return now()->year;
        }

        return $value;
    }
}
