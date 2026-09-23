<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowDownRight,
    ArrowUpRight,
    Printer,
    Receipt,
    Wallet,
} from '@lucide/vue';
import AnalyticsSubnav from '@/components/mis/AnalyticsSubnav.vue';
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
import { provideTableSort } from '@/composables/useTableSort';
import SortableTh from '@/components/SortableTh.vue';
import { formatAfn, formatNumber } from '@/lib/format';

interface ProjectProfitability {
    id: number;
    code: string;
    name: string;
    organization?: string | null;
    income: number;
    expense: number;
    margin: number;
    margin_percent?: number;
}

interface StatementRow {
    key: string;
    amount: number;
    style: 'item' | 'subtotal' | 'emphasis' | 'total';
}

interface FinanceTotals {
    project_income: number;
    general_income: number;
    total_income: number;
    project_expense: number;
    overhead: number;
    total_expense: number;
    operating_net: number;
    tax_due?: number;
    tax_paid?: number;
    tax_remaining?: number;
    net_after_tax?: number;
    margin_percent?: number;
    billed?: number;
    collected?: number;
    outstanding?: number;
    overdue?: number;
}

interface ChartData {
    monthly: Array<{
        label: string;
        income: number;
        expense: number;
        net: number;
        overhead: number;
        general_income: number;
        project_income: number;
        project_expense: number;
    }>;
    income_mix: Array<{ key: string; value: number }>;
    expense_mix: Array<{ key: string; value: number }>;
    income_by_category: Array<{ category: string; value: number }>;
    expense_by_category: Array<{ category: string; value: number }>;
}

interface Props {
    year: number | null;
    year_label: string;
    years: number[];
    stats: FinanceTotals;
    previous?: FinanceTotals | null;
    statement: StatementRow[];
    charts: ChartData;
    currencies: Array<{
        currency: string;
        income: number;
        expense: number;
        net: number;
    }>;
    invoices: {
        billed: number;
        collected: number;
        outstanding: number;
        overdue: number;
        by_status: Array<{ status: string; count: number; total: number }>;
    };
    tax: {
        rate_percent: number;
        due: number;
        paid: number;
        remaining: number;
    };
    projectProfitability: ProjectProfitability[];
}

const props = defineProps<Props>();
const { t, can } = useMisPage();

const { sortedRows } = provideTableSort(() => props.projectProfitability, {
    accessors: {
        project: (row) => row.code,
        client: (row) => row.organization,
        income: (row) => row.income,
        expense: (row) => row.expense,
        margin: (row) => row.margin,
        margin_percent: (row) => row.margin_percent,
    },
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Analytics', href: '/analytics/bidding' },
            { title: 'Finance', href: '/analytics/finance' },
        ],
    },
});

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

const yearValue = computed(() =>
    props.year === null ? 'all' : String(props.year),
);

const printHref = computed(
    () => `/analytics/finance/print?year=${yearValue.value}&autoprint=1`,
);

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'project', label: t('Project') },
    { key: 'client', label: t('Client') },
    { key: 'income', label: t('Income') },
    { key: 'expense', label: t('Expense') },
    { key: 'margin', label: t('Margin') },
    { key: 'margin_percent', label: t('Margin %') },
]);

const projectTotals = computed(() => {
    return props.projectProfitability.reduce(
        (sum, row) => ({
            income: sum.income + row.income,
            expense: sum.expense + row.expense,
            margin: sum.margin + row.margin,
        }),
        { income: 0, expense: 0, margin: 0 },
    );
});

const positiveMix = (rows: Array<{ key: string; value: number }>) =>
    rows.filter((row) => Number(row.value) > 0);

const monthly = computed(() => props.charts?.monthly ?? []);
const incomeMix = computed(() =>
    positiveMix(props.charts?.income_mix ?? []),
);
const expenseMix = computed(() =>
    positiveMix(props.charts?.expense_mix ?? []),
);

const statementShare = (row: StatementRow): number | null => {
    if (row.key.includes('income') || row.key.includes('net')) {
        return shareOf(row.amount, props.stats.total_income);
    }

    if (row.key.includes('expense') || row.key === 'overhead' || row.key.startsWith('tax_')) {
        return shareOf(row.amount, props.stats.total_expense || props.stats.total_income);
    }

    return null;
};

const formatCurrency = (value?: number | null): string => formatAfn(value);

const formatPercent = (value?: number | null): string => {
    if (value == null) {
        return '—';
    }

    return `${formatNumber(value, { maximumFractionDigits: 1 })}%`;
};

