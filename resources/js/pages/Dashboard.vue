<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    BarChart3,
    Briefcase,
    Building2,
    DollarSign,
    FileText,
    Target,
    TrendingUp,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import BarChart from '@/components/charts/BarChart.vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import Can from '@/components/Can.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import { useMisNavigation } from '@/composables/useMisNavigation';
import { useTranslations } from '@/composables/useTranslations';
import { translateProjectStatus } from '@/lib/status-labels';
import {
    formatCurrency as formatCurrencyValue,
    formatLocalizedDate,
    formatNumber,
    getIntlLocale,
    LATIN_NUMERALS,
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

const monthLabel = computed(() =>
    formatLocalizedDate(new Date(), locale.value, {
        month: 'long',
        year: 'numeric',
    }),
);

const weekdayLabels = computed(() => {
    const formatter = new Intl.DateTimeFormat(getIntlLocale(locale.value), {
        weekday: 'short',
        ...LATIN_NUMERALS,
    });
    const sunday = new Date(2024, 0, 7);

    return Array.from({ length: 7 }, (_, index) => {
        const date = new Date(sunday);
        date.setDate(sunday.getDate() + index);

        return formatter.format(date);
    });
});

const statCards = computed(() => {
    if (!props.stats) return [];

    const cards = [
        {
            label: t('Open Opportunities'),
            value: formatNumber(props.stats.bidding.open_opportunities),
            sub: `${formatNumber(props.stats.bidding.pending_bids)} ${t('pending bids')}`,
            icon: FileText,
            href: '/bidding/opportunities',
            status:
                props.stats.bidding.pending_bids > 0
                    ? ('warning' as const)
                    : ('normal' as const),
        },
        {
            label: t('Win Rate'),
            value: `${formatNumber(props.stats.bidding.win_rate)}%`,
            sub: `${formatNumber(props.stats.bidding.won)} ${t('won')} / ${formatNumber(props.stats.bidding.lost)} ${t('lost')}`,
            icon: TrendingUp,
            href: '/analytics/bidding',
            status: 'normal' as const,
        },
        {
            label: t('Active Projects'),
            value: formatNumber(props.stats.projects.active),
            sub: `${formatNumber(props.stats.projects.planning)} ${t('planning')}`,
            icon: Briefcase,
            href: '/projects',
            status: 'normal' as const,
        },
        {
            label: t('Total Projects'),
            value: formatNumber(props.stats.projects.total),
            sub: `${formatNumber(props.stats.projects.active)} ${t('active projects')}`,
            icon: Target,
            href: '/projects',
            status: 'normal' as const,
        },
        {
            label: t('Net Finance'),
            value: formatCurrencyValue(props.stats.finance.net_usd),
            sub: t('Base (USD)'),
            icon: DollarSign,
            href: '/finance',
            status:
                props.stats.finance.net_usd < 0
                    ? ('warning' as const)
                    : ('normal' as const),
        },
        {
            label: t('Workforce'),
            value: formatNumber(
                props.stats.hr.employees + props.stats.hr.contractors,
            ),
            sub: `${formatNumber(props.stats.hr.employees)} ${t('employees')} · ${formatNumber(props.stats.hr.contractors)} ${t('contractors')}`,
            icon: Users,
            href: '/hr/employees',
            status: 'normal' as const,
        },
        {
            label: t('Expiring Documents'),
            value: formatNumber(props.stats.hr.expiring_documents),
            sub: t('Within 30 days'),
            icon: AlertTriangle,
            href: '/hr/employees',
            status:
                props.stats.hr.expiring_documents > 0
                    ? ('critical' as const)
                    : ('normal' as const),
        },
        {
            label: t('Competitor Intel'),
            value: formatNumber(props.stats.competitor_intel),
            sub: t('Recorded competitor bids'),
            icon: BarChart3,
            href: '/analytics/bidding',
            status: 'normal' as const,
        },
    ];

    return cards;
});

const systemStatus = computed(() => {
    if (!props.stats) return 'offline';

    if (props.stats.hr.expiring_documents > 0) return 'alert';
    if (props.stats.bidding.pending_bids > 0) return 'monitoring';

    return 'operational';
});

const activeAlerts = computed(() => {
    if (!props.stats) return 0;

    return (
        props.stats.hr.expiring_documents +
        (props.stats.bidding.pending_bids > 0 ? 1 : 0)
    );
});

function sectorCode(index: number): string {
    return `SEC-${String(index + 1).padStart(2, '0')}`;
}

const financeSummary = computed(() => {
    if (!props.stats) return [];

    return [
        {
            label: t('Total Income'),
            value: formatCurrencyValue(props.stats.finance.total_income_usd),
            tone: 'income' as const,
            code: 'FIN-01',
        },
        {
            label: t('Total Expenses'),
            value: formatCurrencyValue(
                props.stats.finance.total_expense_usd +
                    (props.stats.finance.overhead_usd ?? 0),
            ),
            tone: 'expense' as const,
            code: 'FIN-02',
        },
        {
            label: t('Net Finance'),
            value: formatCurrencyValue(props.stats.finance.net_usd),
            tone: 'net' as const,
            code: 'FIN-03',
        },
    ];
});

const topProjects = computed(() => props.projectProfitability.slice(0, 6));

const maxMargin = computed(() =>
    Math.max(...topProjects.value.map((p) => Math.abs(p.margin)), 1),
);

const calendarDays = computed(() => {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const cells: Array<{ day: number | null; today: boolean }> = [];

    for (let i = 0; i < firstDay; i++) cells.push({ day: null, today: false });
    for (let d = 1; d <= daysInMonth; d++) {
        cells.push({ day: d, today: d === now.getDate() });
    }

    return cells;
});

const outcomeLabel = (key: string): string => {
    if (key === 'won') return t('won');
    if (key === 'lost') return t('lost');
    if (key === 'pending') return t('Pending');

    return key;
};

function formatDate(value: string | null): string {
    if (!value) return '—';

    return formatLocalizedDate(value, locale.value, { dateStyle: 'medium' });
}

function initials(name: string): string {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}
</script>

<template>
    <Head :title="t('Dashboard')" />

    <div class="soc-dashboard flex flex-1 flex-col gap-6 p-4">
        <div
            v-if="stats"
            class="dashboard-reveal flex flex-wrap justify-end gap-2"
        >
                <Can permission="projects.view">
                    <Button variant="outline" size="sm" as-child>
                        <Link href="/analytics/bidding">
                            {{ t('Bidding Analytics') }}
                        </Link>
                    </Button>
                </Can>
                <Can permission="finance.view">
                    <Button variant="outline" size="sm" as-child>
                        <Link href="/analytics/finance">
                            {{ t('Finance Analytics') }}
                        </Link>
                    </Button>
                </Can>
        </div>

        <!-- Command center status bar -->
        <section
            v-if="stats"
            class="soc-command-bar dashboard-reveal p-5 md:p-6"
            style="animation-delay: 60ms"
        >
            <div class="soc-command-scan" />
            <div
                class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="space-y-2">
                    <div class="soc-live">
                        <span class="soc-live-dot" />
                        {{ t('Live Monitoring') }}
                    </div>
                    <h1
                        class="text-lg font-bold uppercase tracking-[0.12em] text-white md:text-xl"
                    >
                        {{ t('Operations Command Center') }}
                    </h1>
                    <p class="font-mono text-xs text-white/55">
                        {{ t('Operator') }}:
                        <span class="text-white/90">{{ userName }}</span>
                        <span class="mx-2 text-white/25">|</span>
                        {{ todayLabel }}
                        <span class="soc-cursor text-red-400">_</span>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="soc-status-pill"
                        :class="
                            systemStatus === 'operational'
                                ? 'soc-status-pill--ok'
                                : 'soc-status-pill--alert'
                        "
                    >
                        <span
                            class="soc-led"
                            :data-status="
                                systemStatus === 'operational'
                                    ? 'normal'
                                    : systemStatus === 'monitoring'
                                      ? 'warning'
                                      : 'critical'
                            "
                        />
                        {{
                            systemStatus === 'operational'
                                ? t('All sectors operational')
                                : systemStatus === 'monitoring'
                                  ? t('Monitoring active')
                                  : t('Attention required')
                        }}
                    </span>
                    <span
                        v-if="activeAlerts > 0"
                        class="soc-status-pill soc-status-pill--alert"
                    >
                        {{ formatNumber(activeAlerts) }}
                        {{ t('Active alerts') }}
                    </span>
                    <span class="soc-status-pill">
                        {{ formatNumber(stats.projects.active) }}
                        {{ t('active projects') }}
                    </span>
                </div>
            </div>
        </section>

        <!-- Limited access notice -->
        <Card v-if="!stats" class="border-dashed">
            <CardHeader>
                <CardTitle>{{ t('Limited dashboard access') }}</CardTitle>
                <CardDescription>
                    {{
                        t(
                            'Contact your administrator for full access to projects, finance, and analytics.',
                        )
                    }}
                </CardDescription>
            </CardHeader>
        </Card>

        <!-- Key metrics -->
        <section v-if="stats" class="dashboard-reveal" style="animation-delay: 120ms">
            <div class="soc-section-head">
                <h2 class="soc-section-title">
                    {{ t('Operational Metrics') }}
                </h2>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <template
                    v-for="(card, index) in statCards"
                    :key="card.label"
                >
                    <Link
                        :href="card.href"
                        class="dashboard-reveal soc-tile"
                        :class="{
                            'soc-tile--warning': card.status === 'warning',
                            'soc-tile--critical': card.status === 'critical',
                        }"
                        :style="{ animationDelay: `${160 + index * 50}ms` }"
                    >
                        <div class="soc-tile-top">
                            <span class="soc-sector">{{
                                sectorCode(index)
                            }}</span>
                            <span
                                class="soc-led"
                                :data-status="card.status"
                            />
                        </div>
                        <div class="flex items-end justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="soc-metric-label">
                                    {{ card.label }}
                                </p>
                                <p class="soc-metric-value mt-1">
                                    {{ card.value }}
                                </p>
                                <p class="soc-metric-sub mt-1 truncate">
                                    {{ card.sub }}
                                </p>
                            </div>
                            <div class="soc-tile-icon shrink-0">
                                <component
                                    :is="card.icon"
                                    class="size-4"
                                    stroke-width="1.75"
                                />
                            </div>
                        </div>
                    </Link>
                </template>
            </div>
        </section>

        <!-- Finance summary -->
        <section v-if="stats" class="dashboard-reveal" style="animation-delay: 200ms">
            <div class="soc-section-head">
                <h2 class="soc-section-title">
                    {{ t('Financial Intelligence') }}
                </h2>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div
                    v-for="(item, index) in financeSummary"
                    :key="item.label"
                    class="dashboard-reveal soc-finance-block"
                    :class="`soc-finance-block--${item.tone}`"
                    :style="{ animationDelay: `${240 + index * 60}ms` }"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <span class="soc-sector">{{ item.code }}</span>
                        <span class="soc-led" data-status="normal" />
                    </div>
                    <p class="soc-metric-label">{{ item.label }}</p>
                    <p class="soc-metric-value mt-2">{{ item.value }}</p>
                </div>
            </div>
        </section>

        <!-- Quick actions -->
        <section
            v-if="misQuickLinks.length > 0"
            class="dashboard-reveal"
            style="animation-delay: 280ms"
        >
            <div class="soc-section-head">
                <h2 class="soc-section-title">
                    {{ t('Rapid Deployment') }}
                </h2>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <Link
                    v-for="(link, index) in misQuickLinks"
                    :key="link.href"
                    :href="link.href"
                    class="dashboard-reveal soc-quick-tile group"
                    :style="{ animationDelay: `${320 + index * 40}ms` }"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <div
                            class="soc-tile-icon group-hover:bg-red-500/12"
                        >
                            <component
                                :is="link.icon"
                                class="size-4"
                                stroke-width="1.75"
                            />
                        </div>
                        <ArrowRight
                            class="size-3.5 text-muted-foreground opacity-0 transition-opacity group-hover:text-primary group-hover:opacity-100"
                        />
                    </div>
                    <p class="text-sm font-semibold">{{ link.title }}</p>
                    <p
                        v-if="link.description"
                        class="mt-1 line-clamp-2 text-xs text-muted-foreground"
                    >
                        {{ link.description }}
                    </p>
                </Link>
            </div>
        </section>

        <!-- Analytics & insights -->
        <section v-if="charts" class="dashboard-reveal" style="animation-delay: 360ms">
            <div class="soc-section-head">
                <h2 class="soc-section-title">
                    {{ t('Intel & Analytics') }}
                </h2>
            </div>
            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <Card
                    class="soc-panel dashboard-reveal xl:col-span-2"
                    style="animation-delay: 400ms"
                >
                    <CardHeader class="soc-panel-header flex-row items-start pb-4">
                        <div>
                            <p class="soc-panel-tag">ANL-01</p>
                            <CardTitle class="mt-1 text-base font-bold uppercase tracking-wide">
                                {{ t('Monthly Finance') }}
                            </CardTitle>
                            <CardDescription>
                                {{ t('Income vs expenses over 6 months') }}
                            </CardDescription>
                        </div>
                        <Can permission="finance.view">
                            <Button variant="ghost" size="sm" as-child>
                                <Link href="/analytics/finance">
                                    {{ t('View Analytics') }}
                                </Link>
                            </Button>
                        </Can>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="mb-4 flex items-center gap-4 text-xs font-medium"
                        >
                            <span
                                class="flex items-center gap-1.5 text-muted-foreground"
                            >
                                <span
                                    class="size-2.5 rounded-full bg-school-navy"
                                />
                                {{ t('Income') }}
                            </span>
                            <span
                                class="flex items-center gap-1.5 text-muted-foreground"
                            >
                                <span
                                    class="size-2.5 rounded-full bg-school-gold"
                                />
                                {{ t('Expenses') }}
                            </span>
                        </div>
                        <BarChart
                            :labels="
                                charts.monthly_finance.map((m) => m.label)
                            "
                            :datasets="[
                                {
                                    label: t('Income'),
                                    data: charts.monthly_finance.map(
                                        (m) => m.income,
                                    ),
                                    backgroundColor: '#0a0a0a',
                                },
                                {
                                    label: t('Expenses'),
                                    data: charts.monthly_finance.map(
                                        (m) => m.expense,
                                    ),
                                    backgroundColor: '#dc2626',
                                },
                            ]"
                            :height="240"
                        />
                    </CardContent>
                </Card>

                <Card
                    class="soc-panel dashboard-reveal"
                    style="animation-delay: 440ms"
                >
                    <CardHeader class="soc-panel-header pb-4">
                        <p class="soc-panel-tag">ANL-02</p>
                        <CardTitle class="mt-1 text-base font-bold uppercase tracking-wide">
                            {{ t('Bidding Outcomes') }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('Win Rate Overview') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DonutChart
                            v-if="charts.bidding_outcomes.length > 0"
                            :labels="
                                charts.bidding_outcomes.map((b) =>
                                    outcomeLabel(b.key),
                                )
                            "
                            :data="
                                charts.bidding_outcomes.map((b) => b.value)
                            "
                            :colors="['#22c55e', '#ef4444', '#dc2626']"
                            :height="220"
                        />
                        <p
                            v-else
                            class="py-10 text-center text-sm text-muted-foreground"
                        >
                            {{ t('No bids submitted yet.') }}
                        </p>
                    </CardContent>
                </Card>

                <Card
                    class="soc-panel dashboard-reveal lg:col-span-2 xl:col-span-3"
                    style="animation-delay: 480ms"
                >
                    <CardHeader class="soc-panel-header pb-4">
                        <p class="soc-panel-tag">ANL-03</p>
                        <CardTitle class="mt-1 text-base font-bold uppercase tracking-wide">
                            {{ t('Project Status Breakdown') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <BarChart
                            v-if="charts.project_statuses.length > 0"
                            :labels="
                                charts.project_statuses.map((s) =>
                                    translateProjectStatus(t, s.status),
                                )
                            "
                            :datasets="[
                                {
                                    label: t('Projects'),
                                    data: charts.project_statuses.map(
                                        (s) => s.count,
                                    ),
                                    backgroundColor: '#0a0a0a',
                                },
                            ]"
                            :height="220"
                        />
                        <p
                            v-else
                            class="py-10 text-center text-sm text-muted-foreground"
                        >
                            {{ t('No projects yet') }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </section>

        <!-- Operations -->
        <section v-if="stats" class="dashboard-reveal" style="animation-delay: 420ms">
            <div class="soc-section-head">
                <h2 class="soc-section-title">
                    {{ t('Field Operations') }}
                </h2>
            </div>
            <div class="grid gap-4 lg:grid-cols-3">
                <Card
                    class="soc-panel dashboard-reveal lg:col-span-2"
                    style="animation-delay: 460ms"
                >
                    <CardHeader class="soc-panel-header pb-4">
                        <div>
                            <p class="soc-panel-tag">OPS-01</p>
                            <CardTitle class="mt-1 text-base font-bold uppercase tracking-wide">
                                {{ t('Compliance Alerts') }}
                            </CardTitle>
                            <CardDescription>
                                {{ t('Personnel attachments expiring soon') }}
                            </CardDescription>
                        </div>
                        <span
                            v-if="expiringDocuments.length > 0"
                            class="soc-status-pill soc-status-pill--alert"
                        >
                            {{ formatNumber(expiringDocuments.length) }}
                            {{ t('Active alerts') }}
                        </span>
                    </CardHeader>
                    <CardContent class="max-h-64 space-y-2 overflow-y-auto pe-1">
                        <div
                            v-if="expiringDocuments.length === 0"
                            class="flex items-center gap-3 rounded-lg bg-emerald-500/8 px-4 py-6"
                        >
                            <span class="soc-led" data-status="normal" />
                            <p class="text-sm text-muted-foreground">
                                {{ t('No documents expiring within 30 days.') }}
                            </p>
                        </div>
                        <div
                            v-for="(doc, index) in expiringDocuments.slice(0, 8)"
                            :key="doc.id"
                            class="dashboard-reveal soc-alert-row"
                            :style="{ animationDelay: `${500 + index * 40}ms` }"
                        >
                            <div class="min-w-0">
                                <p class="soc-alert-id">
                                    ALT-{{ String(doc.id).padStart(4, '0') }}
                                </p>
                                <p class="truncate font-medium">
                                    {{ doc.type ?? t('Document') }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ doc.personnel_type }} #{{
                                        doc.personnel_id
                                    }}
                                </p>
                            </div>
                            <Badge
                                variant="destructive"
                                class="shrink-0 font-mono text-[10px] uppercase tracking-wide"
                            >
                                {{ formatDate(doc.expires_at) }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card
                    class="soc-panel dashboard-reveal"
                    style="animation-delay: 480ms"
                >
                    <CardHeader class="soc-panel-header pb-4">
                        <p class="soc-panel-tag">OPS-02</p>
                        <CardTitle class="mt-1 text-base font-bold uppercase tracking-wide">
                            {{ monthLabel }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="mb-2 grid grid-cols-7 gap-1 text-center text-[10px] font-medium uppercase tracking-wide text-muted-foreground"
                        >
                            <span
                                v-for="(day, index) in weekdayLabels"
                                :key="index"
                            >
                                {{ day }}
                            </span>
                        </div>
                        <div
                            class="grid grid-cols-7 gap-1 text-center text-xs"
                        >
                            <span
                                v-for="(cell, i) in calendarDays"
                                :key="i"
                                class="py-1"
                            >
                                <span
                                    v-if="cell.day"
                                    class="inline-flex size-7 items-center justify-center rounded-full"
                                    :class="
                                        cell.today
                                            ? 'dashboard-today-pulse bg-primary font-semibold text-white'
                                            : 'text-foreground'
                                    "
                                >
                                    {{ formatNumber(cell.day) }}
                                </span>
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>

        <!-- Projects & workforce -->
        <section
            v-if="stats && charts"
            class="dashboard-reveal"
            style="animation-delay: 500ms"
        >
            <div class="soc-section-head">
                <h2 class="soc-section-title">
                    {{ t('Project Profitability') }}
                </h2>
            </div>
            <div class="grid gap-4 xl:grid-cols-12">
                <Card
                    class="soc-panel dashboard-reveal xl:col-span-7"
                    style="animation-delay: 540ms"
                >
                    <CardHeader
                        class="soc-panel-header flex-row items-start pb-4"
                    >
                        <div>
                            <p class="soc-panel-tag">PRJ-01</p>
                            <CardTitle class="mt-1 text-base font-bold uppercase tracking-wide">
                                {{ t('Top Projects by Margin') }}
                            </CardTitle>
                            <CardDescription>
                                {{ t('Income vs expense by project') }}
                            </CardDescription>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link href="/projects">
                                {{ t('View Projects') }}
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="topProjects.length === 0"
                            class="py-10 text-center text-sm text-muted-foreground"
                        >
                            {{ t('No project data available.') }}
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full min-w-[480px] text-sm">
                                <thead>
                                    <tr
                                        class="border-b border-border/60 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400"
                                    >
                                        <th
                                            class="pb-3 text-start font-semibold"
                                        >
                                            {{ t('Project') }}
                                        </th>
                                        <th
                                            class="pb-3 text-start font-semibold"
                                        >
                                            {{ t('Organization') }}
                                        </th>
                                        <th
                                            class="pb-3 text-end font-semibold"
                                        >
                                            {{ t('Margin') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/40">
                                    <tr
                                        v-for="(project, index) in topProjects"
                                        :key="project.id"
                                        class="group dashboard-reveal"
                                        :style="{
                                            animationDelay: `${680 + index * 60}ms`,
                                        }"
                                    >
                                        <td class="py-3 pe-4">
                                            <Link
                                                :href="`/projects/${project.id}`"
                                                class="flex items-center gap-3 hover:text-school-navy"
                                            >
                                                <span
                                                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[color-mix(in_srgb,var(--school-navy)_10%,transparent)] text-xs font-semibold text-school-navy"
                                                >
                                                    {{
                                                        initials(project.name)
                                                    }}
                                                </span>
                                                <span>
                                                    <span
                                                        class="block font-medium"
                                                        >{{
                                                            project.name
                                                        }}</span
                                                    >
                                                    <span
                                                        class="text-xs text-muted-foreground"
                                                        >{{
                                                            project.code
                                                        }}</span
                                                    >
                                                </span>
                                            </Link>
                                        </td>
                                        <td
                                            class="py-3 text-muted-foreground"
                                        >
                                            {{ project.organization ?? '—' }}
                                        </td>
                                        <td class="py-3">
                                            <div
                                                class="flex items-center justify-end gap-3"
                                            >
                                                <div
                                                    class="hidden h-1.5 w-20 overflow-hidden rounded-full bg-slate-100 sm:block dark:bg-muted"
                                                >
                                                    <div
                                                        class="dashboard-margin-bar h-full rounded-full bg-emerald-500"
                                                        :style="{
                                                            width: `${Math.min(100, (Math.max(project.margin, 0) / maxMargin) * 100)}%`,
                                                            animationDelay: `${720 + index * 60}ms`,
                                                        }"
                                                    />
                                                </div>
                                                <span
                                                    class="font-semibold tabular-nums"
                                                    :class="
                                                        project.margin >= 0
                                                            ? 'text-emerald-700 dark:text-emerald-400'
                                                            : 'text-rose-600'
                                                    "
                                                >
                                                    {{
                                                        formatCurrencyValue(
                                                            project.margin,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card
                    class="soc-panel dashboard-reveal xl:col-span-5"
                    style="animation-delay: 560ms"
                >
                    <CardHeader class="soc-panel-header pb-4">
                        <p class="soc-panel-tag">PRJ-02</p>
                        <CardTitle class="mt-1 text-base font-bold uppercase tracking-wide">
                            {{ t('Workforce Split') }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('Employees') }} & {{ t('Contractors') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DonutChart
                            :labels="[t('Employees'), t('Contractors')]"
                            :data="[
                                charts.workforce.employees,
                                charts.workforce.contractors,
                            ]"
                            :colors="['#0a0a0a', '#dc2626']"
                        />
                        <div
                            class="mt-4 flex justify-center gap-6 text-sm"
                        >
                            <span class="flex items-center gap-2">
                                <span
                                    class="size-2.5 rounded-full bg-school-navy"
                                />
                                {{ t('Employees') }}
                                <strong class="tabular-nums">{{
                                    charts.workforce.employees
                                }}</strong>
                            </span>
                            <span class="flex items-center gap-2">
                                <span
                                    class="size-2.5 rounded-full bg-school-gold"
                                />
                                {{ t('Contractors') }}
                                <strong class="tabular-nums">{{
                                    charts.workforce.contractors
                                }}</strong>
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>

        <!-- CTA banner -->
        <section
            class="soc-footer-bar dashboard-reveal px-6 py-8 md:px-8"
            style="animation-delay: 580ms"
        >
            <div
                class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="soc-live mb-2">
                        <span class="soc-live-dot" />
                        {{ t('System Ready') }}
                    </div>
                    <h2 class="text-lg font-bold uppercase tracking-[0.1em]">
                        {{ t('Manage your security operations') }}
                    </h2>
                    <p class="mt-2 max-w-lg font-mono text-xs text-white/60">
                        {{
                            t(
                                'Track bidding, projects, finance, and HR from one centralized dashboard.',
                            )
                        }}
                    </p>
                </div>
                <Button
                    as-child
                    class="rounded-md bg-primary font-semibold uppercase tracking-wide text-white hover:bg-primary/90"
                >
                    <Link href="/organizations" class="gap-2">
                        <Building2 class="size-4" />
                        {{ t('View Organizations') }}
                    </Link>
                </Button>
            </div>
        </section>
    </div>
</template>
