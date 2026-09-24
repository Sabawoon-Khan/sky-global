<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import FileLink from '@/components/FileLink.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
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
import { Badge } from '@/components/ui/badge';
import { formatDate, formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import {
    Archive,
    Inbox,
    Plus,
    Send,
    Share2,
} from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

interface ArchivedDocument {
    id: number;
    reference_number?: string | null;
    title: string;
    direction?: string | null;
    document_date?: string | null;
    expires_at?: string | null;
    is_expired?: boolean;
    is_expiring_soon?: boolean;
    original_filename?: string | null;
    download_url?: string | null;
    document_category?: { id: number; name: string } | null;
    organization?: { id: number; name: string } | null;
    project?: { id: number; code: string; name: string } | null;
}

interface ChartPoint {
    key: string;
    label: string;
    value: number;
}

const props = defineProps<{
    documents: Paginated<ArchivedDocument>;
    stats: {
        total: number;
        incoming: number;
        outgoing: number;
        internal: number;
        archived?: number;
    };
    chart: {
        status: ChartPoint[];
        monthly: ChartPoint[];
    };
    filters?: {
        search?: string | null;
        direction?: string | null;
        document_category_id?: number | null;
        date_from?: string | null;
        date_to?: string | null;
    };
    documentCategories?: { id: number; name: string }[];
}>();

const onlyKeys = ['documents', 'stats', 'chart', 'filters', 'documentCategories'];

const { filters, pending, apply, clear } = useMisFilters(
    '/archive',
    {
        search: props.filters?.search ?? '',
        direction: props.filters?.direction ?? '',
        document_category_id: props.filters?.document_category_id
            ? String(props.filters.document_category_id)
            : '',
        date_from: props.filters?.date_from ?? '',
        date_to: props.filters?.date_to ?? '',
    },
    {
        search: '',
        direction: '',
        document_category_id: '',
        date_from: '',
        date_to: '',
    },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.documents.data, {
    accessors: {
        reference: (row) => row.reference_number,
        title: (row) => row.title,
        category: (row) => row.document_category?.name,
        linked: (row) => row.organization?.name || row.project?.code,
        date: (row) => row.document_date,
        expiry: (row) => row.expires_at,
        attachment: (row) => row.original_filename,
        direction: (row) => row.direction,
    },
});
const { t, viewAction, editAction, deleteAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Archive', href: '/archive' }],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'reference', label: t('Reference') },
    { key: 'title', label: t('Title') },
    { key: 'category', label: t('Category') },
    { key: 'linked', label: t('Linked To') },
    { key: 'date', label: t('Date') },
    { key: 'expiry', label: t('Expiry') },
    { key: 'attachment', label: t('Attachment') },
    { key: 'direction', label: t('Direction') },
    { key: 'actions', label: t('Actions'), locked: true },
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
    const incomingShare =
        total > 0 ? Math.round((props.stats.incoming / total) * 100) : 0;

    return {
        incomingShare,
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

const totalCount = computed(
    () => props.documents.meta?.total ?? props.documents.data.length,
);

const documentActions = (doc: ArchivedDocument): RowActionItem[] => [
    viewAction(`/archive/${doc.id}`),
    editAction(`/archive/${doc.id}`, 'archive.edit'),
    deleteAction(
        {
            href: `/archive/${doc.id}`,
            title: t('Delete document'),
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                { name: doc.title },
            ),
        },
        'archive.delete',
    ),
];
</script>

<template>
    <Head :title="t('Document Archive')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-operations.png">
            <template #eyebrow>{{ t('Records') }}</template>
            <template #title>{{ t('Document Archive') }}</template>
            <template #side>
                <Link
                    v-if="can('archive.create')"
                    href="/archive/create"
                    class="create-btn"
                >
                    <Plus />
                    {{ t('Register new document') }}
                </Link>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Direction') }}</template>
                        <template #meta
                            >{{ pipeline.incomingShare }}%
                            {{ t('Incoming') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{
                                    formatNumber(stats.incoming)
                                }}</strong>
                                <small>{{ t('Incoming') }}</small>
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
                        :title="t('Documents')"
                        :value="formatNumber(stats.total)"
                    >
                        <template #icon><Archive /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Incoming')"
                        :value="formatNumber(stats.incoming)"
                    >
                        <template #icon><Inbox /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Outgoing')"
                        :value="formatNumber(stats.outgoing)"
                    >
                        <template #icon><Send /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Internal')"
                        :value="formatNumber(stats.internal)"
                    >
                        <template #icon><Share2 /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="archive-documents"
            :columns="tableColumns"
            :pending="pending && documents.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search archive...')"
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
                        v-model="filters.direction"
                        :label="t('Direction')"
                        @change="(value) => apply({ direction: value })"
                    >
                        <option value="">{{ t('All') }}</option>
                        <option value="incoming">{{ t('Incoming') }}</option>
                        <option value="outgoing">{{ t('Outgoing') }}</option>
                        <option value="internal">{{ t('Internal') }}</option>
                    </V2SelectFilter>
                    <V2SelectFilter
                        v-model="filters.document_category_id"
                        :label="t('Category')"
                        @change="(value) => apply({ document_category_id: value })"
                    >
                        <option value="">{{ t('All categories') }}</option>
                        <option
                            v-for="category in documentCategories ?? []"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </option>
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
                            <SortableTh column="category">{{ t('Category') }}</SortableTh>
                            <SortableTh column="linked">{{ t('Linked To') }}</SortableTh>
                            <SortableTh column="date">{{ t('Date') }}</SortableTh>
                            <SortableTh column="expiry">{{ t('Expiry') }}</SortableTh>
                            <SortableTh column="attachment">{{ t('Attachment') }}</SortableTh>
                            <SortableTh column="direction">{{ t('Direction') }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(doc, index) in sortedRows"
                            :key="doc.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="documents.meta?.from"
                            />
                            <td class="muted nowrap">
                                {{ doc.reference_number ?? '—' }}
                            </td>
                            <td>{{ doc.title }}</td>
                            <td class="muted">
                                {{ doc.document_category?.name ?? '—' }}
                            </td>
                            <td class="muted">
                                <Link
                                    v-if="doc.project"
                                    :href="`/mis/projects/${doc.project.id}`"
                                    class="code-chip"
                                >
                                    {{ doc.project.code }}
                                </Link>
                                <span v-else-if="doc.organization">
                                    {{ doc.organization.name }}
                                </span>
                                <span v-else>—</span>
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(doc.document_date) }}
                            </td>
                            <td class="muted nowrap">
                                <div class="flex flex-col gap-1">
                                    <span>{{ formatDate(doc.expires_at) }}</span>
                                    <Badge
                                        v-if="doc.is_expired"
                                        variant="destructive"
                                        class="w-fit text-xs"
                                    >
                                        {{ t('Expired') }}
                                    </Badge>
                                    <Badge
                                        v-else-if="doc.is_expiring_soon"
                                        variant="outline"
                                        class="w-fit border-amber-500/50 text-xs text-amber-800 dark:text-amber-200"
                                    >
                                        {{ t('Expiring soon') }}
                                    </Badge>
                                </div>
                            </td>
                            <td>
                                <FileLink
                                    v-if="doc.download_url"
                                    :href="doc.download_url"
                                    :label="
                                        doc.original_filename ?? t('View file')
                                    "
                                    show-icon
                                    compact
                                />
                                <span v-else class="muted">—</span>
                            </td>
                            <td>{{ doc.direction ?? '—' }}</td>
                            <td class="end">
                                <RowActionsMenu
                                    :actions="documentActions(doc)"
                                />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!documents.data.length"
                            :colspan="visibleColCount"
                            :title="t('No documents in archive.')"
                        />
                    </tbody>
                </table>
            </template>

            <template v-if="documents.links?.length" #pager>
                <V2Pager :items="documents" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
