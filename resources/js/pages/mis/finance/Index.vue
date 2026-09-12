<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Can from '@/components/Can.vue';
import BarChart from '@/components/charts/BarChart.vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import {
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2Panel,
    V2StatCard,
    V2StatGrid,
} from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn } from '@/lib/format';
import {
    ArrowDownRight,
    ArrowUpRight,
    CalendarDays,
    Wallet,
} from '@lucide/vue';

interface FinanceSummary {
    total_income?: number;
    project_income?: number;
    general_income?: number;
    total_expenses?: number;
    project_expenses?: number;
    general_expenses?: number;
    outstanding?: number;
    net?: number;
    expenses_today?: number;
    expenses_this_month?: number;
    income_today?: number;
    income_this_month?: number;
}

interface FinanceCharts {
    monthly: Array<{
        label: string;
        income: number;
        expense: number;
        net: number;
    }>;
    finance_breakdown: Array<{ key: string; value: number }>;
}

const props = defineProps<{
    summary?: FinanceSummary;
    charts?: FinanceCharts;
}>();

const { t, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Finance', href: '/finance' }],
    },
});

const financeLabel = (key: string): string => {
    const labels: Record<string, string> = {
        project_income: t('Project Income'),
        general_income: t('Other Income'),
        project_expense: t('Project Expenses'),
        overhead: t('Overhead & Salaries'),
    };
    return labels[key] ?? key;
};

const netValue = computed(
    () =>
        props.summary?.net ??
        (props.summary?.total_income ?? 0) -
            (props.summary?.total_expenses ?? 0),
);

const statusPalette = [
    'var(--school-navy)',
    'var(--brand-accent)',
    'var(--school-gold)',
    'var(--muted-foreground)',
];

const pipeline = computed(() => {
    const rows = (props.charts?.finance_breakdown ?? []).filter(
        (row) => Number(row.value) > 0,
    );
    const total = Math.max(
        rows.reduce((sum, row) => sum + Number(row.value || 0), 0),
        1,
    );
    const incomeShare = Math.round(
        ((Number(props.summary?.total_income ?? 0) /
            Math.max(
                Number(props.summary?.total_income ?? 0) +
                    Number(props.summary?.total_expenses ?? 0),
                1,
            )) *
            100),
    );

    return {
        incomeShare,
        segments: rows.map((row, index) => ({
            key: row.key,
            label: financeLabel(row.key),
            value: Number(row.value) || 0,
            color: statusPalette[index % statusPalette.length],
            width: Math.max(6, (Number(row.value) / total) * 100),
        })),
    };
});

const monthlyBars = computed(() => {
    const rows = props.charts?.monthly?.length
        ? props.charts.monthly.map((row) => ({
              key: row.label,
              label: row.label,
              value: Number(row.net ?? 0),
          }))
        : [];
    const max = Math.max(...rows.map((row) => Math.abs(row.value) || 0), 1);

    return rows.map((row) => ({
        ...row,
        height: Math.max(
            Math.abs(row.value) > 0 ? 6 : 3,
            Math.round((Math.abs(row.value) / max) * 44),
        ),
        peak: Math.abs(row.value) === max && row.value !== 0,
    }));
});

const monthNet = computed(
    () =>
        (props.summary?.income_this_month ?? 0) -
        (props.summary?.expenses_this_month ?? 0),
);
</script>

