<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Briefcase,
    FileText,
    Target,
    TrendingUp,
} from '@lucide/vue';
import AnalyticsSubnav from '@/components/mis/AnalyticsSubnav.vue';
import BarChart from '@/components/charts/BarChart.vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import LineChart from '@/components/charts/LineChart.vue';
import { Badge } from '@/components/ui/badge';
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
import { formatCurrency } from '@/lib/format';
import { translateBidStatus, translateProjectStatus } from '@/lib/status-labels';

interface BidAnalytic {
    id: number;
    bid_number?: string | null;
    status: string;
    our_total_amount?: number | null;
    winning_amount?: number | null;
    currency?: string | null;
    organization?: string | null;
    submitted_at?: string | null;
}

interface BiddingStats {
    open_opportunities?: number;
    pending_bids?: number;
    win_rate?: number;
    won?: number;
    lost?: number;
}

interface OrganizationTypeStat {
    id: number;
    name: string;
    color: string | null;
    organizations_count: number;
    projects_count: number;
    total_contract_value: number;
}

interface Props {
    stats?: BiddingStats;
    organizationTypes?: OrganizationTypeStat[];
    competitorIntel?: number;
    bids: BidAnalytic[];
    charts?: {
        bidding_outcomes: Array<{ key: string; value: number }>;
        project_statuses: Array<{ status: string; count: number }>;
        monthly_bids: Array<{
            label: string;
            submitted: number;
            won: number;
            lost: number;
            win_rate: number;
        }>;
        organization_types: Array<{
            name: string;
            projects_count: number;
            total_contract_value: number;
        }>;
        bid_statuses: Array<{ status: string; count: number }>;
    };
}

const props = defineProps<Props>();
const { t, can } = useMisPage();

const { sortedRows } = provideTableSort(() => props.bids, {
    accessors: {
        bid: (row) => row.bid_number ?? row.id,
        organization: (row) => row.organization,
        submitted: (row) => row.submitted_at,
        amount: (row) => row.our_total_amount,
        winning: (row) => row.winning_amount,
        status: (row) => row.status,
    },
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Analytics', href: '/analytics/bidding' },
            { title: 'Bidding', href: '/analytics/bidding' },
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

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'bid', label: t('Bid') },
    { key: 'organization', label: t('Organization') },
    { key: 'submitted', label: t('Submitted') },
    { key: 'amount', label: t('Our Amount') },
    { key: 'winning', label: t('Winning') },
    { key: 'status', label: t('Status') },
]);

const formatDate = (value?: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );
};

const statusLabel = (status: string) => translateBidStatus(t, status);

const outcomeLabel = (key: string): string => {
    if (key === 'won') return t('won');
    if (key === 'lost') return t('lost');
    if (key === 'pending') return t('Pending');
    return key;
};
</script>

