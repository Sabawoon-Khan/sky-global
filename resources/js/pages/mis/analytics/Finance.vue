<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { DollarSign, PieChart } from '@lucide/vue';
import Can from '@/components/Can.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useMisPage } from '@/composables/useMisPage';
import BarChart from '@/components/charts/BarChart.vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import LineChart from '@/components/charts/LineChart.vue';

interface ProjectProfitability {
    id: number;
    code: string;
    name: string;
    organization?: string | null;
    income: number;
    expense: number;
    margin: number;
}

interface FinanceStats {
    total_income_usd?: number;
    total_expense_usd?: number;
    overhead_usd?: number;
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
    finance_breakdown: Array<{ key: string; value: number }>;
    expense_by_category: Array<{ category: string; value: number }>;
    top_projects_income: Array<{ code: string; income: number }>;
}

interface Props {
    stats?: FinanceStats;
    projectProfitability: ProjectProfitability[];
    charts?: ChartData;
}

const props = defineProps<Props>();

const { t, can } = useMisPage();

const netMargin = computed(() => {
    if (!props.stats) {
        return null;
    }

    return (
        (props.stats.total_income_usd ?? 0) -
        (props.stats.total_expense_usd ?? 0) -
        (props.stats.overhead_usd ?? 0)
    );
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Analytics', href: '/analytics/bidding' },
            { title: 'Finance', href: '/analytics/finance' },
        ],
    },
});