<template>
    <Head :title="t('Finance')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png" priority>
            <template #eyebrow>{{ t('Finance') }}</template>
            <template #title>{{ t('Finance overview') }}</template>
            <template #description>
                {{ t('Income, expenses, and net position at a glance.') }}
            </template>
            <template #side>
                <Link
                    v-if="can('finance.view')"
                    href="/analytics/finance"
                    class="create-btn"
                >
                    <Wallet />
                    {{ t('View Analytics') }}
                </Link>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Breakdown') }}</template>
                        <template #meta>
                            {{ pipeline.incomeShare }}% {{ t('Income') }}
                        </template>
                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ formatAfn(summary?.total_income) }}</strong>
                                <small>{{ t('Income') }}</small>
                            </div>
                        </div>
                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in pipeline.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>
                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in pipeline.segments.slice(0, 4)"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ formatAfn(seg.value) }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Net by month') }}</template>
                        <template #meta>
                            {{ formatAfn(netValue) }} {{ t('Net') }}
                        </template>
                        <div class="money-chart">
                            <div
                                v-for="bar in monthlyBars"
                                :key="bar.key"
                                class="money-col"
                                :class="{ peak: bar.peak }"
                                :title="`${bar.label}: ${bar.value}`"
                            >
                                <div class="money-pair">
                                    <i
                                        class="usd"
                                        :style="{ height: `${bar.height}px` }"
                                    />
                                </div>
                                <span>{{ bar.label }}</span>
                            </div>
                        </div>
                    </V2IndicatorCard>
                </div>
            </template>

            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Total Income')"
                        :value="formatAfn(summary?.total_income)"
                    >
                        <template #icon><ArrowUpRight /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Total Expenses')"
                        :value="formatAfn(summary?.total_expenses)"
                    >
                        <template #icon><ArrowDownRight /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Net Position')"
                        :value="formatAfn(netValue)"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Outstanding')"
                        :value="formatAfn(summary?.outstanding)"
                    >
                        <template #icon><CalendarDays /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <div
            v-if="!summary"
            class="ui-empty-state rounded-2xl border border-border/70 bg-card p-6"
        >
            {{ t('No summary data available.') }}
        </div>

        <template v-else>
            <div class="grid gap-3 sm:grid-cols-3">
                <V2Panel>
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t('Today') }}
                    </p>
                    <p class="mt-2 text-xl font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">
                        {{ formatAfn(summary.income_today) }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ t('Income') }} ·
                        <span class="text-destructive">
                            {{ formatAfn(summary.expenses_today) }}
                            {{ t('Expenses') }}
                        </span>
                    </p>
                </V2Panel>
                <V2Panel>
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t('This Month') }}
                    </p>
                    <p class="mt-2 text-xl font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">
                        {{ formatAfn(summary.income_this_month) }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ t('Income') }} ·
                        <span class="text-destructive">
                            {{ formatAfn(summary.expenses_this_month) }}
                            {{ t('Expenses') }}
                        </span>
                    </p>
                </V2Panel>
                <V2Panel>
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t('This Month Net') }}
                    </p>
                    <p
                        class="mt-2 text-xl font-semibold tabular-nums"
                        :class="
                            monthNet >= 0
                                ? 'text-emerald-700 dark:text-emerald-400'
                                : 'text-destructive'
                        "
                    >
                        {{ formatAfn(monthNet) }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ t('Income minus expenses') }}
                    </p>
                </V2Panel>
            </div>

            <div
                v-if="charts"
                class="grid gap-4 lg:grid-cols-5"
            >
                <V2Panel
                    class="lg:col-span-3"
                    :title="t('Monthly Trends')"
                    :description="t('Last 6 months income vs expenses')"
                >
                    <BarChart
                        :labels="charts.monthly.map((m) => m.label)"
                        :datasets="[
                            {
                                label: t('Income'),
                                data: charts.monthly.map((m) => m.income),
                                backgroundColor: 'rgba(31, 78, 95, 0.85)',
                            },
                            {
                                label: t('Expenses'),
                                data: charts.monthly.map((m) => m.expense),
                                backgroundColor: 'rgba(184, 149, 108, 0.85)',
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
                            charts.finance_breakdown.map((f) =>
                                financeLabel(f.key),
                            )
                        "
                        :data="charts.finance_breakdown.map((f) => f.value)"
                        :center-label="t('Total')"
                    />
                </V2Panel>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Link href="/finance/income" class="block">
                    <V2Panel>
                        <p class="text-xs text-muted-foreground">{{ t('Project Income') }}</p>
                        <p class="mt-2 text-lg font-semibold tabular-nums">
                            {{ formatAfn(summary.project_income) }}
                        </p>
                    </V2Panel>
                </Link>
                <Link href="/finance/general-income" class="block">
                    <V2Panel>
                        <p class="text-xs text-muted-foreground">{{ t('Other Income') }}</p>
                        <p class="mt-2 text-lg font-semibold tabular-nums">
                            {{ formatAfn(summary.general_income) }}
                        </p>
                    </V2Panel>
                </Link>
                <Link href="/finance/expenses" class="block">
                    <V2Panel>
                        <p class="text-xs text-muted-foreground">{{ t('Project Expenses') }}</p>
                        <p class="mt-2 text-lg font-semibold tabular-nums">
                            {{ formatAfn(summary.project_expenses) }}
                        </p>
                    </V2Panel>
                </Link>
                <Link href="/finance/general-expenses" class="block">
                    <V2Panel>
                        <p class="text-xs text-muted-foreground">{{ t('Overhead & Salaries') }}</p>
                        <p class="mt-2 text-lg font-semibold tabular-nums">
                            {{ formatAfn(summary.general_expenses) }}
                        </p>
                    </V2Panel>
                </Link>
            </div>

            <Can permission="finance.view">
                <div class="flex justify-end">
                    <Link
                        href="/analytics/finance"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        {{ t('Open full analytics') }} →
                    </Link>
                </div>
            </Can>
        </template>
    </V2ListPage>
</template>
