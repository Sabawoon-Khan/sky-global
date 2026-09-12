<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import BarChart from '@/components/charts/BarChart.vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import MisPagination from '@/components/MisPagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
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
import { formatAfn, formatDate, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { approvalStatusActions } from '@/lib/status-actions';
import { ArrowUpRight, CheckCircle2, Clock3, Paperclip } from '@lucide/vue';

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

const props = defineProps<{
    incomes: Paginated<Income>;
    filters?: { project_id?: number | null };
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
</script>

<template>
    <Head :title="t('Project Income')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png">
            <template #eyebrow>{{ t('Finance') }}</template>
            <template #title>{{ t('Project Income') }}</template>
            <template #description>
                {{ t('Income recorded against projects.') }}
            </template>
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
                :description="t('Last 6 months')"
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
            :description="t('Highest income by project')"
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
            :delay="false"
        >
            <template #filters>
                <div class="table-top">
                    <div>
                        <h2>{{ t('Project Income') }}</h2>
                        <p>{{ t('Income recorded against projects.') }}</p>
                    </div>
                </div>
            </template>

            <table>
                <thead>
                    <tr>
                        <th>{{ t('Description') }}</th>
                        <th>{{ t('Project') }}</th>
                        <th>{{ t('Date') }}</th>
                        <th>{{ t('Status') }}</th>
                        <th>{{ t('Attachment') }}</th>
                        <th class="end">{{ t('Amount') }}</th>
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
                        v-for="item in props.incomes.data"
                        :key="item.id"
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
                            <a
                                v-if="item.attachments?.length"
                                :href="item.attachments[0].download_url"
                                class="inline-flex items-center gap-1 text-primary hover:underline"
                                :title="item.attachments[0].original_filename"
                            >
                                <Paperclip class="size-3.5 shrink-0" />
                                <span class="max-w-[8rem] truncate text-xs">
                                    {{ item.attachments[0].original_filename }}
                                </span>
                            </a>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="end font-medium tabular-nums">
                            {{ money(item.amount) }}
                        </td>
                        <td class="end">
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
    </V2ListPage>
</template>
