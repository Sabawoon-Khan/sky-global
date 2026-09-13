<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    Briefcase,
    FileCheck,
    Layers3,
    Plus,
    Printer,
    Wallet,
} from '@lucide/vue';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import SortableTh from '@/components/SortableTh.vue';
import {
    V2FilterBar,
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2Pager,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { formatCurrency, formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface ProjectOption {
    id: number;
    code: string;
    name: string;
}

interface PayrollRun {
    id: number;
    title: string;
    payroll_type: 'general' | 'project';
    project?: { id: number; code: string; name?: string } | null;
    date_from: string;
    date_to: string;
    period_year: number;
    period_month: number;
    status: string;
    items_count?: number;
    total_net?: number | string | null;
    processed_by?: { name: string } | null;
    created_by_name?: string | null;
}

interface ChartPoint {
    key: string;
    label: string;
    value: number;
}

interface Props {
    payrollRuns: Paginated<PayrollRun>;
    projects: ProjectOption[];
    stats: {
        total: number;
        processed: number;
        draft: number;
        general: number;
        project: number;
        by_status?: Record<string, number>;
    };
    chart: {
        status: ChartPoint[];
        monthly: ChartPoint[];
    };
    filters?: {
        date_from?: string;
        date_to?: string;
        year?: number;
        month?: number;
        project_id?: number;
    };
}

const props = defineProps<Props>();

const { t, viewAction, deleteAction } = useMisPage();

const onlyKeys = ['payrollRuns', 'projects', 'stats', 'chart', 'filters'];
const { sortedRows } = provideTableSort(() => props.payrollRuns.data, {
    accessors: {
        title: (row) => row.title,
        type: (row) => row.payroll_type,
        range: (row) => row.date_from,
        project: (row) => row.project?.code ?? row.project?.name,
        staff: (row) => row.items_count,
        total: (row) => Number(row.total_net ?? 0),
    },
});

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
    const processedShare =
        total > 0 ? Math.round((props.stats.processed / total) * 100) : 0;

    return {
        processedShare,
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

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'title', label: t('Payroll') },
    { key: 'type', label: t('Type') },
    { key: 'range', label: t('Date range') },
    { key: 'project', label: t('Project') },
    { key: 'staff', label: t('Staff') },
    { key: 'total', label: t('Total net') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const newPayrollType = ref<'general' | 'project'>('general');
const newProjectId = ref<string>('');
const showPayrollForm = ref(false);

watch(newPayrollType, (type) => {
    if (type === 'general') {
        newProjectId.value = '';
    }
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Payroll', href: '/hr/payroll' },
        ],
    },
});

const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );

const typeLabel = (type: PayrollRun['payroll_type']): string =>
    type === 'project' ? t('Project') : t('General');

const typeVariant = (type: PayrollRun['payroll_type']): 'default' | 'secondary' =>
    type === 'project' ? 'default' : 'secondary';

const listTitle = computed(() => {
    if (props.filters?.year && props.filters?.month) {
        const month = new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
            new Date(2000, props.filters.month - 1, 1),
        );

        return t('Payroll runs for :month :year', {
            month,
            year: String(props.filters.year),
        });
    }

    return t('All payroll runs');
});

const totalRuns = computed(
    () => props.payrollRuns.meta?.total ?? props.payrollRuns.data.length,
);

const printUrl = (run: PayrollRun): string =>
    `/hr/payroll/${run.id}/print`;

const payrollActions = (run: PayrollRun): RowActionItem[] => [
    viewAction(`/hr/payroll/${run.id}`),
    {
        label: t('Print sheet'),
        icon: Printer,
        href: printUrl(run),
        download: true,
    },
    deleteAction(
        {
            href: `/hr/payroll/${run.id}`,
            title: t('Delete payroll run?'),
            description: t(
                'This payroll run and all its line items will be removed.',
            ),
        },
        'hr.delete',
    ),
];
</script>