const statementLabel = (key: string): string => {
    const labels: Record<string, string> = {
        project_income: t('Project Income'),
        general_income: t('Other Income'),
        total_income: t('Total Income'),
        project_expense: t('Project Expenses'),
        overhead: t('Overhead & Salaries'),
        total_expense: t('Total Expenses'),
        operating_net: t('Operating net'),
        tax_due: t('Tax due'),
        tax_paid: t('Tax paid'),
        net_after_tax: t('Net after tax'),
    };

    return labels[key] ?? key;
};

const mixLabel = (key: string): string => statementLabel(key);

const categoryLabel = (category: string): string => {
    if (category === 'Uncategorized') {
        return t('Uncategorized');
    }

    return t(category) !== category ? t(category) : category;
};

function shareOf(amount: number, total: number): number | null {
    if (total <= 0) {
        return null;
    }

    return (amount / total) * 100;
}

function previousAmount(key: string): number | null {
    const previous = props.previous as Record<string, number> | null | undefined;

    if (!previous || previous[key] == null) {
        return null;
    }

    return previous[key];
}

function deltaLabel(current: number, previous: number | null): string {
    if (previous == null) {
        return '—';
    }

    if (previous === 0) {
        return current === 0 ? '0%' : '—';
    }

    const pct = ((current - previous) / Math.abs(previous)) * 100;
    const sign = pct > 0 ? '+' : '';

    return `${sign}${formatNumber(pct, { maximumFractionDigits: 1 })}%`;
}

