<?php

namespace App\Services;

use App\Models\Archive\ArchivedDocument;
use App\Models\Equipment\EquipmentStock;
use App\Models\Hr\AttendanceSheet;
use App\Models\Hr\PayrollRun;
use App\Models\Organization;
use App\Models\Procurement\Bid;
use App\Models\Project\Project;
use App\Models\Training\TrainingFieldReport;
use App\Models\Training\TrainingGuard;
use App\Models\User;
use App\Support\CompanyDocument;
use Illuminate\Support\Carbon;

class MisSystemReportService
{
    public function __construct(
        private AnalyticsService $analytics,
        private MisReportCatalogService $catalog,
    ) {}

    /**
     * @return array{
     *     year: int|null,
     *     year_label: string,
     *     years: list<int>,
     *     sections: list<array<string, mixed>>,
     *     generated_on: string,
     *     company: array{name: string}
     * }
     */
    public function buildForUser(User $user, ?int $year, ?string $moduleFilter = null): array
    {
        $dashboard = $this->analytics->dashboard();
        $sections = [];

        if ($user->can('projects.view')) {
            $sections[] = $this->projectsSection($dashboard);
        }

        if ($user->can('bidding.view')) {
            $sections[] = $this->biddingSection($dashboard);
        }

        if ($user->can('finance.view')) {
            $sections[] = $this->financeSection($year);
        }

        if ($user->can('hr.view')) {
            $sections[] = $this->hrSection($dashboard);
        }

        if ($user->can('training.view')) {
            $sections[] = $this->trainingSection();
        }

        if ($user->can('inventory.view')) {
            $sections[] = $this->inventorySection();
        }

        if ($user->can('archive.view')) {
            $sections[] = $this->archiveSection();
        }

        if ($moduleFilter !== null && $moduleFilter !== '') {
            $sections = array_values(array_filter(
                $sections,
                fn (array $section) => ($section['key'] ?? '') === $moduleFilter,
            ));
        }

        $sections = $this->attachDetailLinks($sections, $user);

        $company = CompanyDocument::profile();
        $yearLabel = $year === null ? __('All years') : (string) $year;

        return [
            'year' => $year,
            'year_label' => $yearLabel,
            'years' => $this->yearOptions(),
            'sections' => $sections,
            'generated_on' => Carbon::now()->translatedFormat('M j, Y H:i'),
            'company' => [
                'name' => $company['name'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    private function projectsSection(array $dashboard): array
    {
        $stats = $dashboard['projects'] ?? [];
        $topProjects = $this->analytics->projectProfitability(activeOnly: true)
            ->take(8)
            ->map(fn (array $row) => [
                'code' => $row['code'],
                'name' => $row['name'],
                'organization' => $row['organization'] ?? '—',
                'margin' => $row['margin'],
            ])
            ->values()
            ->all();

        return [
            'key' => 'projects',
            'module_label_key' => 'reports.module.projects',
            'metrics' => [
                $this->metric('total_projects', 'reports.metric.total_projects', (int) ($stats['total'] ?? 0)),
                $this->metric('active_projects', 'reports.metric.active_projects', (int) ($stats['active'] ?? 0)),
                $this->metric('planning_projects', 'reports.metric.planning_projects', (int) ($stats['planning'] ?? 0)),
            ],
            'tables' => $topProjects === [] ? [] : [[
                'key' => 'top_projects',
                'title_key' => 'reports.table.top_projects',
                'columns' => [
                    ['key' => 'code', 'label_key' => 'Project'],
                    ['key' => 'name', 'label_key' => 'Name'],
                    ['key' => 'organization', 'label_key' => 'Client'],
                    ['key' => 'margin', 'label_key' => 'Margin', 'format' => 'currency'],
                ],
                'rows' => $topProjects,
            ]],
        ];
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    private function biddingSection(array $dashboard): array
    {
        $stats = $dashboard['bidding'] ?? [];
        $recentBids = $this->analytics->bidAnalytics()
            ->take(10)
            ->map(fn (array $bid) => [
                'bid' => $bid['bid_number'] ?? (string) $bid['id'],
                'organization' => $bid['organization'] ?? '—',
                'status' => $bid['status'],
                'amount' => $bid['our_total_amount'],
                'currency' => $bid['currency'] ?? 'AFN',
            ])
            ->values()
            ->all();

        return [
            'key' => 'bidding',
            'module_label_key' => 'reports.module.bidding',
            'metrics' => [
                $this->metric('open_opportunities', 'reports.metric.open_opportunities', (int) ($stats['open_opportunities'] ?? 0)),
                $this->metric('pending_bids', 'reports.metric.pending_bids', (int) ($stats['pending_bids'] ?? 0)),
                $this->metric('win_rate', 'reports.metric.win_rate', (float) ($stats['win_rate'] ?? 0), 'percent'),
                $this->metric('won_bids', 'reports.metric.won_bids', (int) ($stats['won'] ?? 0)),
                $this->metric('lost_bids', 'reports.metric.lost_bids', (int) ($stats['lost'] ?? 0)),
                $this->metric('competitor_intel', 'reports.metric.competitor_intel', (int) ($dashboard['competitor_intel'] ?? 0)),
            ],
            'tables' => $recentBids === [] ? [] : [[
                'key' => 'recent_bids',
                'title_key' => 'reports.table.recent_bids',
                'columns' => [
                    ['key' => 'bid', 'label_key' => 'Bid'],
                    ['key' => 'organization', 'label_key' => 'Organization'],
                    ['key' => 'status', 'label_key' => 'Status'],
                    ['key' => 'amount', 'label_key' => 'Amount', 'format' => 'currency'],
                ],
                'rows' => $recentBids,
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function financeSection(?int $year): array
    {
        $report = $this->analytics->financeReport($year ?? now()->year);
        $stats = $report['stats'] ?? [];

        $statementRows = collect($report['statement'] ?? [])
            ->map(fn (array $row) => [
                'line' => $row['key'],
                'amount' => $row['amount'],
            ])
            ->values()
            ->all();

        $projectRows = collect($report['projectProfitability'] ?? [])
            ->take(10)
            ->map(fn (array $row) => [
                'code' => $row['code'],
                'name' => $row['name'],
                'income' => $row['income'],
                'expense' => $row['expense'],
                'margin' => $row['margin'],
            ])
            ->values()
            ->all();

        $tables = [];

        if ($statementRows !== []) {
            $tables[] = [
                'key' => 'profit_and_loss',
                'title_key' => 'Profit and Loss',
                'columns' => [
                    ['key' => 'line', 'label_key' => 'Field', 'format' => 'statement_key'],
                    ['key' => 'amount', 'label_key' => 'Amount', 'format' => 'currency'],
                ],
                'rows' => $statementRows,
            ];
        }

        if ($projectRows !== []) {
            $tables[] = [
                'key' => 'project_profitability',
                'title_key' => 'reports.table.project_profitability',
                'columns' => [
                    ['key' => 'code', 'label_key' => 'Project'],
                    ['key' => 'income', 'label_key' => 'Income', 'format' => 'currency'],
                    ['key' => 'expense', 'label_key' => 'Expense', 'format' => 'currency'],
                    ['key' => 'margin', 'label_key' => 'Margin', 'format' => 'currency'],
                ],
                'rows' => $projectRows,
            ];
        }

        return [
            'key' => 'finance',
            'module_label_key' => 'reports.module.finance',
            'period_label' => $report['year_label'] ?? (string) ($year ?? now()->year),
            'metrics' => [
                $this->metric('total_income', 'Total Income', (float) ($stats['total_income'] ?? 0), 'currency'),
                $this->metric('total_expense', 'Total Expenses', (float) ($stats['total_expense'] ?? 0), 'currency'),
                $this->metric('operating_net', 'Operating net', (float) ($stats['operating_net'] ?? 0), 'currency'),
                $this->metric('net_after_tax', 'Net after tax', (float) ($stats['net_after_tax'] ?? 0), 'currency'),
                $this->metric('outstanding', 'Outstanding invoices', (float) ($stats['outstanding'] ?? 0), 'currency'),
                $this->metric('margin_percent', 'Margin %', (float) ($stats['margin_percent'] ?? 0), 'percent'),
            ],
            'tables' => $tables,
        ];
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    private function hrSection(array $dashboard): array
    {
        $stats = $dashboard['hr'] ?? [];
        $latestPayroll = PayrollRun::query()
            ->withSum('items as total_net', 'net_amount')
            ->latest('date_to')
            ->limit(5)
            ->get(['id', 'title', 'date_from', 'date_to', 'status'])
            ->map(fn (PayrollRun $run) => [
                'label' => $run->title,
                'period' => $run->date_from?->format('Y-m-d').' – '.$run->date_to?->format('Y-m-d'),
                'status' => $run->status,
                'total_net' => (float) ($run->total_net ?? 0),
            ])
            ->values()
            ->all();

        return [
            'key' => 'hr',
            'module_label_key' => 'reports.module.hr',
            'metrics' => [
                $this->metric('active_employees', 'reports.metric.active_employees', (int) ($stats['employees'] ?? 0)),
                $this->metric('active_contractors', 'reports.metric.active_contractors', (int) ($stats['contractors'] ?? 0)),
                $this->metric('expiring_documents', 'reports.metric.expiring_documents', (int) ($stats['expiring_documents'] ?? 0)),
                $this->metric('attendance_sheets', 'reports.metric.attendance_sheets', AttendanceSheet::query()->count()),
                $this->metric('payroll_runs', 'reports.metric.payroll_runs', PayrollRun::query()->count()),
            ],
            'tables' => $latestPayroll === [] ? [] : [[
                'key' => 'recent_payroll',
                'title_key' => 'reports.table.recent_payroll',
                'columns' => [
                    ['key' => 'label', 'label_key' => 'Label'],
                    ['key' => 'period', 'label_key' => 'Period'],
                    ['key' => 'status', 'label_key' => 'Status'],
                    ['key' => 'total_net', 'label_key' => 'Net pay', 'format' => 'currency'],
                ],
                'rows' => $latestPayroll,
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function trainingSection(): array
    {
        $guards = TrainingGuard::query()->count();
        $activeGuards = TrainingGuard::query()->where('status', 'active')->count();
        $fieldReports = TrainingFieldReport::query()->count();

        return [
            'key' => 'training',
            'module_label_key' => 'reports.module.training',
            'metrics' => [
                $this->metric('training_guards', 'reports.metric.training_guards', $guards),
                $this->metric('active_guards', 'reports.metric.active_guards', $activeGuards),
                $this->metric('field_reports', 'reports.metric.field_reports', $fieldReports),
            ],
            'tables' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function inventorySection(): array
    {
        $stockLines = EquipmentStock::query()->count();
        $totalQty = (int) EquipmentStock::query()->sum('quantity_on_hand');

        return [
            'key' => 'inventory',
            'module_label_key' => 'reports.module.inventory',
            'metrics' => [
                $this->metric('stock_lines', 'reports.metric.stock_lines', $stockLines),
                $this->metric('stock_quantity', 'reports.metric.stock_quantity', $totalQty),
            ],
            'tables' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function archiveSection(): array
    {
        $documents = ArchivedDocument::query()->count();
        $organizations = Organization::query()->count();

        return [
            'key' => 'archive',
            'module_label_key' => 'reports.module.archive',
            'metrics' => [
                $this->metric('archived_documents', 'reports.metric.archived_documents', $documents),
                $this->metric('organizations', 'reports.metric.organizations', $organizations),
            ],
            'tables' => [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    private function attachDetailLinks(array $sections, User $user): array
    {
        $catalog = $this->catalog->catalogForUser($user);
        $allItems = [];

        foreach ($catalog['groups'] as $group) {
            foreach ($group['items'] as $item) {
                $allItems[$item['key']] = $item;
            }
        }

        /** @var array<string, list<string>> $linkKeysBySection */
        $linkKeysBySection = [
            'bidding' => ['bidding_overview'],
            'finance' => ['finance_statement', 'monthly_ledger', 'company_tax', 'invoices', 'quotations'],
            'hr' => ['attendance', 'payroll', 'employee_history'],
            'training' => ['guard_certificate'],
        ];

        foreach ($sections as $index => $section) {
            $key = $section['key'] ?? '';
            $links = [];

            foreach ($linkKeysBySection[$key] ?? [] as $itemKey) {
                if (isset($allItems[$itemKey])) {
                    $links[] = $allItems[$itemKey];
                }
            }

            $sections[$index]['detail_links'] = $links;
        }

        return $sections;
    }

    /** @return list<int> */
    private function yearOptions(): array
    {
        $current = now()->year;

        return array_values(range($current, $current - 10));
    }

    /**
     * @return array{key: string, label_key: string, value: float|int, format: string}
     */
    private function metric(string $key, string $labelKey, float|int $value, string $format = 'number'): array
    {
        return [
            'key' => $key,
            'label_key' => $labelKey,
            'value' => $value,
            'format' => $format,
        ];
    }
}
