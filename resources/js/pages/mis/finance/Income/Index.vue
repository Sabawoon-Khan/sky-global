<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BarChart from '@/components/charts/BarChart.vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import FileLink from '@/components/FileLink.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import MisPagination from '@/components/MisPagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    V2FilterBar,
    V2Hero,
    V2ListPage,
    V2Panel,
    V2SelectFilter,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import SortableTh from '@/components/SortableTh.vue';
import { formatAfn, formatDate, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { approvalStatusActions } from '@/lib/status-actions';
import { ArrowUpRight, CheckCircle2, Clock3 } from '@lucide/vue';

interface FinanceAttachment {
    id: number;
    original_filename: string;
    download_url: string;
}

interface Income {
    id: number;
    description: string;
    amount: number;
    amount_usd?: number | null;
    currency?: string | null;
    transaction_date?: string | null;
    status?: string | null;
    project?: { id: number; code: string; name: string } | null;
    attachments?: FinanceAttachment[];
}

interface ChartPoint {
    key: string;
    label?: string;
    value: number;
    count?: number;
}

interface ProjectOption {
    id: number;
    code: string;
    name: string;
}

const props = defineProps<{
    incomes: Paginated<Income>;
    projects?: ProjectOption[];
    categories?: string[];
    filters?: {
        search?: string | null;
        project_id?: number | null;
        status?: string | null;
        category?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
    stats?: {
        total?: number;
        count?: number;
        approved?: number;
        pending?: number;
    };
    charts?: {
        monthly?: Array<{ label: string; value: number }>;
        by_status?: ChartPoint[];
        by_project?: ChartPoint[];
    };
}>();

const { t, editAction, deleteAction, gateActions } = useMisPage();
const viewingRecord = ref<Income | null>(null);

const onlyKeys = [
    'incomes',
    'projects',
    'categories',
    'filters',
    'stats',
    'charts',
];

const { filters, pending, apply, clear } = useMisFilters(
    '/finance/income',
    {
        search: props.filters?.search ?? '',
        project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
        status: props.filters?.status ?? '',
        category: props.filters?.category ?? '',
        date_from: props.filters?.date_from ?? '',
        date_to: props.filters?.date_to ?? '',
    },
    {
        search: '',
        project_id: '',
        status: '',
        category: '',
        date_from: '',
        date_to: '',
    },
    { only: onlyKeys, liveKeys: ['search'] },
);

const hasActiveFilters = computed(
    () =>
        Boolean(filters.search) ||
        Boolean(filters.project_id) ||
        Boolean(filters.status) ||
        Boolean(filters.category) ||
        Boolean(filters.date_from) ||
        Boolean(filters.date_to),
);

const { sortedRows } = provideTableSort(() => props.incomes.data, {
    accessors: {
        description: (row) => row.description,
        project: (row) => row.project?.code ?? row.project?.name,
        date: (row) => row.transaction_date,
        status: (row) => row.status,
        attachment: (row) => row.attachments?.[0]?.original_filename,
        amount: (row) => row.amount,
    },
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finance', href: '/finance' },
            { title: 'Project Income', href: '/finance/income' },
        ],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'description', label: t('Description') },
    { key: 'project', label: t('Project') },
    { key: 'date', label: t('Date') },
    { key: 'status', label: t('Status') },
    { key: 'attachment', label: t('Attachment') },
    { key: 'amount', label: t('Amount') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const money = (value?: number | null): string => formatAfn(value);

const statusLabel = (status?: string | null): string => {
    const key = status || 'pending';
    const labels: Record<string, string> = {
        pending: t('Pending'),
        approved: t('Approved'),
        rejected: t('Rejected'),
    };
    return labels[key] ?? key;
};

const monthlyChart = computed(() => {
    const rows = props.charts?.monthly ?? [];
    return {
        labels: rows.map((row) => row.label),
        datasets: [
            {
                label: t('Income'),
                data: rows.map((row) => Number(row.value ?? 0)),
                backgroundColor: 'rgba(31, 78, 95, 0.85)',
            },
        ],
    };
});

const statusChart = computed(() => {
    const rows = props.charts?.by_status ?? [];
    return {
        labels: rows.map((row) => statusLabel(row.key)),
        data: rows.map((row) => Number(row.value ?? 0)),
    };
});

const projectChart = computed(() => {
    const rows = props.charts?.by_project ?? [];
    return {
        labels: rows.map((row) => row.key),
        datasets: [
            {
                label: t('Income'),
                data: rows.map((row) => Number(row.value ?? 0)),
                backgroundColor: 'rgba(184, 149, 108, 0.85)',
            },
        ],
    };
});

const incomeActions = (item: Income): RowActionItem[] => [
    ...(item.project
        ? [editAction(`/mis/projects/${item.project.id}`, 'projects.view')]
        : []),
    ...gateActions(
        approvalStatusActions({
            url: `/finance/incomes/${item.id}`,
            name: item.description,
            status: item.status ?? 'pending',
            t,
        }),
        'finance.edit',
    ),
    deleteAction(
        {
            href: `/finance/incomes/${item.id}`,
            title: t('Delete income record'),
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                { name: item.description },
            ),
        },
        'finance.delete',
    ),
];

function onProjectChange(value: string): void {
    apply({ project_id: value });
}

function onStatusChange(value: string): void {
    apply({ status: value });
}

function onCategoryChange(value: string): void {
    apply({ category: value });
}
</script>

<template>
    <Head :title="t('Project Income')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png">
            <template #eyebrow>{{ t('Finance') }}</template>
            <template #title>{{ t('Project Income') }}</template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Records')"
                        :value="String(stats?.count ?? incomes.total ?? 0)"
                    >
                        <template #icon><ArrowUpRight /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('Total')"
                        :value="formatAfn(stats?.total)"
                    >
                        <template #icon><ArrowUpRight /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="warm"
                        :title="t('Approved')"
                        :value="formatAfn(stats?.approved)"
                    >
                        <template #icon><CheckCircle2 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Pending')"
                        :value="formatAfn(stats?.pending)"
                    >
                        <template #icon><Clock3 /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <div class="grid gap-4 lg:grid-cols-5">
            <V2Panel
                class="lg:col-span-3"
                :title="t('Monthly income')"
            >
                <BarChart
                    v-if="monthlyChart.labels.length"
                    :labels="monthlyChart.labels"
                    :datasets="monthlyChart.datasets"
                    :show-legend="false"
                />
                <p v-else class="py-8 text-center text-sm text-muted-foreground">
                    {{ t('No income records found.') }}
                </p>
            </V2Panel>

            <V2Panel
                class="lg:col-span-2"
                :title="t('By status')"
            >
                <DonutChart
                    v-if="statusChart.data.some((v) => v > 0)"
                    :labels="statusChart.labels"
                    :data="statusChart.data"
                    :center-label="t('Total')"
                />
                <p v-else class="py-8 text-center text-sm text-muted-foreground">
                    {{ t('No income records found.') }}
                </p>
            </V2Panel>
        </div>

        <V2Panel
            v-if="projectChart.labels.length"
            :title="t('Top projects')"
        >
            <BarChart
                :labels="projectChart.labels"
                :datasets="projectChart.datasets"
                :show-legend="false"
                horizontal
            />
        </V2Panel>

        <V2TablePanel
            table-id="finance-project-income"
            :columns="tableColumns"
            :pending="pending && incomes.data.length > 0"
            :delay="false"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search description, project, or reference...')"
                            @submit="apply()"
                            @clear="apply({ search: '' })"
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
                        v-model="filters.project_id"
                        :label="t('Project')"
                        @change="onProjectChange"
                    >
                        <option value="">{{ t('All projects') }}</option>
                        <option
                            v-for="project in projects ?? []"
                            :key="project.id"
                            :value="String(project.id)"
                        >
                            {{ project.code }}
                        </option>
                    </V2SelectFilter>
                    <V2SelectFilter
                        v-model="filters.status"
                        :label="t('Status')"
                        @change="onStatusChange"
                    >
                        <option value="">{{ t('All statuses') }}</option>
                        <option value="pending">{{ t('Pending') }}</option>
                        <option value="approved">{{ t('Approved') }}</option>
                        <option value="rejected">{{ t('Rejected') }}</option>
                    </V2SelectFilter>
                    <V2SelectFilter
                        v-model="filters.category"
                        :label="t('Category')"
                        @change="onCategoryChange"
                    >
                        <option value="">{{ t('All categories') }}</option>
                        <option
                            v-for="category in categories ?? []"
                            :key="category"
                            :value="category"
                        >
                            {{ category }}
                        </option>
                    </V2SelectFilter>
                    <template v-if="hasActiveFilters" #actions>
                        <Button type="button" variant="ghost" class="h-9" @click="clear">
                            {{ t('Clear') }}
                        </Button>
                    </template>
                    <template #columns>
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>

            <table>
                <thead>
                    <tr>
                        <SortableTh column="description">{{ t('Description') }}</SortableTh>
                        <SortableTh column="project">{{ t('Project') }}</SortableTh>
                        <SortableTh column="date">{{ t('Date') }}</SortableTh>
                        <SortableTh column="status">{{ t('Status') }}</SortableTh>
                        <SortableTh column="attachment">{{ t('Attachment') }}</SortableTh>
                        <SortableTh column="amount" align="end" class="end">{{ t('Amount') }}</SortableTh>
                        <th class="end">{{ t('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!props.incomes.data.length">
                        <td colspan="7" class="py-10 text-center text-muted-foreground">
                            {{ t('No income records found.') }}
                        </td>
                    </tr>
                    <tr
                        v-for="item in sortedRows"
                        :key="item.id"
                        class="cursor-pointer"
                        @click="viewingRecord = item"
                    >
                        <td>{{ item.description }}</td>
                        <td>
                            <div class="font-medium">{{ item.project?.code ?? '—' }}</div>
                            <div
                                v-if="item.project?.name"
                                class="text-xs text-muted-foreground"
                            >
                                {{ item.project.name }}
                            </div>
                        </td>
                        <td>{{ formatDate(item.transaction_date) }}</td>
                        <td>
                            <Badge variant="outline">
                                {{ statusLabel(item.status) }}
                            </Badge>
                        </td>
                        <td>
                            <FileLink
                                v-if="item.attachments?.length"
                                :href="item.attachments[0].download_url"
                                :label="item.attachments[0].original_filename"
                                show-icon
                                compact
                            />
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="end font-medium tabular-nums">
                            {{ money(item.amount) }}
                        </td>
                        <td class="end" @click.stop>
                            <RowActionsMenu :actions="incomeActions(item)" />
                        </td>
                    </tr>
                </tbody>
            </table>

            <template #pager>
                <div class="table-pager">
                    <MisPagination :pagination="props.incomes" />
                </div>
            </template>
        </V2TablePanel>

        <Dialog
            :open="viewingRecord !== null"
            @update:open="(open) => !open && (viewingRecord = null)"
        >
            <DialogContent v-if="viewingRecord" class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ t('Description') }}</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <p class="whitespace-pre-wrap text-sm leading-relaxed">
                        {{ viewingRecord.description || '—' }}
                    </p>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">{{ t('Project') }}</dt>
                            <dd class="font-medium">
                                {{ viewingRecord.project?.code ?? '—' }}
                                <span
                                    v-if="viewingRecord.project?.name"
                                    class="block text-xs font-normal text-muted-foreground"
                                >
                                    {{ viewingRecord.project.name }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Date') }}</dt>
                            <dd class="font-medium">
                                {{ formatDate(viewingRecord.transaction_date) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Status') }}</dt>
                            <dd>
                                <Badge variant="outline">
                                    {{ statusLabel(viewingRecord.status) }}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Amount') }}</dt>
                            <dd class="font-medium tabular-nums">
                                {{ money(viewingRecord.amount) }}
                            </dd>
                        </div>
                    </dl>
                    <div v-if="viewingRecord.attachments?.length">
                        <p class="mb-1 text-sm text-muted-foreground">
                            {{ t('Attachment') }}
                        </p>
                        <div class="flex flex-col gap-1">
                            <FileLink
                                v-for="file in viewingRecord.attachments"
                                :key="file.id"
                                :href="file.download_url"
                                :label="file.original_filename"
                                show-icon
                                class="text-sm"
                            />
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="secondary"
                        @click="viewingRecord = null"
                    >
                        {{ t('Close') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </V2ListPage>
</template>
