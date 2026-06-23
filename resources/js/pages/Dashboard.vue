<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, Briefcase, DollarSign, TrendingUp, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useMisNavigation } from '@/composables/useMisNavigation';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import BarChart from '@/components/charts/BarChart.vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import LineChart from '@/components/charts/LineChart.vue';
import { computed } from 'vue';
import { translateProjectStatus } from '@/lib/status-labels';

interface DashboardStats {
    bidding: {
        open_opportunities: number;
        pending_bids: number;
        win_rate: number;
        won: number;
        lost: number;
        our_bid_by_currency: Array<{
            currency: string;
            total: number;
        }>;
    };
    projects: {
        active: number;
        planning: number;
        total: number;
    };
    finance: {
        total_income_usd: number;
        general_income_usd: number;
        total_expense_usd: number;
        overhead_usd: number;
        net_usd: number;
        net_by_currency: Array<{
            currency: string;
            income: number;
            expense: number;
            net: number;
        }>;
        currencies: Array<{
            code: string;
            name: string;
            symbol: string | null;
            is_default: boolean;
        }>;
        exchange_rates: Array<{
            from_currency: string;
            to_currency: string;
            rate: number;
            effective_date: string | null;
        }>;
    };
    hr: {
        employees: number;
        contractors: number;
        expiring_documents: number;
    };
    organization_types: Array<{
        id: number;
        name: string;
        color: string | null;
        organizations_count: number;
        projects_count: number;
        total_contract_value: number;
    }>;
    competitor_intel: number;
}

interface ProjectProfitability {
    id: number;
    code: string;
    name: string;
    organization: string | null;
    income: number;
    expense: number;
    margin: number;
}

interface ExpiringDocument {
    id: number;
    personnel_type: string;
    personnel_id: number;
    type: string | null;
    expires_at: string | null;
}

interface ChartData {
    monthly_finance: Array<{
        label: string;
        income: number;
        expense: number;
        net: number;
        overhead: number;
        general_income: number;
        project_income: number;
        project_expense: number;
    }>;
    monthly_bids: Array<{ label: string; submitted: number; won: number; lost: number; win_rate: number }>;
    project_statuses: Array<{ status: string; count: number }>;
    workforce: { employees: number; contractors: number };
    bidding_outcomes: Array<{ key: string; value: number }>;
    finance_breakdown: Array<{ key: string; value: number }>;
    expense_by_category: Array<{ category: string; value: number }>;
    top_projects_income: Array<{ code: string; income: number }>;
    organization_types: Array<{ name: string; projects_count: number; total_contract_value: number }>;
}

const props = defineProps<{
    stats: DashboardStats | null;
    projectProfitability: ProjectProfitability[];
    expiringDocuments: ExpiringDocument[];
    charts: ChartData | null;
}>();

const { t } = useTranslations();
const { misQuickLinks } = useMisNavigation();

const outcomeLabel = (key: string): string => {
    if (key === 'won') return t('won');
    if (key === 'lost') return t('lost');
    if (key === 'pending') return t('Pending');
    return key;
};

const financeLabel = (key: string): string => {
    const labels: Record<string, string> = {
        project_income: t('Project Income'),
        general_income: t('Other Income'),
        project_expense: t('Project Expenses'),
        overhead: t('Overhead & Salaries'),
    };
    return labels[key] ?? key;
};

const categoryLabel = (category: string): string => {
    const labels: Record<string, string> = {
        rent: t('Office Rent'),
        salary: t('Salary'),
        utilities: t('Utilities'),
        equipment: t('Equipment'),
        other: t('Other'),
    };
    return labels[category] ?? category;
};

const projectStatusLabel = (status: string): string =>
    translateProjectStatus(t, status);

const topMarginProjects = computed(() => props.projectProfitability.slice(0, 6));

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const formatCurrency = (value: number, currency = 'USD'): string => {
    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency,
            maximumFractionDigits: 2,
        }).format(value);
    } catch {
        return `${currency} ${new Intl.NumberFormat('en-US', {
            maximumFractionDigits: 2,
        }).format(value)}`;
    }
};
</script>

