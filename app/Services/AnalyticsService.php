<?php

namespace App\Services;

use App\Models\Finance\Currency;
use App\Models\Finance\ExchangeRate;
use App\Models\Finance\GeneralExpense;
use App\Models\Finance\GeneralIncome;
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use App\Models\Forms\PersonnelAttachment;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\OrganizationType;
use App\Models\Procurement\Bid;
use App\Models\Procurement\CompetitorBid;
use App\Models\Procurement\ProcurementOpportunity;
use App\Models\Project\Project;
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
            ->selectRaw("UPPER(COALESCE(currency, 'USD')) as currency, SUM(COALESCE(our_bid_amount, 0)) as total")
            ->whereNotNull('our_bid_amount')
            ->groupByRaw("UPPER(COALESCE(currency, 'USD'))")
            ->orderBy('currency')
            ->get()
            ->map(fn ($row) => [
                'currency' => (string) $row->currency,
                'total' => (float) $row->total,
            ])
            ->values()
            ->all();
        $totalIncomeUsd = (float) (ProjectIncome::query()->sum('amount_usd') ?: ProjectIncome::query()->sum('amount'));
        $generalIncomeUsd = (float) (GeneralIncome::query()->sum('amount_usd') ?: GeneralIncome::query()->sum('amount'));
        $totalExpenseUsd = (float) (ProjectExpense::query()->sum('amount_usd') ?: ProjectExpense::query()->sum('amount'));
        $overheadUsd = (float) (GeneralExpense::query()->sum('amount_usd') ?: GeneralExpense::query()->sum('amount'));
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
    public function projectProfitability(): Collection
    {
        return Project::query()
            ->with('organization')
            ->get()
            ->map(function (Project $project) {
                $income = $project->incomes()->sum('amount_usd') ?: $project->incomes()->sum('amount');
                $expense = $project->expenses()->sum('amount_usd') ?: $project->expenses()->sum('amount');

                return [
                    'id' => $project->id,
                    'code' => $project->code,
                    'name' => $project->name,
                    'organization' => $project->organization?->name,
                    'income' => (float) $income,
                    'expense' => (float) $expense,
                    'margin' => (float) $income - (float) $expense,
                ];
            })
            ->sortByDesc('margin')
            ->values();
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

        $expenseByCategory = GeneralExpense::query()
            ->selectRaw("COALESCE(category, 'other') as category, sum(COALESCE(amount_usd, amount)) as total")
            ->groupBy('category')
            ->pluck('total', 'category');

        $topProjectsIncome = Project::query()
            ->with('organization')
            ->get()
            ->map(function (Project $project) {
                $income = $project->incomes()->sum('amount_usd') ?: $project->incomes()->sum('amount');

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

        $totalProjectIncome = (float) (ProjectIncome::query()->sum('amount_usd') ?: ProjectIncome::query()->sum('amount'));
        $totalGeneralIncome = (float) (GeneralIncome::query()->sum('amount_usd') ?: GeneralIncome::query()->sum('amount'));
        $totalProjectExpense = (float) (ProjectExpense::query()->sum('amount_usd') ?: ProjectExpense::query()->sum('amount'));
        $totalOverhead = (float) (GeneralExpense::query()->sum('amount_usd') ?: GeneralExpense::query()->sum('amount'));

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
        $usd = (float) $modelClass::query()
            ->whereBetween($dateColumn, [$start, $end])
            ->sum('amount_usd');

        if ($usd > 0) {
            return $usd;
        }

        return (float) $modelClass::query()
            ->whereBetween($dateColumn, [$start, $end])
            ->sum('amount');
    }

    /** @return Collection<int, array<string, mixed>> */
    private function netFinanceByCurrency(): Collection
    {
        $incomeByCurrency = $this->sumByCurrency([ProjectIncome::class, GeneralIncome::class]);
        $expenseByCurrency = $this->sumByCurrency([ProjectExpense::class, GeneralExpense::class]);

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
    private function sumByCurrency(array $modelClasses): Collection
    {
        $totals = collect();

        foreach ($modelClasses as $modelClass) {
            $sums = $modelClass::query()
                ->selectRaw("UPPER(COALESCE(currency, 'USD')) as currency, SUM(amount) as total")
                ->groupByRaw("UPPER(COALESCE(currency, 'USD'))")
                ->pluck('total', 'currency');

            foreach ($sums as $currency => $total) {
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
