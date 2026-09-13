<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import SortableTh from '@/components/SortableTh.vue';
import {
    V2FilterBar,
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2Pager,
    V2SelectFilter,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import {
    formatCurrency,
    formatDate,
    formatNumber,
    type Paginated,
} from '@/lib/format';
import {
    FileText,
    Plus,
    Send,
    Trophy,
    XCircle,
} from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

interface OpportunitySummary {
    id: number;
    title: string;
    organization?: { name: string } | null;
}

interface Bid {
    id: number;
    bid_number?: string | null;
    status: string;
    submitted_at?: string | null;
    our_total_amount?: number | null;
    winning_amount?: number | null;
    currency?: string | null;
    procurement_opportunity?: OpportunitySummary | null;
}

interface ChartPoint {
    key: string;
    label: string;
    value: number;
}

const props = defineProps<{
    bids: Paginated<Bid>;
    stats: {
        total: number;
        draft?: number;
        submitted: number;
        won: number;
        lost: number;
        cancelled?: number;
        by_status?: Record<string, number>;
    };
    chart: {
        status: ChartPoint[];
        monthly: ChartPoint[];
    };
    filters?: {
        search?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
}>();

const onlyKeys = ['bids', 'stats', 'chart', 'filters'];

const { filters, pending, apply, clear } = useMisFilters(
    '/bidding/bids',
    {
        search: props.filters?.search ?? '',
        status: props.filters?.status ?? '',
        date_from: props.filters?.date_from ?? '',
        date_to: props.filters?.date_to ?? '',
    },
    { search: '', status: '', date_from: '', date_to: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.bids.data, {
    accessors: {
        bid_number: (row) => row.bid_number ?? row.id,
        opportunity: (row) => row.procurement_opportunity?.title,
        submitted: (row) => row.submitted_at,
        amount: (row) => row.our_total_amount,
        status: (row) => row.status,
    },
});
const { t, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Bidding', href: '/bidding/opportunities' },
            { title: 'Bids', href: '/bidding/bids' },
        ],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'bid_number', label: t('Bid #') },
    { key: 'opportunity', label: t('Opportunity') },
    { key: 'submitted', label: t('Submitted') },
    { key: 'amount', label: t('Our Amount') },
    { key: 'status', label: t('Status') },
]);

const statusPalette = [
    'var(--school-navy)',
    'var(--brand-accent)',
    'var(--school-gold)',
    'var(--muted-foreground)',
    '#3d5a80',
    '#8b9bb4',
];

const pipeline = computed(() => {
    const rows = (props.chart?.status ?? []).filter((row) => row.value > 0);
    const total = Math.max(
        rows.reduce((sum, row) => sum + row.value, 0),
        props.stats.total,
        1,
    );
    const submittedShare =
        total > 0 ? Math.round((props.stats.submitted / total) * 100) : 0;

    return {
        submittedShare,
        segments: rows.map((row, index) => ({
            key: row.key,
            label: row.label,
            value: row.value,
            color: statusPalette[index % statusPalette.length],
            width: Math.max(row.value > 0 ? 6 : 0, (row.value / total) * 100),
        })),
    };
});

const monthlyBars = computed(() => {
    const rows =
        props.chart?.monthly?.length > 0
            ? props.chart.monthly
            : Array.from({ length: 6 }, (_, index) => ({
                  key: `m-${index}`,
                  label: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'][index],
                  value: 0,
              }));
    const max = Math.max(...rows.map((row) => Number(row.value) || 0), 1);

    return rows.map((row) => {
        const value = Number(row.value) || 0;
        return {
            key: row.key,
            label: row.label,
            value,
            height: Math.max(value > 0 ? 6 : 3, Math.round((value / max) * 44)),
            peak: value === max && value > 0,
        };
    });
});
</script>

