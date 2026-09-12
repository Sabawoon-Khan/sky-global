<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowUpRight,
    Briefcase,
    FileText,
    TrendingUp,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    MisChart,
    MisChartCard,
} from '@/components/mis';
import SortableTh from '@/components/SortableTh.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import {
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2Panel,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useLocale } from '@/composables/useLocale';
import { useMisNavigation } from '@/composables/useMisNavigation';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { translateProjectStatus } from '@/lib/status-labels';
import {
    formatCurrency as formatCurrencyValue,
    formatLocalizedDate,
    formatNumber,
} from '@/lib/format';
import { dashboard } from '@/routes';

interface DashboardStats {
    bidding: {
        open_opportunities: number;
        pending_bids: number;
        win_rate: number;
        won: number;
        lost: number;
    };
    projects: {
        active: number;
        planning: number;
        total: number;
    };
    finance: {
        total_income_usd: number;
        total_expense_usd: number;
        overhead_usd?: number;
        net_usd: number;
    };
    hr: {
        employees: number;
        contractors: number;
        expiring_documents: number;
    };
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
    }>;
    workforce: { employees: number; contractors: number };
    bidding_outcomes: Array<{ key: string; value: number }>;
    project_statuses: Array<{ status: string; count: number }>;
    monthly_bids?: Array<{
        label: string;
        submitted: number;
        won: number;
        lost: number;
    }>;
}

const props = defineProps<{
    stats: DashboardStats | null;
    projectProfitability: ProjectProfitability[];
    expiringDocuments: ExpiringDocument[];
    charts: ChartData | null;
}>();

const page = usePage();
const { t } = useTranslations();
const { locale } = useLocale();
const { misQuickLinks } = useMisNavigation();

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Project') },
    { key: 'income', label: t('Income') },
    { key: 'expense', label: t('Expenses') },
    { key: 'margin', label: t('Margin') },
]);

const { sortedRows } = provideTableSort(
    () => props.projectProfitability as Array<Record<string, unknown>>,
);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const userName = computed(() => page.props.auth.user?.name ?? t('User'));

const todayLabel = computed(() =>
    formatLocalizedDate(new Date(), locale.value, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }),
);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return t('Good morning');
    if (hour < 17) return t('Good afternoon');
    return t('Good evening');
});

const totalExpenses = computed(() => {
    if (!props.stats) return 0;
    return (
        Number(props.stats.finance.total_expense_usd ?? 0) +
        Number(props.stats.finance.overhead_usd ?? 0)
    );
});

const monthlyChart = computed(() => {
    const rows = props.charts?.monthly_finance ?? [];
    return {
        labels: rows.map((row) => row.label),
        datasets: [
            {
                label: t('Income'),
                data: rows.map((row) => Number(row.income ?? 0)),
                color: 'var(--chart-2)',
            },
            {
                label: t('Expenses'),
                data: rows.map((row) => Number(row.expense ?? 0)),
                color: 'var(--chart-3)',
            },
        ],
    };
});

const biddingChart = computed(() => {
    const rows = props.charts?.bidding_outcomes ?? [];
    return {
        labels: rows.map((row) => t(row.key)),
        datasets: [
            {
                label: t('Bids'),
                data: rows.map((row) => row.value),
                color: 'var(--chart-1)',
            },
        ],
    };
});

const projectsChart = computed(() => {
    const rows = props.projectProfitability.slice(0, 6);
    return {
        labels: rows.map((row) => row.code || row.name),
        datasets: [
            {
                label: t('Income'),
                data: rows.map((row) => Number(row.income ?? 0)),
                color: 'var(--chart-1)',
            },
            {
                label: t('Margin'),
                data: rows.map((row) => Number(row.margin ?? 0)),
                color: 'var(--chart-3)',
            },
        ],
    };
});

const statusChart = computed(() => {
    const rows = props.charts?.project_statuses ?? [];
    return {
        labels: rows.map((row) => translateProjectStatus(t, row.status)),
        datasets: [
            {
                label: t('Projects'),
                data: rows.map((row) => row.count),
                color: 'var(--chart-2)',
            },
        ],
    };
});

function formatDate(value: string | null): string {
    if (!value) return '—';
    return formatLocalizedDate(value, locale.value, { dateStyle: 'medium' });
}

function personnelHref(doc: ExpiringDocument): string {
    const type = doc.personnel_type.toLowerCase();
    const isContractor =
        type === 'contractor' || type.includes('contractor');
    return `/hr/${isContractor ? 'contractors' : 'employees'}/${doc.personnel_id}`;
}

function openProject(row: Record<string, unknown>) {
    if (row?.id) {
        router.visit(`/mis/projects/${row.id}`);
    }
}
</script>