<template>
    <Head :title="t('Bidding Analytics')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-operations.png" priority>
            <template #eyebrow>{{ t('Analytics') }}</template>
            <template #title>{{ t('Bidding Analytics') }}</template>
            <template #side>
                <Link
                    v-if="can('projects.create')"
                    href="/mis/projects/create"
                    class="create-btn"
                >
                    {{ t('New Project') }}
                </Link>
            </template>
            <template v-if="stats" #stats>
                <V2StatGrid>
                    <V2StatCard
                        :title="t('Open Opportunities')"
                        :value="stats.open_opportunities ?? 0"
                    >
                        <template #icon><Briefcase /></template>
                    </V2StatCard>
                    <V2StatCard
                        icon-tone="teal"
                        :title="t('Win Rate')"
                        :value="`${stats.win_rate ?? 0}%`"
                    >
                        <template #icon><TrendingUp /></template>
                    </V2StatCard>
                    <V2StatCard
                        icon-tone="warm"
                        :title="t('Won / Lost')"
                        :value="`${stats.won ?? 0} / ${stats.lost ?? 0}`"
                    >
                        <template #icon><Target /></template>
                    </V2StatCard>
                    <V2StatCard
                        accent
                        icon-tone="orange"
                        :title="t('Pending Bids')"
                        :value="stats.pending_bids ?? 0"
                    >
                        <template #icon><FileText /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <AnalyticsSubnav active="bidding" />

        <div v-if="charts" class="grid gap-4 lg:grid-cols-5">
            <V2Panel
                class="lg:col-span-2"
                :title="t('Bidding Outcomes')"
            >
                <DonutChart
                    :labels="charts.bidding_outcomes.map((b) => outcomeLabel(b.key))"
                    :data="charts.bidding_outcomes.map((b) => b.value)"
                    :colors="donutColors"
                    :center-label="t('Bids')"
                />
            </V2Panel>

            <V2Panel
                class="lg:col-span-3"
                :title="t('Monthly Bid Activity')"
            >
                <LineChart
                    :labels="charts.monthly_bids.map((m) => m.label)"
                    :datasets="[
                        {
                            label: t('Submitted'),
                            data: charts.monthly_bids.map((m) => m.submitted),
                            borderColor: colors.teal,
                            backgroundColor: 'rgba(31, 78, 95, 0.12)',
                        },
                        {
                            label: t('won'),
                            data: charts.monthly_bids.map((m) => m.won),
                            borderColor: colors.brass,
                            backgroundColor: 'rgba(184, 149, 108, 0.14)',
                        },
                        {
                            label: t('lost'),
                            data: charts.monthly_bids.map((m) => m.lost),
                            borderColor: colors.danger,
                            backgroundColor: 'rgba(143, 45, 58, 0.1)',
                        },
                    ]"
                />
            </V2Panel>
        </div>

        <div v-if="charts" class="grid gap-4 lg:grid-cols-2">
            <V2Panel :title="t('Project Status Breakdown')">
                <BarChart
                    :labels="
                        charts.project_statuses.map((s) =>
                            translateProjectStatus(t, s.status),
                        )
                    "
                    :datasets="[
                        {
                            label: t('Projects'),
                            data: charts.project_statuses.map((s) => s.count),
                            backgroundColor: colors.teal,
                        },
                    ]"
                    :show-legend="false"
                />
            </V2Panel>

            <V2Panel :title="t('Win Rate Trend')">
                <LineChart
                    :labels="charts.monthly_bids.map((m) => m.label)"
                    :datasets="[
                        {
                            label: t('Win Rate'),
                            data: charts.monthly_bids.map((m) => m.win_rate),
                            borderColor: colors.brass,
                            backgroundColor: 'rgba(184, 149, 108, 0.14)',
                        },
                    ]"
                />
            </V2Panel>
        </div>

        <div
            v-if="charts?.organization_types?.length"
            class="grid gap-4 lg:grid-cols-5"
        >
            <V2Panel
                class="lg:col-span-3"
                :title="t('Contract Value by Org Type')"
            >
                <BarChart
                    :labels="charts.organization_types.map((o) => o.name)"
                    :datasets="[
                        {
                            label: t('Contract Value'),
                            data: charts.organization_types.map(
                                (o) => o.total_contract_value,
                            ),
                            backgroundColor: colors.navy,
                        },
                    ]"
                    :show-legend="false"
                />
            </V2Panel>
            <V2Panel
                class="lg:col-span-2"
                :title="t('Projects by Org Type')"
            >
                <DonutChart
                    :labels="charts.organization_types.map((o) => o.name)"
                    :data="
                        charts.organization_types.map((o) => o.projects_count)
                    "
                    :colors="donutColors"
                />
            </V2Panel>
        </div>

        <div
            v-if="organizationTypes?.length || competitorIntel !== undefined"
            class="grid gap-4 lg:grid-cols-2"
        >
            <V2Panel
                v-if="organizationTypes?.length"
                :title="t('By Organization Type')"
                :padded="false"
            >
                <div class="divide-y">
                    <div
                        v-for="type in organizationTypes"
                        :key="type.id"
                        class="flex items-center justify-between gap-3 px-5 py-3 text-sm"
                    >
                        <div class="min-w-0">
                            <p class="font-medium">{{ type.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ type.organizations_count }}
                                {{ t('organizations') }} ·
                                {{ type.projects_count }} {{ t('projects') }}
                            </p>
                        </div>
                        <span class="shrink-0 font-semibold tabular-nums">
                            {{ formatCurrency(type.total_contract_value) }}
                        </span>
                    </div>
                </div>
            </V2Panel>

            <V2Panel v-if="competitorIntel !== undefined" :title="t('Competitor Intel')">
                <p class="text-3xl font-semibold tracking-tight tabular-nums">
                    {{ competitorIntel ?? 0 }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ t('Competitor records across projects') }}
                </p>
                <Link
                    v-if="can('bidding.view_competitors')"
                    href="/mis/projects"
                    class="mt-4 inline-flex text-sm font-medium text-primary hover:underline"
                >
                    {{ t('Review on projects') }} →
                </Link>
            </V2Panel>
        </div>

        <V2TablePanel
            table-id="analytics-recent-bids"
            :columns="tableColumns"
            :delay="false"
        >
            <template #filters>
                <div class="table-top">
                    <div>
                        <h2>{{ t('Recent Bids') }}</h2>
                        <p>{{ t('Latest bid submissions and outcomes') }}</p>
                    </div>
                </div>
            </template>

            <table>
                <thead>
                    <tr>
                        <SortableTh column="bid">{{ t('Bid') }}</SortableTh>
                        <SortableTh column="organization">{{ t('Organization') }}</SortableTh>
                        <SortableTh column="submitted">{{ t('Submitted') }}</SortableTh>
                        <SortableTh column="amount" align="end" class="end">{{ t('Our Amount') }}</SortableTh>
                        <SortableTh column="winning" align="end" class="end">{{ t('Winning') }}</SortableTh>
                        <SortableTh column="status">{{ t('Status') }}</SortableTh>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!bids.length">
                        <td colspan="6" class="py-10 text-center text-muted-foreground">
                            {{ t('No bid data available.') }}
                        </td>
                    </tr>
                    <tr v-for="bid in sortedRows" :key="bid.id">
                        <td>
                            <Link
                                v-if="can('projects.view')"
                                href="/mis/projects"
                                class="font-medium hover:text-primary hover:underline"
                            >
                                {{ bid.bid_number ?? `#${bid.id}` }}
                            </Link>
                            <span v-else class="font-medium">
                                {{ bid.bid_number ?? `#${bid.id}` }}
                            </span>
                        </td>
                        <td>{{ bid.organization ?? '—' }}</td>
                        <td>{{ formatDate(bid.submitted_at) }}</td>
                        <td class="end tabular-nums">
                            {{
                                formatCurrency(
                                    bid.our_total_amount,
                                    bid.currency ?? 'AFN',
                                )
                            }}
                        </td>
                        <td class="end tabular-nums">
                            {{
                                formatCurrency(
                                    bid.winning_amount,
                                    bid.currency ?? 'AFN',
                                )
                            }}
                        </td>
                        <td>
                            <Badge variant="outline">
                                {{ statusLabel(bid.status) }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </V2TablePanel>
    </V2ListPage>
</template>