<template>
    <Head :title="t('Bids')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-operations.png">
            <template #eyebrow>{{ t('Bidding') }}</template>
            <template #title>{{ t('Bids') }}</template>
            <template #side>
                <Link
                    v-if="can('bidding.create')"
                    href="/bidding/bids/create"
                    class="create-btn"
                >
                    <Plus />
                    {{ t('New Bid') }}
                </Link>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Pipeline') }}</template>
                        <template #meta
                            >{{ pipeline.submittedShare }}%
                            {{ t('Submitted') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{
                                    formatNumber(stats.submitted)
                                }}</strong>
                                <small>{{ t('Submitted') }}</small>
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
                                <b>{{ formatNumber(seg.value) }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Created by month') }}</template>
                        <template #meta
                            >{{ formatNumber(stats.total) }}
                            {{ t('Total') }}</template
                        >

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
                        :title="t('Bids')"
                        :value="formatNumber(stats.total)"
                    >
                        <template #icon><FileText /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Submitted')"
                        :value="formatNumber(stats.submitted)"
                    >
                        <template #icon><Send /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Won')"
                        :value="formatNumber(stats.won)"
                    >
                        <template #icon><Trophy /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Lost')"
                        :value="formatNumber(stats.lost)"
                    >
                        <template #icon><XCircle /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="bidding-bids"
            :columns="tableColumns"
            :pending="pending && bids.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search bids...')"
                            @submit="apply()"
                            @clear="clear"
                        />
                    </div>
                    <label class="filter-select">
                        <span>{{ t('From') }}</span>
                        <input
                            v-model="filters.date_from"
                            type="date"
                            @change="apply()"
                        />
                    </label>
                    <label class="filter-select">
                        <span>{{ t('To') }}</span>
                        <input
                            v-model="filters.date_to"
                            type="date"
                            @change="apply()"
                        />
                    </label>
                    <V2SelectFilter
                        v-model="filters.status"
                        :label="t('Status')"
                        @change="(value) => apply({ status: value })"
                    >
                        <option value="">{{ t('All statuses') }}</option>
                        <option value="draft">{{ t('Draft') }}</option>
                        <option value="submitted">{{ t('Submitted') }}</option>
                        <option value="under_review">{{ t('Under review') }}</option>
                        <option value="won">{{ t('Won') }}</option>
                        <option value="lost">{{ t('Lost') }}</option>
                        <option value="cancelled">{{ t('Cancelled') }}</option>
                    </V2SelectFilter>
                    <template #columns>
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>

            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="bid_number">{{ t('Bid #') }}</SortableTh>
                            <SortableTh column="opportunity">{{ t('Opportunity') }}</SortableTh>
                            <SortableTh column="submitted">{{ t('Submitted') }}</SortableTh>
                            <SortableTh column="amount">{{ t('Our Amount') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(bid, index) in sortedRows"
                            :key="bid.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="bids.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/bidding/bids/${bid.id}`"
                                    class="code-chip"
                                >
                                    {{ bid.bid_number ?? `#${bid.id}` }}
                                </Link>
                            </td>
                            <td>
                                <div>
                                    {{
                                        bid.procurement_opportunity?.title ??
                                        '—'
                                    }}
                                </div>
                                <div
                                    v-if="
                                        bid.procurement_opportunity?.organization
                                            ?.name
                                    "
                                    class="muted text-xs"
                                >
                                    {{
                                        bid.procurement_opportunity.organization
                                            .name
                                    }}
                                </div>
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(bid.submitted_at) }}
                            </td>
                            <td class="nums">
                                {{
                                    formatCurrency(
                                        bid.our_total_amount,
                                        bid.currency ?? 'AFN',
                                    )
                                }}
                            </td>
                            <td>
                                <StatusBadge :status="bid.status" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!bids.data.length"
                            :colspan="visibleColCount"
                            :title="t('No bids found.')"
                        />
                    </tbody>
                </table>
            </template>

            <template v-if="bids.links?.length" #pager>
                <V2Pager :items="bids" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