function onYearChange(event: Event): void {
    const year = (event.target as HTMLSelectElement).value;

    router.get(
        '/analytics/finance',
        { year },
        { preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head :title="t('Finance Analytics')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png" priority>
            <template #eyebrow>{{ t('Analytics') }}</template>
            <template #title>{{ t('Finance Analytics') }}</template>
            <template #description>
                {{ t('Monitor income, expenses, overhead, and project profitability.') }}
                · {{ year === null ? t('All years') : year }}
            </template>
            <template #side>
                <div class="detail-actions">
                    <label class="year-filter">
                        <span>{{ t('Year') }}</span>
                        <select
                            :value="yearValue"
                            class="year-select"
                            @change="onYearChange"
                        >
                            <option
                                v-for="option in years"
                                :key="option"
                                :value="option"
                            >
                                {{ option }}
                            </option>
                            <option value="all">{{ t('All years') }}</option>
                        </select>
                    </label>
                    <a
                        :href="printHref"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="create-btn"
                    >
                        <Printer />
                        {{ t('Print report') }}
                    </a>
                    <Link
                        v-if="can('finance.view')"
                        href="/finance"
                        class="detail-btn"
                    >
                        {{ t('View Finance') }}
                    </Link>
                </div>
            </template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :title="t('Total Income')"
                        :value="formatCurrency(stats.total_income)"
                    >
                        <template #icon><ArrowUpRight /></template>
                    </V2StatCard>
                    <V2StatCard
                        icon-tone="warm"
                        :title="t('Total Expenses')"
                        :value="formatCurrency(stats.total_expense)"
                    >
                        <template #icon><ArrowDownRight /></template>
                    </V2StatCard>
                    <V2StatCard
                        icon-tone="teal"
                        :title="t('Operating net')"
                        :value="formatCurrency(stats.operating_net)"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                    <V2StatCard
                        accent
                        icon-tone="orange"
                        :title="t('Outstanding invoices')"
                        :value="formatCurrency(stats.outstanding)"
                    >
                        <template #icon><Receipt /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <AnalyticsSubnav active="finance" />

        <div class="grid gap-4 lg:grid-cols-5">
            <V2Panel
                class="lg:col-span-3"
                :title="t('Monthly Trends')"
                :description="t('Income minus all expenses')"
            >
                <BarChart
                    :labels="monthly.map((m) => m.label)"
                    :datasets="[
                        {
                            label: t('Income'),
                            data: monthly.map((m) => m.income),
                            backgroundColor: colors.teal,
                        },
                        {
                            label: t('Expenses'),
                            data: monthly.map((m) => m.expense),
                            backgroundColor: colors.brass,
                        },
                    ]"
                />
            </V2Panel>

            <V2Panel
                class="lg:col-span-2"
                :title="t('Profit and Loss')"
                :padded="false"
            >
                <table class="statement-table">
                    <thead>
                        <tr>
                            <th>{{ t('Field') }}</th>
                            <th class="end">{{ t('Amount') }}</th>
                            <th class="end">{{ t('Share') }}</th>
                            <th v-if="previous" class="end">
                                {{ t('vs previous year') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in statement"
                            :key="row.key"
                            :class="`is-${row.style}`"
                        >
                            <td>{{ statementLabel(row.key) }}</td>
                            <td
                                class="end tabular-nums"
                                :class="{
                                    'text-destructive':
                                        row.style === 'emphasis' &&
                                        row.amount < 0,
                                }"
                            >
                                {{ formatCurrency(row.amount) }}
                            </td>
                            <td class="end tabular-nums text-muted-foreground">
                                {{ formatPercent(statementShare(row)) }}
                            </td>
                            <td
                                v-if="previous"
                                class="end tabular-nums text-muted-foreground"
                            >
                                {{
                                    deltaLabel(
                                        row.amount,
                                        previousAmount(row.key),
                                    )
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </V2Panel>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <V2Panel :title="t('Income mix')">
                <DonutChart
                    v-if="incomeMix.length"
                    :labels="incomeMix.map((row) => mixLabel(row.key))"
                    :data="incomeMix.map((row) => row.value)"
                    :colors="donutColors"
                    :center-label="t('Income')"
                />
                <p v-else class="empty-note">
                    {{ t('No summary data available.') }}
                </p>
            </V2Panel>
            <V2Panel :title="t('Expense mix')">
                <DonutChart
                    v-if="expenseMix.length"
                    :labels="expenseMix.map((row) => mixLabel(row.key))"
                    :data="expenseMix.map((row) => row.value)"
                    :colors="donutColors"
                    :center-label="t('Expenses')"
                />
                <p v-else class="empty-note">
                    {{ t('No summary data available.') }}
                </p>
            </V2Panel>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <V2Panel :title="t('Net Cash Flow')">
                <LineChart
                    :labels="monthly.map((m) => m.label)"
                    :datasets="[
                        {
                            label: t('Net'),
                            data: monthly.map((m) => m.net),
                            borderColor: colors.navy,
                            backgroundColor: 'rgba(12, 26, 46, 0.1)',
                        },
                    ]"
                />
            </V2Panel>
            <V2Panel
                v-if="charts.expense_by_category?.length"
                :title="t('Expenses by category')"
            >
                <DonutChart
                    :labels="
                        charts.expense_by_category.map((c) =>
                            categoryLabel(c.category),
                        )
                    "
                    :data="charts.expense_by_category.map((c) => c.value)"
                    :colors="donutColors"
                    :center-label="t('Expenses')"
                />
            </V2Panel>
            <V2Panel v-else :title="t('Income by category')">
                <DonutChart
                    v-if="charts.income_by_category?.length"
                    :labels="
                        charts.income_by_category.map((c) =>
                            categoryLabel(c.category),
                        )
                    "
                    :data="charts.income_by_category.map((c) => c.value)"
                    :colors="donutColors"
                    :center-label="t('Income')"
                />
                <p v-else class="empty-note">
                    {{ t('No summary data available.') }}
                </p>
            </V2Panel>
        </div>

        <div class="grid gap-4 lg:grid-cols-5">
            <V2Panel
                class="lg:col-span-3"
                :title="t('Project Income vs Expense')"
            >
                <BarChart
                    v-if="projectProfitability.length"
                    :labels="
                        projectProfitability.slice(0, 8).map((p) => p.code)
                    "
                    :datasets="[
                        {
                            label: t('Income'),
                            data: projectProfitability
                                .slice(0, 8)
                                .map((p) => p.income),
                            backgroundColor: colors.teal,
                        },
                        {
                            label: t('Expense'),
                            data: projectProfitability
                                .slice(0, 8)
                                .map((p) => p.expense),
                            backgroundColor: colors.brass,
                        },
                    ]"
                />
                <p v-else class="empty-note">
                    {{ t('No profitability data available.') }}
                </p>
            </V2Panel>
            <V2Panel class="lg:col-span-2" :title="t('Invoices')">
                <ul class="metric-list">
                    <li>
                        <span>{{ t('Billed') }}</span>
                        <b>{{ formatCurrency(invoices.billed) }}</b>
                    </li>
                    <li>
                        <span>{{ t('Collected') }}</span>
                        <b>{{ formatCurrency(invoices.collected) }}</b>
                    </li>
                    <li>
                        <span>{{ t('Outstanding') }}</span>
                        <b>{{ formatCurrency(invoices.outstanding) }}</b>
                    </li>
                    <li>
                        <span>{{ t('Overdue') }}</span>
                        <b class="text-destructive">{{
                            formatCurrency(invoices.overdue)
                        }}</b>
                    </li>
                </ul>
            </V2Panel>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <V2Panel :title="t('Tax')">
                <ul class="metric-list">
                    <li>
                        <span>{{ t('Tax due') }} · {{ tax.rate_percent }}%</span>
                        <b>{{ formatCurrency(tax.due) }}</b>
                    </li>
                    <li>
                        <span>{{ t('Tax paid') }}</span>
                        <b>{{ formatCurrency(tax.paid) }}</b>
                    </li>
                    <li>
                        <span>{{ t('Tax remaining') }}</span>
                        <b>{{ formatCurrency(tax.remaining) }}</b>
                    </li>
                    <li>
                        <span>{{ t('Net after tax') }}</span>
                        <b
                            :class="
                                (stats.net_after_tax ?? 0) >= 0
                                    ? 'text-primary'
                                    : 'text-destructive'
                            "
                        >
                            {{ formatCurrency(stats.net_after_tax) }}
                        </b>
                    </li>
                </ul>
            </V2Panel>
            <V2Panel :title="t('Currency')">
                <p v-if="!currencies.length" class="empty-note">
                    {{ t('No summary data available.') }}
                </p>
                <ul v-else class="metric-list">
                    <li v-for="row in currencies" :key="row.currency">
                        <span>{{ row.currency }}</span>
                        <b
                            :class="
                                row.net >= 0
                                    ? 'text-primary'
                                    : 'text-destructive'
                            "
                        >
                            {{ formatCurrency(row.net) }}
                        </b>
                    </li>
                </ul>
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
                        <p>
                            {{
                                t(
                                    'Income, expense, and margin by project',
                                )
                            }}
                        </p>
                    </div>
                </div>
            </template>

            <table>
                <thead>
                    <tr>
                        <SortableTh column="project">{{ t('Project') }}</SortableTh>
                        <SortableTh column="client">{{ t('Client') }}</SortableTh>
                        <SortableTh column="income" align="end" class="end">{{
                            t('Income')
                        }}</SortableTh>
                        <SortableTh
                            column="expense"
                            align="end"
                            class="end"
                        >
                            {{ t('Expense') }}
                        </SortableTh>
                        <SortableTh column="margin" align="end" class="end">{{
                            t('Margin')
                        }}</SortableTh>
                        <SortableTh
                            column="margin_percent"
                            align="end"
                            class="end"
                        >
                            {{ t('Margin %') }}
                        </SortableTh>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!projectProfitability.length">
                        <td
                            colspan="6"
                            class="py-10 text-center text-muted-foreground"
                        >
                            {{ t('No profitability data available.') }}
                        </td>
                    </tr>
                    <tr
                        v-for="project in sortedRows"
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
                        <td
                            class="end tabular-nums"
                            :class="
                                (project.margin_percent ?? 0) >= 0
                                    ? 'text-primary'
                                    : 'text-destructive'
                            "
                        >
                            {{ formatPercent(project.margin_percent) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="projectProfitability.length">
                    <tr>
                        <td colspan="2" class="font-medium">{{ t('Total') }}</td>
                        <td class="end font-medium tabular-nums">
                            {{ formatCurrency(projectTotals.income) }}
                        </td>
                        <td class="end font-medium tabular-nums">
                            {{ formatCurrency(projectTotals.expense) }}
                        </td>
                        <td
                            class="end font-medium tabular-nums"
                            :class="
                                projectTotals.margin >= 0
                                    ? 'text-primary'
                                    : 'text-destructive'
                            "
                        >
                            {{ formatCurrency(projectTotals.margin) }}
                        </td>
                        <td class="end tabular-nums">
                            {{
                                formatPercent(
                                    projectTotals.income > 0
                                        ? (projectTotals.margin /
                                              projectTotals.income) *
                                              100
                                        : 0,
                                )
                            }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </V2TablePanel>
    </V2ListPage>
</template>

<style scoped>
.year-filter {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--muted-foreground);
}

.year-select {
    height: 2.25rem;
    min-width: 7.5rem;
    border-radius: 0.5rem;
    border: 1px solid var(--border);
    background: var(--background);
    padding: 0 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: none;
    letter-spacing: 0;
    color: var(--foreground);
}

.statement-table {
    width: 100%;
    font-size: 0.875rem;
}

.statement-table th,
.statement-table td {
    padding: 0.55rem 1.1rem;
    border-bottom: 1px solid var(--border);
}

.statement-table th {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--muted-foreground);
}

.statement-table .is-subtotal td {
    font-weight: 600;
    background: color-mix(in srgb, var(--muted) 55%, transparent);
}

.statement-table .is-emphasis td {
    font-weight: 700;
}

.statement-table .is-total td {
    font-weight: 700;
    background: color-mix(in srgb, var(--muted) 80%, transparent);
}

.empty-note {
    padding: 2.5rem 1rem;
    text-align: center;
    font-size: 0.875rem;
    color: var(--muted-foreground);
}

.metric-list {
    display: grid;
    gap: 0.65rem;
    padding: 0.25rem 0;
}

.metric-list li {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    font-size: 0.875rem;
}

.metric-list span {
    color: var(--muted-foreground);
}

.metric-list b {
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}
</style>
