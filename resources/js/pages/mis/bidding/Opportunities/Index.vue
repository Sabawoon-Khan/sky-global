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
    Briefcase,
    DoorClosed,
    DoorOpen,
    Plus,
    Trophy,
} from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

interface Organization {
    id: number;
    name: string;
}

interface Opportunity {
    id: number;
    reference_number: string | null;
    title: string;
    status: string;
    submission_deadline: string | null;
    estimated_value: number | null;
    currency: string | null;
    location: string | null;
    security_scope: string | null;
    organization: Organization | null;
}

interface ChartPoint {
    key: string;
    label: string;
    value: number;
}

const props = defineProps<{
    opportunities: Paginated<Opportunity>;
    stats: {
        total: number;
        open: number;
        closed: number;
        awarded: number;
        cancelled?: number;
    };
    chart: {
        status: ChartPoint[];
        monthly: ChartPoint[];
    };
    filters?: {
        search?: string | null;
        status?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
}>();

const onlyKeys = ['opportunities', 'stats', 'chart', 'filters'];

const { filters, pending, apply } = useMisFilters(
    '/bidding/opportunities',
    {
        search: props.filters?.search ?? '',
        status: props.filters?.status ?? '',
        date_from: props.filters?.date_from ?? '',
        date_to: props.filters?.date_to ?? '',
    },
    { search: '', status: '', date_from: '', date_to: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.opportunities.data, {
    accessors: {
        reference: (row) => row.reference_number,
        title: (row) => row.title,
        organization: (row) => row.organization?.name,
        scope: (row) => row.security_scope || row.location,
        deadline: (row) => row.submission_deadline,
        value: (row) => row.estimated_value,
        status: (row) => row.status,
    },
});
const { t, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Opportunities', href: '/bidding/opportunities' },
        ],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'reference', label: t('Reference') },
    { key: 'title', label: t('Title') },
    { key: 'organization', label: t('Organization') },
    { key: 'scope', label: t('Scope') },
    { key: 'deadline', label: t('Deadline') },
    { key: 'value', label: t('Value') },
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
    const openShare =
        total > 0 ? Math.round((props.stats.open / total) * 100) : 0;

    return {
        openShare,
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

function onStatusChange(value: string) {
    apply({ status: value });
}
</script>

<template>
    <Head :title="t('Opportunities')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-operations.png">
            <template #eyebrow>{{ t('Bidding') }}</template>
            <template #title>{{ t('Opportunities') }}</template>
            <template #side>
                <Link
                    v-if="can('bidding.create')"
                    href="/bidding/opportunities/create"
                    class="create-btn"
                >
                    <Plus />
                    {{ t('New opportunity') }}
                </Link>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Pipeline') }}</template>
                        <template #meta
                            >{{ pipeline.openShare }}%
                            {{ t('Open') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{
                                    formatNumber(stats.open)
                                }}</strong>
                                <small>{{ t('Open') }}</small>
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
                        :title="t('Opportunities')"
                        :value="formatNumber(stats.total)"
                    >
                        <template #icon><Briefcase /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Open')"
                        :value="formatNumber(stats.open)"
                    >
                        <template #icon><DoorOpen /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Closed')"
                        :value="formatNumber(stats.closed)"
                    >
                        <template #icon><DoorClosed /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Awarded')"
                        :value="formatNumber(stats.awarded)"
                    >
                        <template #icon><Trophy /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="bidding-opportunities"
            :columns="tableColumns"
            :pending="pending && opportunities.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search title or reference...')"
                            @submit="apply()"
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
                        @change="onStatusChange"
                    >
                        <option value="">{{ t('All statuses') }}</option>
                        <option value="open">{{ t('Open') }}</option>
                        <option value="closed">{{ t('Closed') }}</option>
                        <option value="awarded">{{ t('Awarded') }}</option>
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
                            <SortableTh column="reference">{{ t('Reference') }}</SortableTh>
                            <SortableTh column="title">{{ t('Title') }}</SortableTh>
                            <SortableTh column="organization">{{ t('Organization') }}</SortableTh>
                            <SortableTh column="scope">{{ t('Scope') }}</SortableTh>
                            <SortableTh column="deadline">{{ t('Deadline') }}</SortableTh>
                            <SortableTh column="value">{{ t('Value') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(opportunity, index) in sortedRows"
                            :key="opportunity.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="opportunities.meta?.from"
                            />
                            <td class="muted nowrap">
                                {{ opportunity.reference_number ?? '—' }}
                            </td>
                            <td>
                                <Link
                                    :href="`/bidding/opportunities/${opportunity.id}`"
                                    class="code-chip"
                                >
                                    {{ opportunity.title }}
                                </Link>
                                <div
                                    v-if="opportunity.location"
                                    class="muted text-xs"
                                >
                                    {{ opportunity.location }}
                                </div>
                            </td>
                            <td class="muted">
                                {{ opportunity.organization?.name ?? '—' }}
                            </td>
                            <td class="muted">
                                {{ opportunity.security_scope ?? '—' }}
                            </td>
                            <td class="muted nowrap">
                                {{
                                    formatDate(opportunity.submission_deadline)
                                }}
                            </td>
                            <td class="nums">
                                {{
                                    formatCurrency(
                                        opportunity.estimated_value,
                                        opportunity.currency,
                                    )
                                }}
                            </td>
                            <td>
                                <StatusBadge :status="opportunity.status" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!opportunities.data.length"
                            :colspan="visibleColCount"
                            :title="t('No opportunities yet')"
                        >
                            <template #actions>
                                <Link
                                    v-if="can('bidding.create')"
                                    href="/bidding/opportunities/create"
                                    class="create-btn"
                                >
                                    <Plus />
                                    {{ t('Add first opportunity') }}
                                </Link>
                            </template>
                        </EmptyState>
                    </tbody>
                </table>
            </template>

            <template v-if="opportunities.links?.length" #pager>
                <V2Pager :items="opportunities" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
