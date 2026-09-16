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
import { formatAfn, formatDate, formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { Plus, Shield } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

interface Payment {
    id: number;
    reference_number: string;
    batch_number?: string | null;
    payment_date?: string | null;
    period_start?: string | null;
    period_end?: string | null;
    total_amount: number | string;
    receipt_url?: string | null;
    original_filename?: string | null;
    guards_count?: number;
}

interface ChartPoint {
    key: string;
    label: string;
    value: number;
}

const props = defineProps<{
    payments: Paginated<Payment>;
    stats: {
        total: number;
        amount: number;
        guards: number;
    };
    chart?: { monthly?: ChartPoint[] };
    filters?: {
        search?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
}>();

const onlyKeys = ['payments', 'stats', 'chart', 'filters'];

const { filters, pending, apply, clear } = useMisFilters(
    '/training/payments',
    {
        search: props.filters?.search ?? '',
        date_from: props.filters?.date_from ?? '',
        date_to: props.filters?.date_to ?? '',
    },
    { search: '', date_from: '', date_to: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.payments.data, {
    accessors: {
        reference: (row) => row.reference_number,
        batch: (row) => row.batch_number,
        date: (row) => row.payment_date,
        period: (row) => row.period_start,
        guards: (row) => row.guards_count,
        amount: (row) => Number(row.total_amount),
    },
});
const { t, viewAction, deleteAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Training', href: '/training/guards' },
            { title: 'Public Protection Deputy', href: '/training/payments' },
        ],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'reference', label: t('Reference') },
    { key: 'batch', label: t('Batch number') },
    { key: 'date', label: t('Payment date') },
    { key: 'period', label: t('Period') },
    { key: 'guards', label: t('Guards') },
    { key: 'amount', label: t('Total payment') },
    { key: 'receipt', label: t('Receipt') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const paymentActions = (payment: Payment): RowActionItem[] => [
    viewAction(`/training/payments/${payment.id}`),
    deleteAction(
        {
            href: `/training/payments/${payment.id}`,
            title: t('Delete payment'),
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                { name: payment.reference_number },
            ),
        },
        'training.delete',
    ),
];
</script>

<template>
    <Head :title="t('Public Protection Deputy')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('Training') }}</template>
            <template #title>{{ t('Public Protection Deputy') }}</template>
            <template #side>
                <Link
                    v-if="can('training.create')"
                    href="/training/payments/create"
                    class="create-btn"
                >
                    <Plus />
                    {{ t('Record ministry payment') }}
                </Link>
            </template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Payments')"
                        :value="formatNumber(stats.total)"
                    >
                        <template #icon><Shield /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Guards')"
                        :value="formatNumber(stats.guards)"
                    >
                        <template #icon><Shield /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        accent
                        icon-tone="orange"
                        :title="t('Total payment')"
                        :value="formatAfn(stats.amount)"
                    >
                        <template #icon><Shield /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="training-payments"
            :columns="tableColumns"
            :pending="pending && payments.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search payments...')"
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
                            <SortableTh column="batch">{{ t('Batch number') }}</SortableTh>
                            <SortableTh column="date">{{ t('Payment date') }}</SortableTh>
                            <SortableTh column="period">{{ t('Period') }}</SortableTh>
                            <SortableTh column="guards">{{ t('Guards') }}</SortableTh>
                            <SortableTh column="amount">{{ t('Total payment') }}</SortableTh>
                            <th>{{ t('Receipt') }}</th>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(payment, index) in sortedRows"
                            :key="payment.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="payments.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/training/payments/${payment.id}`"
                                    class="code-chip"
                                >
                                    {{ payment.reference_number }}
                                </Link>
                            </td>
                            <td class="muted nowrap">
                                {{ payment.batch_number ?? '—' }}
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(payment.payment_date) }}
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(payment.period_start) }}
                                –
                                {{ formatDate(payment.period_end) }}
                            </td>
                            <td>{{ formatNumber(payment.guards_count ?? 0) }}</td>
                            <td>{{ formatAfn(Number(payment.total_amount)) }}</td>
                            <td>
                                <FileLink
                                    v-if="payment.receipt_url"
                                    :href="payment.receipt_url"
                                    :label="payment.original_filename ?? t('Receipt')"
                                    show-icon
                                    compact
                                />
                                <span v-else class="muted">—</span>
                            </td>
                            <td class="end">
                                <RowActionsMenu :actions="paymentActions(payment)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!payments.data.length"
                            :colspan="visibleColCount"
                            :title="t('No ministry payments recorded.')"
                        >
                            <template #actions>
                                <Link
                                    v-if="can('training.create')"
                                    href="/training/payments/create"
                                    class="create-btn"
                                >
                                    <Plus />
                                    {{ t('Record ministry payment') }}
                                </Link>
                            </template>
                        </EmptyState>
                    </tbody>
                </table>
            </template>

            <template v-if="payments.links?.length" #pager>
                <V2Pager :items="payments" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