<template>
    <Head :title="t('Payroll')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('HR') }}</template>
            <template #title>{{ t('Payroll') }}</template>
            <template #side>
            <Can permission="hr.create">
                <button
                    type="button"
                    class="create-btn"
                    @click="showPayrollForm = true"
                >
                    <Plus />
                    {{ t('Generate payroll') }}
                </button>
            </Can>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Run status') }}</template>
                        <template #meta
                            >{{ pipeline.processedShare }}%
                            {{ t('Processed') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{
                                    formatNumber(stats.processed)
                                }}</strong>
                                <small>{{ t('Processed') }}</small>
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
                        :title="t('Payroll runs')"
                        :value="formatNumber(stats.total)"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Processed')"
                        :value="formatNumber(stats.processed)"
                    >
                        <template #icon><FileCheck /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('General')"
                        :value="formatNumber(stats.general)"
                    >
                        <template #icon><Layers3 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Project')"
                        :value="formatNumber(stats.project)"
                    >
                        <template #icon><Briefcase /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel table-id="hr-payroll" :columns="tableColumns">
            <template #filters>
                <V2FilterBar>
                    <form
                        method="get"
                        action="/hr/payroll"
                        class="flex flex-wrap items-end gap-2"
                    >
                        <Input
                            name="year"
                            type="number"
                            min="2000"
                            max="2100"
                            :default-value="filters?.year ?? ''"
                            class="h-9 w-24"
                            :placeholder="t('Year')"
                        />
                        <Input
                            name="month"
                            type="number"
                            min="1"
                            max="12"
                            :default-value="filters?.month ?? ''"
                            class="h-9 w-20"
                            :placeholder="t('Month')"
                        />
                        <select
                            name="project_id"
                            class="mis-form-select h-9 min-w-[8rem]"
                        >
                            <option value="">{{ t('All projects') }}</option>
                            <option
                                v-for="project in projects"
                                :key="project.id"
                                :value="project.id"
                                :selected="filters?.project_id === project.id"
                            >
                                {{ project.code }}
                            </option>
                        </select>
                        <Button type="submit" variant="outline" class="h-9">
                            {{ t('Filter') }}
                        </Button>
                    </form>
                </V2FilterBar>
            </template>

            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="title">{{ t('Payroll') }}</SortableTh>
                            <SortableTh column="type">{{ t('Type') }}</SortableTh>
                            <SortableTh column="range">{{ t('Date range') }}</SortableTh>
                            <SortableTh column="project">{{ t('Project') }}</SortableTh>
                            <SortableTh column="staff">{{ t('Staff') }}</SortableTh>
                            <SortableTh column="total" align="end" class="end">{{ t('Total net') }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(run, index) in sortedRows"
                            :key="run.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="payrollRuns.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/hr/payroll/${run.id}`"
                                    class="code-chip"
                                >
                                    {{ run.title }}
                                </Link>
                            </td>
                            <td>{{ typeLabel(run.payroll_type) }}</td>
                            <td class="muted nowrap">
                                {{ formatDate(run.date_from) }}
                                —
                                {{ formatDate(run.date_to) }}
                            </td>
                            <td class="muted">
                                {{ run.project?.code ?? '—' }}
                            </td>
                            <td>{{ run.items_count ?? 0 }}</td>
                            <td class="nums">
                                {{
                                    formatCurrency(Number(run.total_net ?? 0))
                                }}
                            </td>
                            <td class="end">
                                <RowActionsMenu
                                    :actions="payrollActions(run)"
                                />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!payrollRuns.data.length"
                            :colspan="visibleColCount"
                            :title="
                                t(
                                    'No payroll runs yet. Record attendance first, then generate payroll.',
                                )
                            "
                        />
                    </tbody>
                </table>
            </template>

            <template v-if="payrollRuns.links?.length" #pager>
                <V2Pager :items="payrollRuns" :only="onlyKeys" />
            </template>
        </V2TablePanel>

        <Dialog
            :open="showPayrollForm"
            @update:open="showPayrollForm = $event"
        >
            <DialogContent class="sm:max-w-lg">
                <Form
                    action="/hr/payroll"
                    method="post"
                    :options="{ preserveScroll: true }"
                    v-slot="{ processing }"
                    @success="showPayrollForm = false"
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Generate payroll') }}</DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-3 py-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="payroll_type">{{ t('Type') }}</Label>
                            <select
                                id="payroll_type"
                                v-model="newPayrollType"
                                name="payroll_type"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="general">{{ t('General') }}</option>
                                <option value="project">{{ t('Project') }}</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="title">{{ t('Title') }}</Label>
                            <Input
                                id="title"
                                name="title"
                                type="text"
                                :placeholder="t('Optional')"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="date_from">{{ t('From') }}</Label>
                            <Input
                                id="date_from"
                                name="date_from"
                                type="date"
                                required
                                :default-value="filters?.date_from"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="date_to">{{ t('To') }}</Label>
                            <Input
                                id="date_to"
                                name="date_to"
                                type="date"
                                required
                                :default-value="filters?.date_to"
                            />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="project_id">{{ t('Project') }}</Label>
                            <select
                                id="project_id"
                                v-model="newProjectId"
                                name="project_id"
                                :disabled="newPayrollType !== 'project'"
                                :required="newPayrollType === 'project'"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm disabled:opacity-50"
                            >
                                <option value="">{{ t('Select project') }}</option>
                                <option
                                    v-for="project in projects"
                                    :key="project.id"
                                    :value="project.id"
                                >
                                    {{ project.code }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="showPayrollForm = false"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Generate payroll') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </V2ListPage>
</template>
