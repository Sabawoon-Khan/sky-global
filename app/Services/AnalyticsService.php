<?php

namespace App\Services;

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

        return [
            'bidding' => [
                'open_opportunities' => ProcurementOpportunity::query()->where('status', 'open')->count(),
                'pending_bids' => Bid::query()->whereIn('status', ['draft', 'submitted', 'under_review'])->count(),
                'win_rate' => $totalBids > 0 ? round(($wonBids / $totalBids) * 100, 1) : 0,
                'won' => $wonBids,
                'lost' => $lostBids,
            ],
            'projects' => [
                'active' => Project::query()->where('status', 'active')->count(),
                'planning' => Project::query()->where('status', 'planning')->count(),
                'total' => Project::query()->count(),
            ],
            'finance' => [
                'total_income_usd' => ProjectIncome::query()->sum('amount_usd') ?: ProjectIncome::query()->sum('amount'),
                'general_income_usd' => GeneralIncome::query()->sum('amount_usd') ?: GeneralIncome::query()->sum('amount'),
                'total_expense_usd' => ProjectExpense::query()->sum('amount_usd') ?: ProjectExpense::query()->sum('amount'),
                'overhead_usd' => GeneralExpense::query()->sum('amount_usd') ?: GeneralExpense::query()->sum('amount'),
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

            $income = (float) (ProjectIncome::query()
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount_usd') ?: ProjectIncome::query()
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount'));

            $expense = (float) (ProjectExpense::query()
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount_usd') ?: ProjectExpense::query()
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount'));

            return [
                'label' => $start->format('M Y'),
                'income' => $income,
                'expense' => $expense,
            ];
        });

        $projectStatuses = Project::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'monthly_finance' => $monthlyFinance->values()->all(),
            'project_statuses' => $projectStatuses->map(fn ($count, $status) => [
                'status' => (string) $status,
                'count' => (int) $count,
            ])->values()->all(),
            'workforce' => [
                'employees' => Employee::query()->where('status', 'active')->count(),
                'contractors' => Contractor::query()->where('status', 'active')->count(),
            ],
            'bidding_outcomes' => [
                ['label' => 'Won', 'value' => Bid::query()->where('status', 'won')->count()],
                ['label' => 'Lost', 'value' => Bid::query()->where('status', 'lost')->count()],
                ['label' => 'Pending', 'value' => Bid::query()->whereIn('status', ['draft', 'submitted', 'under_review'])->count()],
            ],
        ];
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