const formatCurrency = (value?: number | null): string => {
    if (value == null) {
        return '—';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(value);
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

const incomeSources = computed(() => {
    if (!props.charts) {
        return { labels: [] as string[], data: [] as number[] };
    }

    const items = props.charts.finance_breakdown.filter((f) =>
        ['project_income', 'general_income'].includes(f.key),
    );

    return {
        labels: items.map((f) => financeLabel(f.key)),
        data: items.map((f) => f.value),
    };
});
</script>

<template>
    <Head :title="t('Finance Analytics')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                :title="t('Finance Analytics')"
                :description="t('Revenue, expenses, and project profitability')"
            />
            <div class="flex flex-wrap gap-2">
                <Can permission="finance.view">
                    <Button variant="outline" as-child>
                        <Link href="/finance">{{ t('View Finance') }}</Link>
                    </Button>
                </Can>
                <Can permission="bidding.view">
                    <Button variant="outline" as-child>
                        <Link href="/analytics/bidding">{{ t('Bidding Analytics') }}</Link>
                    </Button>
                </Can>
            </div>
        </div>

        <div v-if="stats" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Total Income') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ formatCurrency(stats.total_income_usd) }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Project Expenses') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-bold text-destructive">
                    {{ formatCurrency(stats.total_expense_usd) }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Overhead') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-bold">
                    {{ formatCurrency(stats.overhead_usd) }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm">{{ t('Net Margin') }}</CardTitle>
                    <DollarSign class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent class="text-2xl font-bold">
                    {{ formatCurrency(netMargin) }}
                </CardContent>
            </Card>
        </div>

        <div v-if="charts" class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
            <Card class="xl:col-span-2">
                <CardHeader>
                    <CardTitle>{{ t('Monthly Trends') }}</CardTitle>
                    <CardDescription>{{ t('Income vs expenses over 6 months') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :labels="charts.monthly_finance.map((m) => m.label)"
                        :datasets="[
                            { label: t('Income'), data: charts.monthly_finance.map((m) => m.income), backgroundColor: 'rgba(34, 197, 94, 0.7)' },
                            { label: t('Expenses'), data: charts.monthly_finance.map((m) => m.expense), backgroundColor: 'rgba(239, 68, 68, 0.7)' },
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
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Net Cash Flow') }}</CardTitle>
                    <CardDescription>{{ t('Monthly income minus expenses') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <LineChart
                        :labels="charts.monthly_finance.map((m) => m.label)"
                        :datasets="[{ label: t('Net'), data: charts.monthly_finance.map((m) => m.net) }]"
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
                        :datasets="[{ label: t('Overhead & Salaries'), data: charts.monthly_finance.map((m) => m.overhead) }]"
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
                        :datasets="[{ label: t('Other Income'), data: charts.monthly_finance.map((m) => m.general_income) }]"
                    />
                </CardContent>
            </Card>
            <Card v-if="incomeSources.labels.length">
                <CardHeader>
                    <CardTitle>{{ t('Income Sources') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <DonutChart
                        :labels="incomeSources.labels"
                        :data="incomeSources.data"
                    />
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Project Income Trend') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <LineChart
                        :labels="charts.monthly_finance.map((m) => m.label)"
                        :datasets="[
                            {
                                label: t('Project Income'),
                                data: charts.monthly_finance.map((m) => m.project_income),
                            },
                        ]"
                    />
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Project Expense Trend') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <LineChart
                        :labels="charts.monthly_finance.map((m) => m.label)"
                        :datasets="[
                            {
                                label: t('Project Expenses'),
                                data: charts.monthly_finance.map((m) => m.project_expense),
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
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Top Projects by Margin') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :labels="projectProfitability.slice(0, 8).map((p) => p.code)"
                        :datasets="[
                            { label: t('Margin'), data: projectProfitability.slice(0, 8).map((p) => p.margin), backgroundColor: 'rgba(59, 130, 246, 0.7)' },
                        ]"
                        :height="280"
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
                            { label: t('Income'), data: charts.top_projects_income.map((p) => p.income), backgroundColor: 'rgba(34, 197, 94, 0.7)' },
                        ]"
                    />
                </CardContent>
            </Card>
            <Card class="xl:col-span-2">
                <CardHeader>
                    <CardTitle>{{ t('Project Income vs Expense') }}</CardTitle>
                    <CardDescription>{{ t('Top projects comparison') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :labels="projectProfitability.slice(0, 6).map((p) => p.code)"
                        :datasets="[
                            { label: t('Income'), data: projectProfitability.slice(0, 6).map((p) => p.income), backgroundColor: 'rgba(34, 197, 94, 0.7)' },
                            { label: t('Expense'), data: projectProfitability.slice(0, 6).map((p) => p.expense), backgroundColor: 'rgba(239, 68, 68, 0.7)' },
                        ]"
                    />
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <PieChart class="size-5" />
                    {{ t('Project Profitability') }}
                </CardTitle>
                <CardDescription>
                    {{ t('Income vs expense by project') }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="projectProfitability.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No profitability data available.') }}
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-start text-muted-foreground">
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Project')
                                }}</th>
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Client')
                                }}</th>
                                <th class="pb-3 pe-4 text-end font-medium">
                                    {{ t('Income') }}
                                </th>
                                <th class="pb-3 pe-4 text-end font-medium">
                                    {{ t('Expense') }}
                                </th>
                                <th class="pb-3 text-end font-medium">{{
                                    t('Margin')
                                }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="project in projectProfitability"
                                :key="project.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pe-4">
                                    <Link
                                        v-if="can('projects.view')"
                                        :href="`/projects/${project.id}`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ project.code }}
                                    </Link>
                                    <span v-else class="font-medium">
                                        {{ project.code }}
                                    </span>
                                    <div class="text-xs text-muted-foreground">
                                        {{ project.name }}
                                    </div>
                                </td>
                                <td class="py-3 pe-4 text-muted-foreground">
                                    {{ project.organization ?? '—' }}
                                </td>
                                <td class="py-3 pe-4 text-end">
                                    {{ formatCurrency(project.income) }}
                                </td>
                                <td class="py-3 pe-4 text-end">
                                    {{ formatCurrency(project.expense) }}
                                </td>
                                <td
                                    class="py-3 text-end font-medium"
                                    :class="
                                        project.margin >= 0
                                            ? 'text-green-600 dark:text-green-400'
                                            : 'text-destructive'
                                    "
                                >
                                    {{ formatCurrency(project.margin) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