<template>
    <Head :title="t('Dashboard')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            :title="t('Dashboard')"
            :description="t('Overview of bidding, projects, finance, and HR')"
        />

        <div
            v-if="misQuickLinks.length > 0"
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
        >
            <Link
                v-for="link in misQuickLinks"
                :key="link.href"
                :href="link.href"
                class="group rounded-lg border p-4 transition-colors hover:bg-muted/50"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-md bg-muted group-hover:bg-background"
                    >
                        <component :is="link.icon" class="size-5 text-muted-foreground" />
                    </div>
                    <div>
                        <p class="font-medium">{{ link.title }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ link.description }}
                        </p>
                    </div>
                </div>
            </Link>
        </div>

        <div
            v-if="stats"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        >
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Open Opportunities')
                    }}</CardTitle>
                    <Briefcase class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats.bidding.open_opportunities }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ stats.bidding.pending_bids }}
                        {{ t('pending bids') }}
                    </p>
                    <div
                        v-if="stats.bidding.our_bid_by_currency.length"
                        class="mt-3 space-y-1 border-t pt-3"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{ t('Our bid totals') }}
                        </p>
                        <div
                            v-for="bid in stats.bidding.our_bid_by_currency"
                            :key="bid.currency"
                            class="flex items-center justify-between text-xs"
                        >
                            <span class="text-muted-foreground">{{ bid.currency }}</span>
                            <span class="font-medium">
                                {{ formatCurrency(bid.total, bid.currency) }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Win Rate')
                    }}</CardTitle>
                    <TrendingUp class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.bidding.win_rate }}%</div>
                    <p class="text-xs text-muted-foreground">
                        {{ stats.bidding.won }} {{ t('won') }} /
                        {{ stats.bidding.lost }} {{ t('lost') }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Active Projects')
                    }}</CardTitle>
                    <Briefcase class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.projects.active }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ stats.projects.planning }} {{ t('planning') }} ·
                        {{ stats.projects.total }} {{ t('total') }}
                    </p>
                </CardContent>
            </Card>

            <Card class="transition-colors hover:bg-muted/30">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Net Finance')
                    }}</CardTitle>
                    <DollarSign class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <Link href="/finance" class="block space-y-1">
                        <div class="text-2xl font-bold">
                            {{ formatCurrency(stats.finance.net_usd, 'USD') }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('Base (USD)') }} · {{ t('Income') }}
                            {{ formatCurrency(stats.finance.total_income_usd) }} ·
                            {{ t('View finance') }}
                        </p>
                    </Link>
                    <div
                        v-if="stats.finance.net_by_currency.length"
                        class="mt-3 space-y-1 border-t pt-3"
                    >
                        <div
                            v-for="item in stats.finance.net_by_currency"
                            :key="item.currency"
                            class="flex items-center justify-between text-xs"
                        >
                            <span class="text-muted-foreground">{{ item.currency }}</span>
                            <span class="font-medium">
                                {{ formatCurrency(item.net, item.currency) }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="transition-colors hover:bg-muted/30">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Workforce')
                    }}</CardTitle>
                    <Users class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <Link href="/hr/employees" class="block space-y-1">
                        <div class="text-2xl font-bold">
                            {{ stats.hr.employees + stats.hr.contractors }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.hr.employees }} {{ t('employees') }} ·
                            {{ stats.hr.contractors }} {{ t('contractors') }} ·
                            {{ t('View HR') }}
                        </p>
                    </Link>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Expiring Documents')
                    }}</CardTitle>
                    <AlertTriangle class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.hr.expiring_documents }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ t('Within 30 days') }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Competitor Intel')
                    }}</CardTitle>
                    <TrendingUp class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.competitor_intel }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ t('Recorded competitor bids') }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Currencies & Exchange Rates')
                    }}</CardTitle>
                    <DollarSign class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent class="space-y-3">
                    <div>
                        <p class="mb-1 text-xs text-muted-foreground">
                            {{ t('Active Currencies') }}
                        </p>
                        <div class="flex flex-wrap gap-1">
                            <Badge
                                v-for="currency in stats.finance.currencies"
                                :key="currency.code"
                                variant="secondary"
                                class="text-xs"
                            >
                                {{ currency.code }}
                                <span v-if="currency.is_default"> · {{ t('default') }}</span>
                            </Badge>
                        </div>
                    </div>
                    <div v-if="stats.finance.exchange_rates.length">
                        <p class="mb-1 text-xs text-muted-foreground">
                            {{ t('Latest to USD') }}
                        </p>
                        <div class="space-y-1">
                            <div
                                v-for="rate in stats.finance.exchange_rates"
                                :key="`${rate.from_currency}-${rate.to_currency}`"
                                class="flex items-center justify-between text-xs"
                            >
                                <span class="text-muted-foreground">
                                    {{ rate.from_currency }} → {{ rate.to_currency }}
                                </span>
                                <span class="font-medium">{{ rate.rate }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div
            v-if="charts"
            class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3"
        >
            <Card class="xl:col-span-2">
                <CardHeader>
                    <CardTitle>{{ t('Monthly Finance') }}</CardTitle>
                    <CardDescription>{{ t('Project income vs expenses') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :labels="charts.monthly_finance.map((m) => m.label)"
                        :datasets="[
                            {
                                label: t('Income'),
                                data: charts.monthly_finance.map((m) => m.income),
                                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            },
                            {
                                label: t('Expenses'),
                                data: charts.monthly_finance.map((m) => m.expense),
                                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                            },
                        ]"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Project Status') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <DonutChart
                        :labels="charts.project_statuses.map((s) => projectStatusLabel(s.status))"
                        :data="charts.project_statuses.map((s) => s.count)"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Workforce Split') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <DonutChart
                        :labels="[t('Employees'), t('Contractors')]"
                        :data="[
                            charts.workforce.employees,
                            charts.workforce.contractors,
                        ]"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Bidding Outcomes') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <DonutChart
                        :labels="charts.bidding_outcomes.map((b) => outcomeLabel(b.key))"
                        :data="charts.bidding_outcomes.map((b) => b.value)"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Net Cash Flow') }}</CardTitle>
                    <CardDescription>{{ t('Monthly income minus expenses') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <LineChart
                        :labels="charts.monthly_finance.map((m) => m.label)"
                        :datasets="[
                            {
                                label: t('Net'),
                                data: charts.monthly_finance.map((m) => m.net),
                                borderColor: 'rgba(59, 130, 246, 1)',
                            },
                        ]"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Finance Breakdown') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <DonutChart
                        :labels="charts.finance_breakdown.map((f) => financeLabel(f.key))"
                        :data="charts.finance_breakdown.map((f) => f.value)"
                    />
                </CardContent>
            </Card>

            <Card v-if="charts.organization_types.length">
                <CardHeader>
                    <CardTitle>{{ t('Contract Value by Org Type') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :labels="charts.organization_types.map((o) => o.name)"
                        :datasets="[
                            {
                                label: t('Contract Value'),
                                data: charts.organization_types.map((o) => o.total_contract_value),
                            },
                        ]"
                    />
                </CardContent>
            </Card>

            <Card v-if="topMarginProjects.length">
                <CardHeader>
                    <CardTitle>{{ t('Top Projects by Margin') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :labels="topMarginProjects.map((p) => p.code)"
                        :datasets="[
                            {
                                label: t('Margin'),
                                data: topMarginProjects.map((p) => p.margin),
                                backgroundColor: 'rgba(168, 85, 247, 0.7)',
                            },
                        ]"
                    />
                </CardContent>
            </Card>

            <Card v-if="charts.expense_by_category.length">
                <CardHeader>
                    <CardTitle>{{ t('Overhead by Category') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <DonutChart
                        :labels="charts.expense_by_category.map((c) => categoryLabel(c.category))"
                        :data="charts.expense_by_category.map((c) => c.value)"
                    />
                </CardContent>
            </Card>

            <Card v-if="charts.top_projects_income.length">
                <CardHeader>
                    <CardTitle>{{ t('Top Projects by Income') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :labels="charts.top_projects_income.map((p) => p.code)"
                        :datasets="[
                            {
                                label: t('Income'),
                                data: charts.top_projects_income.map((p) => p.income),
                                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            },
                        ]"
                    />
                </CardContent>
            </Card>

            <Card v-if="charts.organization_types.length">
                <CardHeader>
                    <CardTitle>{{ t('Projects by Org Type') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <DonutChart
                        :labels="charts.organization_types.map((o) => o.name)"
                        :data="charts.organization_types.map((o) => o.projects_count)"
                    />
                </CardContent>
            </Card>

            <Card v-if="topMarginProjects.length" class="xl:col-span-2">
                <CardHeader>
                    <CardTitle>{{ t('Project Income vs Expense') }}</CardTitle>
                    <CardDescription>{{ t('Top projects comparison') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :labels="topMarginProjects.map((p) => p.code)"
                        :datasets="[
                            {
                                label: t('Income'),
                                data: topMarginProjects.map((p) => p.income),
                                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            },
                            {
                                label: t('Expense'),
                                data: topMarginProjects.map((p) => p.expense),
                                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                            },
                        ]"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Income Sources') }}</CardTitle>
                    <CardDescription>{{ t('Project vs other income over 6 months') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :labels="charts.monthly_finance.map((m) => m.label)"
                        :datasets="[
                            {
                                label: t('Project Income'),
                                data: charts.monthly_finance.map((m) => m.project_income),
                                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            },
                            {
                                label: t('Other Income'),
                                data: charts.monthly_finance.map((m) => m.general_income),
                                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            },
                        ]"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Overhead Trend') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <LineChart
                        :labels="charts.monthly_finance.map((m) => m.label)"
                        :datasets="[
                            {
                                label: t('Overhead & Salaries'),
                                data: charts.monthly_finance.map((m) => m.overhead),
                            },
                        ]"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Other Income Trend') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <LineChart
                        :labels="charts.monthly_finance.map((m) => m.label)"
                        :datasets="[
                            {
                                label: t('Other Income'),
                                data: charts.monthly_finance.map((m) => m.general_income),
                            },
                        ]"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Monthly Bid Activity') }}</CardTitle>
                    <CardDescription>{{ t('Submissions, wins, and losses') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <LineChart
                        :labels="charts.monthly_bids.map((m) => m.label)"
                        :datasets="[
                            {
                                label: t('Submitted'),
                                data: charts.monthly_bids.map((m) => m.submitted),
                            },
                            {
                                label: t('won'),
                                data: charts.monthly_bids.map((m) => m.won),
                            },
                            {
                                label: t('lost'),
                                data: charts.monthly_bids.map((m) => m.lost),
                            },
                        ]"
                    />
                </CardContent>
            </Card>
        </div>

        <div
            v-if="stats"
            class="grid gap-4 lg:grid-cols-2"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Project Profitability') }}</CardTitle>
                    <CardDescription>{{
                        t('Income vs expense by project')
                    }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="projectProfitability.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        {{ t('No project data available.') }}
                    </div>
                    <div v-else class="divide-y">
                        <Link
                            v-for="project in projectProfitability.slice(0, 8)"
                            :key="project.id"
                            :href="`/projects/${project.id}`"
                            class="flex items-center justify-between py-3 transition-colors hover:bg-muted/50"
                        >
                            <div>
                                <p class="font-medium">{{ project.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ project.code }}
                                    <span v-if="project.organization">
                                        · {{ project.organization }}
                                    </span>
                                </p>
                            </div>
                            <Badge
                                :variant="project.margin >= 0 ? 'default' : 'destructive'"
                            >
                                {{ formatCurrency(project.margin) }}
                            </Badge>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Expiring Documents') }}</CardTitle>
                    <CardDescription>{{
                        t('Personnel attachments expiring soon')
                    }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="expiringDocuments.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        {{ t('No documents expiring within 30 days.') }}
                    </div>
                    <div v-else class="divide-y">
                        <div
                            v-for="doc in expiringDocuments.slice(0, 8)"
                            :key="doc.id"
                            class="flex items-center justify-between py-3"
                        >
                            <div>
                                <p class="font-medium">{{ doc.type ?? t('Document') }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ doc.personnel_type }} #{{ doc.personnel_id }}
                                </p>
                            </div>
                            <Badge variant="outline">{{ doc.expires_at }}</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