<template>
    <Head :title="t('Dashboard')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png" priority>
            <template #eyebrow>{{ todayLabel }}</template>
            <template #title>{{ greeting }}, {{ userName }}</template>
            <template #description>
                {{
                    t(
                        'Protective operations overview — bidding, sites, finance, and workforce.',
                    )
                }}
            </template>
            <template #side>
                <div class="detail-actions">
                    <Button as-child variant="outline" size="sm" class="detail-btn">
                        <Link href="/analytics/bidding">
                            {{ t('Bidding Analytics') }}
                            <ArrowUpRight class="size-4" />
                        </Link>
                    </Button>
                    <Button as-child size="sm" class="detail-btn primary">
                        <Link href="/analytics/finance">
                            {{ t('Finance Analytics') }}
                            <ArrowUpRight class="size-4" />
                        </Link>
                    </Button>
                </div>
            </template>
            <template v-if="stats" #stats>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Win Rate') }}</template>
                        <template #meta>
                            {{ formatNumber(stats.bidding.won) }} {{ t('won') }}
                        </template>
                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ formatNumber(stats.bidding.win_rate) }}%</strong>
                                <small>{{ t('Win Rate') }}</small>
                            </div>
                        </div>
                    </V2IndicatorCard>
                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Net Finance') }}</template>
                        <template #meta>{{ t('Base (AFN)') }}</template>
                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ formatCurrencyValue(stats.finance.net_usd) }}</strong>
                                <small>{{ t('Net') }}</small>
                            </div>
                        </div>
                    </V2IndicatorCard>
                </div>
                <V2StatGrid>
                    <V2StatCard
                        :title="t('Open Opportunities')"
                        :value="formatNumber(stats.bidding.open_opportunities)"
                    >
                        <template #icon><FileText /></template>
                    </V2StatCard>
                    <V2StatCard
                        icon-tone="warm"
                        :title="t('Active Projects')"
                        :value="formatNumber(stats.projects.active)"
                    >
                        <template #icon><Briefcase /></template>
                    </V2StatCard>
                    <V2StatCard
                        icon-tone="teal"
                        :title="t('Workforce')"
                        :value="
                            formatNumber(
                                stats.hr.employees + stats.hr.contractors,
                            )
                        "
                    >
                        <template #icon><Users /></template>
                    </V2StatCard>
                    <V2StatCard
                        accent
                        icon-tone="orange"
                        :title="t('Expiring Documents')"
                        :value="formatNumber(stats.hr.expiring_documents)"
                    >
                        <template #icon><AlertTriangle /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2Panel v-if="!stats" :title="t('Limited dashboard access')">
            <p class="text-sm text-muted-foreground">
                {{
                    t(
                        'Contact your administrator for full access to projects, finance, and analytics.',
                    )
                }}
            </p>
        </V2Panel>

        <template v-else>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <V2Panel padded>
                    <div class="flex items-start gap-3">
                        <span class="stat-icon teal"><FileText /></span>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">{{ t('Pending Bids') }}</p>
                            <p class="mt-1 text-xl font-semibold tabular-nums">
                                {{ formatNumber(stats.bidding.pending_bids) }}
                            </p>
                        </div>
                    </div>
                </V2Panel>
                <V2Panel padded>
                    <div class="flex items-start gap-3">
                        <span class="stat-icon"><Briefcase /></span>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">{{ t('Projects') }}</p>
                            <p class="mt-1 text-xl font-semibold tabular-nums">
                                {{ formatNumber(stats.projects.total) }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{ formatNumber(stats.projects.planning) }} {{ t('planning') }}
                            </p>
                        </div>
                    </div>
                </V2Panel>
                <V2Panel padded>
                    <div class="flex items-start gap-3">
                        <span class="stat-icon warm"><TrendingUp /></span>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">{{ t('Total Income') }}</p>
                            <p class="mt-1 text-xl font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">
                                {{ formatCurrencyValue(stats.finance.total_income_usd) }}
                            </p>
                        </div>
                    </div>
                </V2Panel>
                <V2Panel padded>
                    <div class="flex items-start gap-3">
                        <span class="stat-icon orange"><Wallet /></span>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">{{ t('Total Expenses') }}</p>
                            <p class="mt-1 text-xl font-semibold tabular-nums">
                                {{ formatCurrencyValue(totalExpenses) }}
                            </p>
                        </div>
                    </div>
                </V2Panel>
            </div>

            <div v-if="charts" class="grid gap-4 xl:grid-cols-5">
                <MisChartCard
                    class="xl:col-span-3"
                    :title="t('Income vs expenses')"
                    :description="t('Grouped totals for the selected period')"
                    type="bar"
                    :labels="monthlyChart.labels"
                    :datasets="monthlyChart.datasets"
                />
                <MisChartCard
                    class="xl:col-span-2"
                    :title="t('Bidding Outcomes')"
                    :description="`${formatNumber(stats.bidding.win_rate)}% ${t('Win Rate')}`"
                    type="pie"
                    :labels="biddingChart.labels"
                    :datasets="biddingChart.datasets"
                />
            </div>

            <div v-if="charts" class="grid gap-4 xl:grid-cols-5">
                <MisChartCard
                    class="xl:col-span-2"
                    :title="t('Top projects')"
                    :description="t('Income and margin')"
                    type="bar"
                    :labels="projectsChart.labels"
                    :datasets="projectsChart.datasets"
                />
                <V2TablePanel
                    class="xl:col-span-3"
                    table-id="dashboard-projects"
                    :columns="tableColumns"
                    :delay="false"
                >
                    <template #filters>
                        <div class="table-top">
                            <div>
                                <h2>{{ t('Projects') }}</h2>
                            </div>
                            <Button as-child variant="ghost" size="sm">
                                <Link href="/mis/projects">
                                    {{ t('View all') }}
                                    <ArrowUpRight class="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </template>
                    <table>
                        <thead>
                            <tr>
                                <TableIndexTh />
                                <SortableTh column="name">{{ t('Project') }}</SortableTh>
                                <SortableTh column="income">{{ t('Income') }}</SortableTh>
                                <SortableTh column="expense">{{ t('Expenses') }}</SortableTh>
                                <SortableTh column="margin">{{ t('Margin') }}</SortableTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(p, index) in sortedRows.slice(0, 8)"
                                :key="String(p.id)"
                                @click="openProject(p)"
                            >
                                <TableIndexTd :index="index" />
                                <td>
                                    <Link
                                        :href="`/mis/projects/${p.id}`"
                                        class="font-medium hover:text-primary hover:underline"
                                        @click.stop
                                    >
                                        {{ p.name }}
                                    </Link>
                                    <div class="text-xs text-muted-foreground">
                                        {{ p.code }}
                                        <span v-if="p.organization">
                                            · {{ p.organization }}
                                        </span>
                                    </div>
                                </td>
                                <td class="tabular-nums">
                                    {{ formatCurrencyValue(Number(p.income ?? 0)) }}
                                </td>
                                <td class="tabular-nums">
                                    {{ formatCurrencyValue(Number(p.expense ?? 0)) }}
                                </td>
                                <td
                                    class="font-medium tabular-nums"
                                    :class="
                                        Number(p.margin) < 0
                                            ? 'text-rose-700 dark:text-rose-400'
                                            : 'text-emerald-700 dark:text-emerald-400'
                                    "
                                >
                                    {{ formatCurrencyValue(Number(p.margin ?? 0)) }}
                                </td>
                            </tr>
                            <tr v-if="!projectProfitability.length">
                                <td colspan="5" class="py-8 text-center text-muted-foreground">
                                    {{ t('No projects yet.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </V2TablePanel>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <V2Panel :title="t('Compliance Alerts')" :padded="false">
                    <template #actions>
                        <Button as-child variant="ghost" size="sm">
                            <Link href="/hr/employees">
                                {{ t('Open') }}
                                <ArrowUpRight class="size-4" />
                            </Link>
                        </Button>
                    </template>
                    <div class="divide-y">
                        <Link
                            v-for="doc in expiringDocuments.slice(0, 6)"
                            :key="doc.id"
                            :href="personnelHref(doc)"
                            class="flex items-center justify-between gap-3 px-5 py-3 text-sm transition-colors hover:bg-muted/30"
                        >
                            <div class="min-w-0">
                                <div class="truncate font-medium">
                                    {{ doc.type ?? t('Document') }}
                                </div>
                                <div class="truncate text-xs text-muted-foreground">
                                    {{ doc.personnel_type }} #{{ doc.personnel_id }}
                                </div>
                            </div>
                            <div class="shrink-0 font-semibold tabular-nums text-amber-700">
                                {{ formatDate(doc.expires_at) }}
                            </div>
                        </Link>
                        <div
                            v-if="!expiringDocuments.length"
                            class="px-5 py-6 text-sm text-muted-foreground"
                        >
                            {{ t('No documents expiring within 30 days.') }}
                        </div>
                    </div>
                </V2Panel>

                <V2Panel v-if="charts" :title="t('Project Status Breakdown')">
                    <MisChart
                        type="bar"
                        orientation="horizontal"
                        :labels="statusChart.labels"
                        :datasets="statusChart.datasets"
                        :show-values="false"
                    />
                </V2Panel>

                <V2Panel :title="t('Quick Actions')" :padded="false">
                    <div class="divide-y">
                        <Link
                            v-for="link in misQuickLinks.slice(0, 6)"
                            :key="link.title"
                            :href="link.href"
                            class="flex items-center gap-3 px-5 py-3 text-sm transition-colors hover:bg-muted/30"
                        >
                            <span
                                class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/8 text-primary"
                            >
                                <component
                                    :is="link.icon"
                                    class="size-3.5"
                                    stroke-width="1.75"
                                />
                            </span>
                            <span class="min-w-0 flex-1 truncate font-medium">
                                {{ link.title }}
                            </span>
                            <ArrowUpRight
                                class="size-3.5 shrink-0 text-muted-foreground"
                            />
                        </Link>
                        <div
                            v-if="!misQuickLinks.length"
                            class="px-5 py-6 text-sm text-muted-foreground"
                        >
                            {{ t('No quick actions available.') }}
                        </div>
                    </div>
                </V2Panel>
            </div>
        </template>
    </V2ListPage>
</template>
