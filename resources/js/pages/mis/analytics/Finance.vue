<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowDownRight,
    ArrowUpRight,
    CircleDollarSign,
    Wallet,
} from '@lucide/vue';
import BarChart from '@/components/charts/BarChart.vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import LineChart from '@/components/charts/LineChart.vue';
import {
    V2Hero,
    V2ListPage,
    V2Panel,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn } from '@/lib/format';

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

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Analytics', href: '/analytics/bidding' },
            { title: 'Finance', href: '/analytics/finance' },
        ],
    },
});

/** Embassy brand chart palette */
const colors = {
    navy: '#0c1a2e',
    teal: '#1f4e5f',
    brass: '#b8956c',
    steel: '#3d5a80',
    muted: '#8b9bb4',
    danger: '#8f2d3a',
};

const donutColors = [
    colors.teal,
    colors.brass,
    colors.navy,
    colors.steel,
    colors.muted,
    colors.danger,
];

const netMargin = computed(() => {
    if (!props.stats) return null;
    return (
        (props.stats.total_income_usd ?? 0) -
        (props.stats.total_expense_usd ?? 0) -
        (props.stats.overhead_usd ?? 0)
    );
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'project', label: t('Project') },
    { key: 'client', label: t('Client') },
    { key: 'income', label: t('Income') },
    { key: 'expense', label: t('Expense') },
    { key: 'margin', label: t('Margin') },
]);

const formatCurrency = (value?: number | null): string => formatAfn(value);

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
</script>

