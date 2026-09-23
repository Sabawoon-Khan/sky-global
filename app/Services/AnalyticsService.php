<?php

namespace App\Services;

use App\Models\Finance\Currency;
use App\Models\Finance\ExchangeRate;
use App\Models\Finance\GeneralExpense;
use App\Models\Finance\GeneralIncome;
use App\Models\Finance\Invoice;
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use App\Models\Finance\TaxPayment;
use App\Models\Forms\PersonnelAttachment;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\OrganizationType;
use App\Models\Procurement\Bid;
use App\Models\Procurement\CompetitorBid;
use App\Models\Procurement\ProcurementOpportunity;
use App\Models\Project\Project;
use App\Support\CompanyDocument;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /** @return array<string, mixed> */
    public function dashboard(): array
    {
        $totalBids = Bid::query()->count();
        $wonBids = Bid::query()->where('status', 'won')->count();
        $lostBids = Bid::query()->where('status', 'lost')->count();
        $ourBidByCurrency = Project::query()
            ->selectRaw("UPPER(COALESCE(currency, 'AFN')) as currency, SUM(COALESCE(our_bid_amount, 0)) as total")
            ->whereNotNull('our_bid_amount')
            ->groupByRaw("UPPER(COALESCE(currency, 'AFN'))")
            ->orderBy('currency')
            ->get()
            ->map(fn ($row) => [
                'currency' => (string) $row->currency,
                'total' => (float) $row->total,
            ])
            ->values()
            ->all();
        $projectIncomeUsd = (float) ProjectIncome::query()->sum('amount');
        $generalIncomeUsd = (float) GeneralIncome::query()->sum('amount');
        $totalIncomeUsd = $projectIncomeUsd + $generalIncomeUsd;
        $totalExpenseUsd = (float) ProjectExpense::query()->sum('amount');
        $overheadUsd = (float) GeneralExpense::query()->sum('amount');
        $netByCurrency = $this->netFinanceByCurrency();

        return [
            'bidding' => [
                'open_opportunities' => ProcurementOpportunity::query()->where('status', 'open')->count(),
                'pending_bids' => Bid::query()->whereIn('status', ['draft', 'submitted', 'under_review'])->count(),
                'win_rate' => $totalBids > 0 ? round(($wonBids / $totalBids) * 100, 1) : 0,
                'won' => $wonBids,
                'lost' => $lostBids,
                'our_bid_by_currency' => $ourBidByCurrency,
            ],
            'projects' => [
                'active' => Project::query()->where('status', 'active')->count(),
                'planning' => Project::query()->where('status', 'planning')->count(),
                'total' => Project::query()->count(),
            ],
            'finance' => [
                'total_income_usd' => $totalIncomeUsd,
                'project_income_usd' => $projectIncomeUsd,
                'general_income_usd' => $generalIncomeUsd,
                'total_expense_usd' => $totalExpenseUsd,
                'overhead_usd' => $overheadUsd,
                'net_usd' => $totalIncomeUsd - $totalExpenseUsd - $overheadUsd,
                'net_by_currency' => $netByCurrency->values()->all(),
                'currencies' => Currency::query()
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('code')
                    ->get(['code', 'name', 'symbol', 'is_default'])
                    ->map(fn (Currency $currency) => [
                        'code' => $currency->code,
                        'name' => $currency->name,
                        'symbol' => $currency->symbol,
                        'is_default' => (bool) $currency->is_default,
                    ])
                    ->values()
                    ->all(),
                'exchange_rates' => ExchangeRate::query()
                    ->where('to_currency', 'USD')
                    ->orderByDesc('effective_date')
                    ->get(['from_currency', 'to_currency', 'rate', 'effective_date'])
                    ->unique('from_currency')
                    ->map(fn (ExchangeRate $rate) => [
                        'from_currency' => $rate->from_currency,
                        'to_currency' => $rate->to_currency,
                        'rate' => (float) $rate->rate,
                        'effective_date' => $rate->effective_date?->toDateString(),
                    ])
                    ->values()
                    ->all(),
            ],
            'hr' => [
                'employees' => Employee::query()->where('status', 'active')->count(),
                'contractors' => Contractor::query()->where('status', 'active')->count(),
                'expiring_documents' => PersonnelAttachment::query()
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now()->addDays(30))
                    ->count(),
            ],
            'organization_types' => $this->organizationTypeBreakdown(),
            'competitor_intel' => CompetitorBid::query()->count(),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function projectProfitability(?CarbonInterface $start = null, ?CarbonInterface $end = null, bool $activeOnly = false): Collection
    {
        $projects = Project::query()
            ->with('organization:id,name')
            ->withSum(
                ['incomes as income_total' => fn ($query) => $this->constrainDate($query, 'transaction_date', $start, $end)],
                'amount',
            )
            ->withSum(
                ['expenses as expense_total' => fn ($query) => $this->constrainDate($query, 'transaction_date', $start, $end)],
                'amount',
            )
            ->get()
            ->map(function (Project $project) {
                $income = (float) ($project->income_total ?? 0);
                $expense = (float) ($project->expense_total ?? 0);
                $margin = $income - $expense;

                return [
                    'id' => $project->id,
                    'code' => $project->code,
                    'name' => $project->name,
                    'organization' => $project->organization?->name,
                    'income' => $income,
                    'expense' => $expense,
                    'margin' => $margin,
                    'margin_percent' => $income > 0
                        ? round(($margin / $income) * 100, 1)
                        : ($expense > 0 ? -100.0 : 0.0),
                ];
            });

        if ($activeOnly) {
            $projects = $projects->filter(
                fn (array $project) => abs($project['income']) >= 0.01 || abs($project['expense']) >= 0.01,
            );
        }

        return $projects->sortByDesc('margin')->values();
    }

    /**
     * Year-scoped finance report used by analytics and the printable statement.
     *
     * @return array<string, mixed>
     */
    public function financeReport(?int $year): array
    {
        [$start, $end] = $this->yearBounds($year);
        $totals = $this->financeTotals($start, $end);
        $previous = null;

        if ($year !== null) {
            $previous = $this->financeTotals(
                Carbon::create($year - 1, 1, 1)->startOfDay(),
                Carbon::create($year - 1, 12, 31)->endOfDay(),
            );
        }

        $companyTax = app(AfghanistanCompanyTaxService::class);
        $taxSummary = $companyTax->summarize(
            $totals['total_income'],
            $totals['total_expense'],
            AfghanistanCompanyTaxService::YEARLY_RATE,
        );
        $taxPaid = $this->sumTaxPaid($year);
        $invoices = $this->invoiceSnapshot($start, $end);
        $projects = $this->projectProfitability($start, $end, true);

        return [
            'year' => $year,
            'year_label' => $year !== null ? (string) $year : 'All years',
            'years' => $this->availableYears(),
            'period_start' => $start?->toDateString(),
            'period_end' => $end?->toDateString(),
            'generated_on' => now()->toDayDateTimeString(),
            'company' => CompanyDocument::profile(),
            'stats' => [
                ...$totals,
                'tax_due' => $taxSummary['tax_due'],
                'tax_paid' => $taxPaid,
                'tax_remaining' => round(max(0, $taxSummary['tax_due'] - $taxPaid), 2),
                'net_after_tax' => round($totals['operating_net'] - $taxPaid, 2),
                'margin_percent' => $totals['total_income'] > 0
                    ? round(($totals['operating_net'] / $totals['total_income']) * 100, 1)
                    : 0.0,
                'billed' => $invoices['billed'],
                'collected' => $invoices['collected'],
                'outstanding' => $invoices['outstanding'],
                'overdue' => $invoices['overdue'],
            ],
            'previous' => $previous,
            'statement' => $this->profitAndLossRows($totals, $taxPaid, $taxSummary['tax_due']),
            'charts' => [
                'monthly' => $this->monthlyFinanceSeries($year),
                'income_mix' => [
                    ['key' => 'project_income', 'value' => $totals['project_income']],
                    ['key' => 'general_income', 'value' => $totals['general_income']],
                ],
                'expense_mix' => [
                    ['key' => 'project_expense', 'value' => $totals['project_expense']],
                    ['key' => 'overhead', 'value' => $totals['overhead']],
                ],
                'income_by_category' => $this->sumByCategory(
                    [ProjectIncome::class, GeneralIncome::class],
                    $start,
                    $end,
                ),
                'expense_by_category' => $this->sumByCategory(
                    [ProjectExpense::class, GeneralExpense::class],
                    $start,
                    $end,
                ),
            ],
            'currencies' => $this->netFinanceByCurrency($start, $end)->all(),
            'invoices' => $invoices,
            'tax' => [
                'rate_percent' => $taxSummary['rate_percent'],
                'due' => $taxSummary['tax_due'],
                'paid' => $taxPaid,
                'remaining' => round(max(0, $taxSummary['tax_due'] - $taxPaid), 2),
            ],
            'projectProfitability' => $projects,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function bidAnalytics(): Collection
    {
        return Bid::query()
            ->with('procurementOpportunity.organization')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Bid $bid) => [
                'id' => $bid->id,
                'bid_number' => $bid->bid_number,
                'status' => $bid->status,
                'our_total_amount' => $bid->our_total_amount,
                'winning_amount' => $bid->winning_amount,
                'currency' => $bid->currency,
                'organization' => $bid->procurementOpportunity?->organization?->name,
                'submitted_at' => $bid->submitted_at,
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    public function expiringDocuments(): Collection
    {
        return PersonnelAttachment::query()
            ->with('attachmentType')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->orderBy('expires_at')
            ->get()
            ->map(fn (PersonnelAttachment $attachment) => [
                'id' => $attachment->id,
                'personnel_type' => $attachment->personnel_type,
                'personnel_id' => $attachment->personnel_id,
                'type' => $attachment->attachmentType?->name,
                'expires_at' => $attachment->expires_at?->toDateString(),
            ]);
    }

    /** @return array<string, mixed> */
    public function chartData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $i) => now()->subMonths($i));

        $monthlyFinance = $months->map(function ($date) {
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $projectIncome = $this->sumInRange(ProjectIncome::class, 'transaction_date', $start, $end);
            $generalIncome = $this->sumInRange(GeneralIncome::class, 'transaction_date', $start, $end);
            $projectExpense = $this->sumInRange(ProjectExpense::class, 'transaction_date', $start, $end);
            $overhead = $this->sumInRange(GeneralExpense::class, 'transaction_date', $start, $end);
            $income = $projectIncome + $generalIncome;
            $expense = $projectExpense + $overhead;

            return [
                'label' => $start->format('M Y'),
                'income' => $income,
                'expense' => $expense,
                'project_income' => $projectIncome,
                'general_income' => $generalIncome,
                'project_expense' => $projectExpense,
                'overhead' => $overhead,
                'net' => $income - $expense,
            ];
        });

        $projectStatuses = Project::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $generalExpenseTable = (new GeneralExpense)->getTable();
        $expenseByCategory = GeneralExpense::query()
            ->selectRaw(
                "COALESCE(`{$generalExpenseTable}`.`category`, 'other') as category, sum(`{$generalExpenseTable}`.`amount`) as total",
            )
            ->groupByRaw('1')
            ->pluck('total', 'category');

        $topProjectsIncome = Project::query()
            ->with('organization')
            ->get()
            ->map(function (Project $project) {
                $income = $project->incomes()->sum('amount');

                return [
                    'code' => $project->code,
                    'income' => (float) $income,
                ];
            })
            ->sortByDesc('income')
            ->take(8)
            ->values()
            ->all();

        $monthlyBids = $months->map(function ($date) {
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $won = Project::query()
                ->where('status', 'won')
                ->whereBetween('updated_at', [$start, $end])
                ->count();
            $lost = Project::query()
                ->where('status', 'lost')
                ->whereBetween('updated_at', [$start, $end])
                ->count();
            $decided = $won + $lost;

            return [
                'label' => $start->format('M Y'),
                'submitted' => Project::query()
                    ->whereNotNull('bid_submitted_at')
                    ->whereBetween('bid_submitted_at', [$start, $end])
                    ->count(),
                'won' => $won,
                'lost' => $lost,
                'win_rate' => $decided > 0 ? round(($won / $decided) * 100, 1) : 0,
            ];
        });

        $bidStatuses = Bid::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $orgTypes = $this->organizationTypeBreakdown()->map(fn ($type) => [
            'name' => $type['name'],
            'projects_count' => $type['projects_count'],
            'total_contract_value' => $type['total_contract_value'],
        ])->values()->all();

        $totalProjectIncome = (float) ProjectIncome::query()->sum('amount');
        $totalGeneralIncome = (float) GeneralIncome::query()->sum('amount');
        $totalProjectExpense = (float) ProjectExpense::query()->sum('amount');
        $totalOverhead = (float) GeneralExpense::query()->sum('amount');

        return [
            'monthly_finance' => $monthlyFinance->values()->all(),
            'monthly_bids' => $monthlyBids->values()->all(),
            'project_statuses' => $projectStatuses->map(fn ($count, $status) => [
                'status' => (string) $status,
                'count' => (int) $count,
            ])->values()->all(),
            'workforce' => [
                'employees' => Employee::query()->where('status', 'active')->count(),
                'contractors' => Contractor::query()->where('status', 'active')->count(),
            ],
            'bidding_outcomes' => [
                ['key' => 'won', 'value' => Bid::query()->where('status', 'won')->count()],
                ['key' => 'lost', 'value' => Bid::query()->where('status', 'lost')->count()],
                ['key' => 'pending', 'value' => Bid::query()->whereIn('status', ['draft', 'submitted', 'under_review'])->count()],
            ],
            'finance_breakdown' => [
                ['key' => 'project_income', 'value' => $totalProjectIncome],
                ['key' => 'general_income', 'value' => $totalGeneralIncome],
                ['key' => 'project_expense', 'value' => $totalProjectExpense],
                ['key' => 'overhead', 'value' => $totalOverhead],
            ],
            'expense_by_category' => $expenseByCategory->map(fn ($total, $category) => [
                'category' => (string) $category,
                'value' => (float) $total,
            ])->values()->all(),
            'top_projects_income' => $topProjectsIncome,
            'organization_types' => $orgTypes,
            'bid_statuses' => $bidStatuses->map(fn ($count, $status) => [
                'status' => (string) $status,
                'count' => (int) $count,
            ])->values()->all(),
        ];
    }

    private function sumInRange(string $modelClass, string $dateColumn, $start, $end): float
    {
        return (float) $modelClass::query()
            ->whereBetween($dateColumn, [$start, $end])
            ->sum('amount');
    }

    /**
     * @return array{
     *     project_income: float,
     *     general_income: float,
     *     total_income: float,
     *     project_expense: float,
     *     overhead: float,
     *     total_expense: float,
     *     operating_net: float
     * }
     */
    private function financeTotals(?CarbonInterface $start, ?CarbonInterface $end): array
    {
        $projectIncome = $this->sumModels([ProjectIncome::class], $start, $end);
        $generalIncome = $this->sumModels([GeneralIncome::class], $start, $end);
        $projectExpense = $this->sumModels([ProjectExpense::class], $start, $end);
        $overhead = $this->sumModels([GeneralExpense::class], $start, $end);
        $totalIncome = $projectIncome + $generalIncome;
        $totalExpense = $projectExpense + $overhead;

        return [
            'project_income' => $projectIncome,
            'general_income' => $generalIncome,
            'total_income' => $totalIncome,
            'project_expense' => $projectExpense,
            'overhead' => $overhead,
            'total_expense' => $totalExpense,
            'operating_net' => round($totalIncome - $totalExpense, 2),
        ];
    }

    /**
     * @param  array<string, float>  $totals
     * @return list<array{key: string, amount: float, style: string}>
     */
    private function profitAndLossRows(array $totals, float $taxPaid, float $taxDue): array
    {
        return [
            ['key' => 'project_income', 'amount' => $totals['project_income'], 'style' => 'item'],
            ['key' => 'general_income', 'amount' => $totals['general_income'], 'style' => 'item'],
            ['key' => 'total_income', 'amount' => $totals['total_income'], 'style' => 'subtotal'],
            ['key' => 'project_expense', 'amount' => $totals['project_expense'], 'style' => 'item'],
            ['key' => 'overhead', 'amount' => $totals['overhead'], 'style' => 'item'],
            ['key' => 'total_expense', 'amount' => $totals['total_expense'], 'style' => 'subtotal'],
            ['key' => 'operating_net', 'amount' => $totals['operating_net'], 'style' => 'emphasis'],
            ['key' => 'tax_due', 'amount' => $taxDue, 'style' => 'item'],
            ['key' => 'tax_paid', 'amount' => $taxPaid, 'style' => 'item'],
            ['key' => 'net_after_tax', 'amount' => round($totals['operating_net'] - $taxPaid, 2), 'style' => 'total'],
        ];
    }

    /**
     * @return list<array<string, float|string>>
     */
    private function monthlyFinanceSeries(?int $year): array
    {
        $months = $year !== null
            ? collect(range(1, 12))->map(fn (int $month) => Carbon::create($year, $month, 1))
            : collect(range(11, 0))->map(fn (int $i) => now()->copy()->startOfMonth()->subMonths($i));

        return $months->map(function ($date) {
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();
            $totals = $this->financeTotals($start, $end);

            return [
                'label' => $start->format('M Y'),
                'income' => $totals['total_income'],
                'expense' => $totals['total_expense'],
                'project_income' => $totals['project_income'],
                'general_income' => $totals['general_income'],
                'project_expense' => $totals['project_expense'],
                'overhead' => $totals['overhead'],
                'net' => $totals['operating_net'],
            ];
        })->values()->all();
    }

    /**
     * @return array{
     *     billed: float,
     *     collected: float,
     *     outstanding: float,
     *     overdue: float,
     *     by_status: list<array{status: string, count: int, total: float}>
     * }
     */
    private function invoiceSnapshot(?CarbonInterface $start, ?CarbonInterface $end): array
    {
        $query = Invoice::query()->where('status', '!=', 'cancelled');
        $this->constrainDate($query, 'issue_date', $start, $end);

        $byStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => (string) $row->status,
                'count' => (int) $row->count,
                'total' => (float) $row->total,
            ])
            ->values()
            ->all();

        return [
            'billed' => (float) (clone $query)->sum('total'),
            'collected' => (float) (clone $query)->where('status', 'paid')->sum('total'),
            'outstanding' => (float) (clone $query)->whereIn('status', ['draft', 'sent', 'overdue'])->sum('total'),
            'overdue' => (float) (clone $query)->where('status', 'overdue')->sum('total'),
            'by_status' => $byStatus,
        ];
    }

    private function sumTaxPaid(?int $year): float
    {
        $query = TaxPayment::query();

        if ($year !== null) {
            [$start, $end] = $this->yearBounds($year);
            $query->whereBetween('payment_date', [$start?->toDateString(), $end?->toDateString()]);
        }

        return (float) $query->sum('amount');
    }

    /**
     * @return list<int>
     */
    private function availableYears(): array
    {
        $dates = collect();

        foreach ([ProjectIncome::class, GeneralIncome::class, ProjectExpense::class, GeneralExpense::class, Invoice::class] as $modelClass) {
            $column = $modelClass === Invoice::class ? 'issue_date' : 'transaction_date';
            $dates->push($modelClass::query()->min($column));
            $dates->push($modelClass::query()->max($column));
        }

        $dates = $dates->filter();
        $endYear = now()->year;
        $startYear = $dates->isEmpty()
            ? $endYear
            : min($endYear, Carbon::parse((string) $dates->min())->year);

        if ($dates->isNotEmpty()) {
            $endYear = max($endYear, Carbon::parse((string) $dates->max())->year);
        }

        return range($endYear, $startYear);
    }

    /**
     * @return array{0: ?CarbonInterface, 1: ?CarbonInterface}
     */
    private function yearBounds(?int $year): array
    {
        if ($year === null) {
            return [null, null];
        }

        return [
            Carbon::create($year, 1, 1)->startOfDay(),
            Carbon::create($year, 12, 31)->endOfDay(),
        ];
    }

    /** @param array<int, class-string> $modelClasses */
    private function sumModels(array $modelClasses, ?CarbonInterface $start, ?CarbonInterface $end): float
    {
        $total = 0.0;

        foreach ($modelClasses as $modelClass) {
            $query = $modelClass::query();
            $this->constrainDate($query, 'transaction_date', $start, $end);
            $total += (float) $query->sum('amount');
        }

        return round($total, 2);
    }

    /**
     * @param  array<int, class-string>  $modelClasses
     * @return list<array{category: string, value: float}>
     */
    private function sumByCategory(array $modelClasses, ?CarbonInterface $start, ?CarbonInterface $end): array
    {
        $totals = collect();

        foreach ($modelClasses as $modelClass) {
            $table = (new $modelClass)->getTable();
            $categoryExpression = "COALESCE(NULLIF(TRIM(`{$table}`.`category`), ''), 'Uncategorized')";

            $query = $modelClass::query()
                ->selectRaw("{$categoryExpression} as category, SUM(`{$table}`.`amount`) as total")
                ->groupByRaw('1');
            $this->constrainDate($query, 'transaction_date', $start, $end);

            foreach ($query->pluck('total', 'category') as $category => $total) {
                $totals[$category] = (float) ($totals->get($category, 0.0)) + (float) $total;
            }
        }

        return $totals
            ->sortDesc()
            ->map(fn ($value, $category) => [
                'category' => (string) $category,
                'value' => round((float) $value, 2),
            ])
            ->values()
            ->all();
    }

    /** @param  Builder|\Illuminate\Database\Query\Builder  $query */
    private function constrainDate($query, string $column, ?CarbonInterface $start, ?CarbonInterface $end): void
    {
        if ($start !== null && $end !== null) {
            $query->whereBetween($column, [$start->toDateString(), $end->toDateString()]);
        }
    }

    /** @return Collection<int, array<string, mixed>> */
    private function netFinanceByCurrency(?CarbonInterface $start = null, ?CarbonInterface $end = null): Collection
    {
        $incomeByCurrency = $this->sumByCurrency([ProjectIncome::class, GeneralIncome::class], $start, $end);
        $expenseByCurrency = $this->sumByCurrency([ProjectExpense::class, GeneralExpense::class], $start, $end);

        return $incomeByCurrency
            ->keys()
            ->merge($expenseByCurrency->keys())
            ->unique()
            ->sort()
            ->map(function (string $currency) use ($incomeByCurrency, $expenseByCurrency) {
                $income = (float) ($incomeByCurrency->get($currency, 0.0));
                $expense = (float) ($expenseByCurrency->get($currency, 0.0));

                return [
                    'currency' => $currency,
                    'income' => $income,
                    'expense' => $expense,
                    'net' => $income - $expense,
                ];
            })
            ->values();
    }

    /** @param array<int, class-string> $modelClasses */
    private function sumByCurrency(array $modelClasses, ?CarbonInterface $start = null, ?CarbonInterface $end = null): Collection
    {
        $totals = collect();

        foreach ($modelClasses as $modelClass) {
            $query = $modelClass::query()
                ->selectRaw("UPPER(COALESCE(currency, 'AFN')) as currency, SUM(amount) as total")
                ->groupByRaw("UPPER(COALESCE(currency, 'AFN'))");
            $this->constrainDate($query, 'transaction_date', $start, $end);

            foreach ($query->pluck('total', 'currency') as $currency => $total) {
                $totals[$currency] = (float) ($totals->get($currency, 0.0)) + (float) $total;
            }
        }

        return $totals;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function organizationTypeBreakdown(): Collection
    {
        return OrganizationType::query()
            ->withCount('organizations')
            ->get()
            ->map(function (OrganizationType $type) {
                $projectCount = Project::query()
                    ->whereHas('organization', fn ($q) => $q->where('organization_type_id', $type->id))
                    ->count();

                $revenue = Project::query()
                    ->whereHas('organization', fn ($q) => $q->where('organization_type_id', $type->id))
                    ->sum('total_contract_value');

                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'color' => $type->color,
                    'organizations_count' => $type->organizations_count,
                    'projects_count' => $projectCount,
                    'total_contract_value' => (float) $revenue,
                ];
            });
    }
}
