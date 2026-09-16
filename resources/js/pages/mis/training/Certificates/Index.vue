<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import SortableTh from '@/components/SortableTh.vue';
import {
    V2FilterBar,
    V2Hero,
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
import { formatDate, formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { Award, Printer } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

interface TrainingGuard {
    id: number;
    name: string;
    father_name: string;
    batch_number: string;
    status: string;
    training_path?: string | null;
    certificate_number?: string | null;
    certificate_issued_at?: string | null;
}

const props = defineProps<{
    guards: Paginated<TrainingGuard>;
    stats: {
        certified: number;
        ready: number;
        completed: number;
    };
    filters?: {
        search?: string | null;
        status?: string | null;
        batch_number?: string | null;
    };
}>();

const onlyKeys = ['guards', 'stats', 'filters'];

const { filters, pending, apply, clear } = useMisFilters(
    '/training/certificates',
    {
        search: props.filters?.search ?? '',
        status: props.filters?.status ?? '',
        batch_number: props.filters?.batch_number ?? '',
    },
    { search: '', status: '', batch_number: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.guards.data, {
    accessors: {
        name: (row) => row.name,
        batch: (row) => row.batch_number,
        number: (row) => row.certificate_number,
        issued: (row) => row.certificate_issued_at,
        status: (row) => row.status,
    },
});
const { t, viewAction } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Training', href: '/training/guards' },
            { title: 'Certificates', href: '/training/certificates' },
        ],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Name') },
    { key: 'batch', label: t('Batch number') },
    { key: 'number', label: t('Certificate number') },
    { key: 'issued', label: t('Issue date') },
    { key: 'status', label: t('Status') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const pathLabel = (path?: string | null): string => {
    if (path === 'ministry') {
        return t('Public Protection Deputy');
    }
    if (path === 'company') {
        return t('Company training');
    }
    return '—';
};

const guardActions = (guard: TrainingGuard): RowActionItem[] => {
    const actions: RowActionItem[] = [viewAction(`/training/guards/${guard.id}`)];

    if (guard.status === 'certified') {
        actions.push({
            label: t('Print certificate'),
            icon: Printer,
            href: `/training/guards/${guard.id}/certificate/print`,
        });
    }

    return actions;
};
</script>

<template>
    <Head :title="t('Certificates')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('Training') }}</template>
            <template #title>{{ t('Certificates') }}</template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Certified')"
                        :value="formatNumber(stats.certified)"
                    >
                        <template #icon><Award /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Ready for certificate')"
                        :value="formatNumber(stats.ready)"
                    >
                        <template #icon><Award /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        accent
                        icon-tone="orange"
                        :title="t('Completed')"
                        :value="formatNumber(stats.completed)"
                    >
                        <template #icon><Award /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="training-certificates"
            :columns="tableColumns"
            :pending="pending && guards.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search certificates...')"
                            @submit="apply()"
                            @clear="clear"
                        />
                    </div>
                    <V2SelectFilter
                        v-model="filters.status"
                        :label="t('Status')"
                        @change="(value) => apply({ status: value })"
                    >
                        <option value="">{{ t('All statuses') }}</option>
                        <option value="completed">{{ t('Completed') }}</option>
                        <option value="certified">{{ t('Certified') }}</option>
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
                            <SortableTh column="name">{{ t('Name') }}</SortableTh>
                            <SortableTh column="batch">{{ t('Batch number') }}</SortableTh>
                            <SortableTh column="number">{{
                                t('Certificate number')
                            }}</SortableTh>
                            <SortableTh column="issued">{{ t('Issue date') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(guard, index) in sortedRows"
                            :key="guard.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="guards.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/training/guards/${guard.id}`"
                                    class="code-chip"
                                >
                                    {{ guard.name }}
                                </Link>
                                <div class="text-xs text-muted-foreground">
                                    {{ pathLabel(guard.training_path) }}
                                </div>
                            </td>
                            <td class="muted">{{ guard.batch_number }}</td>
                            <td class="muted">
                                {{ guard.certificate_number ?? '—' }}
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(guard.certificate_issued_at) }}
                            </td>
                            <td>
                                <StatusBadge :status="guard.status" />
                            </td>
                            <td class="end">
                                <RowActionsMenu :actions="guardActions(guard)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!guards.data.length"
                            :colspan="visibleColCount"
                            :title="t('No certificates issued yet.')"
                        />
                    </tbody>
                </table>
            </template>

            <template v-if="guards.links?.length" #pager>
                <V2Pager :items="guards" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