<template>
    <Head :title="t('Finance Analytics')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png" priority>
            <template #eyebrow>{{ t('Analytics') }}</template>
            <template #title>{{ t('Finance Analytics') }}</template>
            <template #description>
                {{ t('Income, expenses, and project margin trends.') }}
            </template>
            <template #side>
                <div class="detail-actions">
                    <Link
                        v-if="can('finance.view')"
                        href="/finance"
                        class="detail-btn"
                    >
                        {{ t('View Finance') }}
                    </Link>
                    <Link
                        v-if="can('bidding.view')"
                        href="/analytics/bidding"
                        class="detail-btn"
                    >
                        {{ t('Bidding Analytics') }}
                    </Link>
                </div>
            </template>
            <template v-if="stats" #stats>
                <V2StatGrid>
                    <V2StatCard
                        :title="t('Total Income')"
                        :value="formatCurrency(stats.total_income_usd)"
                    >
                        <template #icon><ArrowUpRight /></template>
                    </V2StatCard>
                    <V2StatCard
                        icon-tone="warm"
                        :title="t('Project Expenses')"
                        :value="formatCurrency(stats.total_expense_usd)"
                    >
                        <template #icon><ArrowDownRight /></template>
                    </V2StatCard>
                    <V2StatCard
                        icon-tone="teal"
                        :title="t('Overhead')"
                        :value="formatCurrency(stats.overhead_usd)"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                    <V2StatCard
                        accent
                        icon-tone="orange"
                        :title="t('Net Margin')"
                        :value="formatCurrency(netMargin)"
                    >
                        <template #icon><CircleDollarSign /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <div v-if="charts" class="grid gap-4 lg:grid-cols-5">
            <V2Panel
                class="lg:col-span-3"
                :title="t('Monthly Trends')"
                :description="t('Income vs expenses')"
            >
                <BarChart
                    :labels="charts.monthly_finance.map((m) => m.label)"
                    :datasets="[
                        {
                            label: t('Income'),
                            data: charts.monthly_finance.map((m) => m.income),
                            backgroundColor: colors.teal,
                        },
                        {
                            label: t('Expenses'),
                            data: charts.monthly_finance.map((m) => m.expense),
                            backgroundColor: colors.brass,
                        },
                    ]"
                />
            </V2Panel>

            <V2Panel
                class="lg:col-span-2"
                :title="t('Finance Breakdown')"
            >
                <DonutChart
                    :labels="
                        charts.finance_breakdown.map((f) => financeLabel(f.key))
                    "
                    :data="charts.finance_breakdown.map((f) => f.value)"
                    :colors="donutColors"
                    :center-label="t('Total')"
                />
            </V2Panel>
        </div>

        <div v-if="charts" class="grid gap-4 lg:grid-cols-2">
            <V2Panel :title="t('Net Cash Flow')">
                <LineChart
                    :labels="charts.monthly_finance.map((m) => m.label)"
                    :datasets="[
                        {
                            label: t('Net'),
                            data: charts.monthly_finance.map((m) => m.net),
                            borderColor: colors.navy,
                            backgroundColor: 'rgba(12, 26, 46, 0.1)',
                        },
                    ]"
                />
            </V2Panel>

            <V2Panel :title="t('Income Sources')">
                <BarChart
                    :labels="charts.monthly_finance.map((m) => m.label)"
                    :datasets="[
                        {
                            label: t('Project Income'),
                            data: charts.monthly_finance.map(
                                (m) => m.project_income,
                            ),
                            backgroundColor: colors.teal,
                        },
                        {
                            label: t('Other Income'),
                            data: charts.monthly_finance.map(
                                (m) => m.general_income,
                            ),
                            backgroundColor: colors.brass,
                        },
                    ]"
                />
            </V2Panel>
        </div>

        <div v-if="charts" class="grid gap-4 lg:grid-cols-5">
            <V2Panel
                class="lg:col-span-3"
                :title="t('Project Income vs Expense')"
            >
                <BarChart
                    :labels="
                        projectProfitability.slice(0, 6).map((p) => p.code)
                    "
                    :datasets="[
                        {
                            label: t('Income'),
                            data: projectProfitability
                                .slice(0, 6)
                                .map((p) => p.income),
                            backgroundColor: colors.teal,
                        },
                        {
                            label: t('Expense'),
                            data: projectProfitability
                                .slice(0, 6)
                                .map((p) => p.expense),
                            backgroundColor: colors.brass,
                        },
                    ]"
                />
            </V2Panel>

            <V2Panel
                v-if="charts.expense_by_category.length"
                class="lg:col-span-2"
                :title="t('Overhead by Category')"
            >
                <DonutChart
                    :labels="
                        charts.expense_by_category.map((c) =>
                            categoryLabel(c.category),
                        )
                    "
                    :data="charts.expense_by_category.map((c) => c.value)"
                    :colors="donutColors"
                />
            </V2Panel>
            <V2Panel
                v-else
                class="lg:col-span-2"
                :title="t('Top Projects by Margin')"
            >
                <BarChart
                    :labels="
                        projectProfitability.slice(0, 6).map((p) => p.code)
                    "
                    :datasets="[
                        {
                            label: t('Margin'),
                            data: projectProfitability
                                .slice(0, 6)
                                .map((p) => p.margin),
                            backgroundColor: colors.navy,
                        },
                    ]"
                    :show-legend="false"
                    horizontal
                />
            </V2Panel>
        </div>

        <V2TablePanel
            table-id="analytics-project-profitability"
            :columns="tableColumns"
            :delay="false"
        >
            <template #filters>
                <div class="table-top">
                    <div>
                        <h2>{{ t('Project Profitability') }}</h2>
                        <p>{{ t('Income, expense, and margin by project') }}</p>
                    </div>
                </div>
            </template>

            <table>
                <thead>
                    <tr>
                        <th>{{ t('Project') }}</th>
                        <th>{{ t('Client') }}</th>
                        <th class="end">{{ t('Income') }}</th>
                        <th class="end">{{ t('Expense') }}</th>
                        <th class="end">{{ t('Margin') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!projectProfitability.length">
                        <td colspan="5" class="py-10 text-center text-muted-foreground">
                            {{ t('No profitability data available.') }}
                        </td>
                    </tr>
                    <tr
                        v-for="project in projectProfitability"
                        :key="project.id"
                    >
                        <td>
                            <Link
                                v-if="can('projects.view')"
                                :href="`/mis/projects/${project.id}`"
                                class="font-medium hover:text-primary hover:underline"
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
                        <td>{{ project.organization ?? '—' }}</td>
                        <td class="end tabular-nums">
                            {{ formatCurrency(project.income) }}
                        </td>
                        <td class="end tabular-nums">
                            {{ formatCurrency(project.expense) }}
                        </td>
                        <td
                            class="end font-medium tabular-nums"
                            :class="
                                project.margin >= 0
                                    ? 'text-primary'
                                    : 'text-destructive'
                            "
                        >
                            {{ formatCurrency(project.margin) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </V2TablePanel>
    </V2ListPage>
</template>
