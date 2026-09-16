<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import FileLink from '@/components/FileLink.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import TrainingFieldSubnav from '@/components/mis/TrainingFieldSubnav.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import SortableTh from '@/components/SortableTh.vue';
import { Button } from '@/components/ui/button';
import {
    V2FilterBar,
    V2Hero,
    V2ListPage,
    V2Pager,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { formatDate, formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { ClipboardList, Plus } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

interface Report {
    id: number;
    reference_number: string;
    report_date?: string | null;
    description: string;
    trainer_name?: string | null;
    attachment_url?: string | null;
    original_filename?: string | null;
    guards_count?: number;
}

const props = defineProps<{
    reports: Paginated<Report>;
    stats: { roster: number; reports: number };
    filters?: {
        search?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
}>();

const onlyKeys = ['reports', 'stats', 'filters'];

const { filters, pending, apply, clear } = useMisFilters(
    '/training/field/reports',
    {
        search: props.filters?.search ?? '',
        date_from: props.filters?.date_from ?? '',
        date_to: props.filters?.date_to ?? '',
    },
    { search: '', date_from: '', date_to: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.reports.data, {
    accessors: {
        reference: (row) => row.reference_number,
        date: (row) => row.report_date,
        trainer: (row) => row.trainer_name,
        guards: (row) => row.guards_count,
    },
});

const { t, viewAction, deleteAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Training', href: '/training/guards' },
            { title: 'On-site training', href: '/training/field/reports' },
        ],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'reference', label: t('Reference') },
    { key: 'date', label: t('Report date') },
    { key: 'trainer', label: t('Trainer') },
    { key: 'guards', label: t('Guards') },
    { key: 'attachment', label: t('Attachment') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const rowActions = (report: Report): RowActionItem[] => [
    viewAction(`/training/field/reports/${report.id}`),
    deleteAction(
        {
            href: `/training/field/reports/${report.id}`,
            title: t('Delete report'),
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                { name: report.reference_number },
            ),
        },
        'training.delete',
    ),
];
</script>

<template>
    <Head :title="t('On-site training')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('Education and Training') }}</template>
            <template #title>{{ t('On-site training') }}</template>
            <template #description>
                {{ t('Trainer visit reports for guards already on duty.') }}
            </template>
            <template #actions>
                <Button v-if="can('training.create')" as-child>
                    <Link href="/training/field/reports/create">
                        <Plus class="me-2 size-4" />
                        {{ t('New visit report') }}
                    </Link>
                </Button>
            </template>
        </V2Hero>

        <TrainingFieldSubnav />

        <V2StatGrid>
            <V2StatCard
                :title="t('Visit reports')"
                :value="formatNumber(stats.reports)"
                :icon="ClipboardList"
            />
            <V2StatCard
                :title="t('Existing guards')"
                :value="formatNumber(stats.roster)"
            />
        </V2StatGrid>

        <V2TablePanel
            table-id="training-field-reports"
            :columns="tableColumns"
            :pending="pending && reports.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search reports...')"
                            @submit="apply()"
                            @clear="clear"
                        />
                    </div>
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
                            <SortableTh column="reference">{{
                                t('Reference')
                            }}</SortableTh>
                            <SortableTh column="date">{{
                                t('Report date')
                            }}</SortableTh>
                            <SortableTh column="trainer">{{
                                t('Trainer')
                            }}</SortableTh>
                            <SortableTh column="guards">{{
                                t('Guards')
                            }}</SortableTh>
                            <th>{{ t('Attachment') }}</th>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(report, index) in sortedRows"
                            :key="report.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="reports.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/training/field/reports/${report.id}`"
                                    class="code-chip"
                                >
                                    {{ report.reference_number }}
                                </Link>
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(report.report_date) }}
                            </td>
                            <td class="muted">
                                {{ report.trainer_name ?? '—' }}
                            </td>
                            <td>{{ formatNumber(report.guards_count ?? 0) }}</td>
                            <td>
                                <FileLink
                                    v-if="report.attachment_url"
                                    :href="report.attachment_url"
                                    :label="
                                        report.original_filename ??
                                        t('Attachment')
                                    "
                                    show-icon
                                    compact
                                />
                                <span v-else class="muted">—</span>
                            </td>
                            <td class="end">
                                <RowActionsMenu :actions="rowActions(report)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!reports.data.length"
                            :colspan="visibleColCount"
                            :title="t('No visit reports yet.')"
                        >
                            <template #actions>
                                <Link
                                    v-if="can('training.create')"
                                    href="/training/field/reports/create"
                                    class="create-btn"
                                >
                                    <Plus />
                                    {{ t('New visit report') }}
                                </Link>
                            </template>
                        </EmptyState>
                    </tbody>
                </table>
            </template>

            <template v-if="reports.links?.length" #pager>
                <V2Pager :items="reports" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
