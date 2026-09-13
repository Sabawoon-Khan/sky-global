<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    CheckCircle,
    ClipboardList,
    FileCheck,
    FilePen,
    Plus,
    Printer,
    Send,
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
import type { Paginated } from '@/lib/format';
import { formatNumber } from '@/lib/format';
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

interface AttendanceSheet {
    id: number;
    title: string;
    attendance_type: 'general' | 'project';
    project?: { id: number; code: string; name?: string } | null;
    date_from: string;
    date_to: string;
    year: number;
    month: number;
    staff_count: number;
    status: 'draft' | 'submitted' | 'approved' | 'partial';
    can_delete: boolean;
    created_by_name?: string | null;
    updated_at?: string | null;
}

interface ChartPoint {
    key: string;
    label: string;
    value: number;
}

interface Props {
    sheets: Paginated<AttendanceSheet>;
    projects: ProjectOption[];
    stats: {
        total: number;
        draft: number;
        submitted: number;
        approved: number;
        partial: number;
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

const { t, editAction, deleteAction, can } = useMisPage();

const onlyKeys = ['sheets', 'projects', 'stats', 'chart', 'filters'];
const { sortedRows } = provideTableSort(() => props.sheets.data, {
    accessors: {
        title: (row) => row.title,
        type: (row) => row.attendance_type,
        range: (row) => row.date_from,
        project: (row) => row.project?.code ?? row.project?.name,
        staff: (row) => row.staff_count,
        status: (row) => row.status,
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
    const approvedShare =
        total > 0 ? Math.round((props.stats.approved / total) * 100) : 0;

    return {
        approvedShare,
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
    { key: 'title', label: t('Sheet') },
    { key: 'type', label: t('Type') },
    { key: 'range', label: t('Date range') },
    { key: 'project', label: t('Project') },
    { key: 'staff', label: t('Staff') },
    { key: 'status', label: t('Status') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const newSheetType = ref<'general' | 'project'>('general');
const showSheetForm = ref(false);
const newSheetProjectId = ref<string>('');

watch(newSheetType, (type) => {
    if (type === 'general') {
        newSheetProjectId.value = '';
    }
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Attendance', href: '/hr/attendance' },
        ],
    },
});

const monthName = (month: number): string => {
    if (!month || month < 1 || month > 12) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
        new Date(2000, month - 1, 1),
    );
};

const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );

const typeLabel = (type: AttendanceSheet['attendance_type']): string =>
    type === 'project' ? t('Project') : t('General');

const typeVariant = (
    type: AttendanceSheet['attendance_type'],
): 'default' | 'secondary' =>
    type === 'project' ? 'default' : 'secondary';

const statusLabel = (status: AttendanceSheet['status']): string => {
    switch (status) {
        case 'submitted':
            return t('Submitted');
        case 'approved':
            return t('Approved');
        case 'partial':
            return t('Partial');
        default:
            return t('Draft');
    }
};

const statusVariant = (
    status: AttendanceSheet['status'],
): 'default' | 'secondary' | 'outline' => {
    switch (status) {
        case 'approved':
            return 'default';
        case 'submitted':
            return 'secondary';
        case 'partial':
            return 'outline';
        default:
            return 'outline';
    }
};

const listTitle = computed(() => {
    if (props.filters?.year && props.filters?.month) {
        return t('Attendance sheets for :month :year', {
            month: monthName(props.filters.month),
            year: String(props.filters.year),
        });
    }

    return t('All attendance sheets');
});

const totalSheets = computed(
    () => props.sheets.meta?.total ?? props.sheets.data.length,
);

const openSheetUrl = (sheet: AttendanceSheet): string =>
    `/hr/attendance/create?sheet_id=${sheet.id}`;

const printSheetUrl = (sheet: AttendanceSheet): string =>
    `/hr/attendance/print?sheet_id=${sheet.id}`;

const canApproveSheet = (sheet: AttendanceSheet): boolean =>
    can('hr.edit')
    && (sheet.status === 'submitted' || sheet.status === 'partial');

const sheetActions = (sheet: AttendanceSheet): RowActionItem[] => [
    editAction(openSheetUrl(sheet), 'hr.create'),
    {
        label: t('Approve'),
        icon: CheckCircle,
        separator: true,
        href: `/hr/attendance/sheets/${sheet.id}/approve`,
        method: 'post',
        confirm: {
            title: t('Approve attendance'),
            description: t(
                'Approve all submitted records on this sheet? This cannot be undone.',
            ),
            confirmLabel: t('Approve'),
        },
        hidden: !canApproveSheet(sheet),
    },
    {
        label: t('Print sheet'),
        icon: Printer,
        onClick: () => {
            if (typeof window !== 'undefined') {
                window.open(
                    `${printSheetUrl(sheet)}&autoprint=1`,
                    '_blank',
                    'noopener,noreferrer',
                );
            }
        },
    },
    {
        ...deleteAction(
            {
                href: `/hr/attendance/sheets/${sheet.id}`,
                title: t('Delete attendance sheet?'),
                description: t(
                    'This sheet and all draft attendance entries on it will be permanently removed.',
                ),
            },
            'hr.delete',
        ),
        hidden: !sheet.can_delete,
    },
];
</script>

<template>
    <Head :title="t('Attendance')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('HR') }}</template>
            <template #title>{{ t('Attendance') }}</template>
            <template #side>
            <Can permission="hr.create">
                <button
                    type="button"
                    class="create-btn"
                    @click="showSheetForm = true"
                >
                    <Plus />
                    {{ t('New sheet') }}
                </button>
            </Can>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Sheet status') }}</template>
                        <template #meta
                            >{{ pipeline.approvedShare }}%
                            {{ t('Approved') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{
                                    formatNumber(stats.approved)
                                }}</strong>
                                <small>{{ t('Approved') }}</small>
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
                        :title="t('Sheets')"
                        :value="formatNumber(stats.total)"
                    >
                        <template #icon><ClipboardList /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Draft')"
                        :value="formatNumber(stats.draft)"
                    >
                        <template #icon><FilePen /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Submitted')"
                        :value="formatNumber(stats.submitted)"
                    >
                        <template #icon><Send /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Approved')"
                        :value="formatNumber(stats.approved)"
                    >
                        <template #icon><FileCheck /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="hr-attendance"
            :columns="tableColumns"
        >
            <template #filters>
                <V2FilterBar>
                    <form
                        method="get"
                        action="/hr/attendance"
                        class="flex flex-wrap items-end gap-2"
                    >
                        <Input
                            id="year"
                            name="year"
                            type="number"
                            min="2000"
                            max="2100"
                            :default-value="filters?.year ?? ''"
                            class="h-9 w-24"
                            :placeholder="t('Year')"
                        />
                        <Input
                            id="month"
                            name="month"
                            type="number"
                            min="1"
                            max="12"
                            :default-value="filters?.month ?? ''"
                            class="h-9 w-20"
                            :placeholder="t('Month')"
                        />
                        <select
                            id="list_project"
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
                            <SortableTh column="title">{{ t('Sheet') }}</SortableTh>
                            <SortableTh column="type">{{ t('Type') }}</SortableTh>
                            <SortableTh column="range">{{ t('Date range') }}</SortableTh>
                            <SortableTh column="project">{{ t('Project') }}</SortableTh>
                            <SortableTh column="staff">{{ t('Staff') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(sheet, index) in sortedRows"
                            :key="sheet.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="sheets.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="openSheetUrl(sheet)"
                                    class="code-chip"
                                >
                                    {{ sheet.title }}
                                </Link>
                            </td>
                            <td>{{ typeLabel(sheet.attendance_type) }}</td>
                            <td class="muted nowrap">
                                {{ formatDate(sheet.date_from) }}
                                —
                                {{ formatDate(sheet.date_to) }}
                            </td>
                            <td class="muted">
                                {{ sheet.project?.code ?? '—' }}
                            </td>
                            <td>{{ sheet.staff_count }}</td>
                            <td>{{ statusLabel(sheet.status) }}</td>
                            <td class="end">
                                <RowActionsMenu
                                    :actions="sheetActions(sheet)"
                                />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!sheets.data.length"
                            :colspan="visibleColCount"
                            :title="t('No attendance sheets.')"
                        />
                    </tbody>
                </table>
            </template>

            <template v-if="sheets.links?.length" #pager>
                <V2Pager :items="sheets" :only="onlyKeys" />
            </template>
        </V2TablePanel>

        <Dialog :open="showSheetForm" @update:open="showSheetForm = $event">
            <DialogContent class="sm:max-w-lg">
                <form method="get" action="/hr/attendance/create">
                    <DialogHeader>
                        <DialogTitle>{{ t('New sheet') }}</DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-3 py-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="new_attendance_type">{{ t('Type') }}</Label>
                            <select
                                id="new_attendance_type"
                                v-model="newSheetType"
                                name="attendance_type"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="general">{{ t('General') }}</option>
                                <option value="project">{{ t('Project') }}</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="new_sheet_project">{{ t('Project') }}</Label>
                            <select
                                id="new_sheet_project"
                                v-model="newSheetProjectId"
                                name="project_id"
                                :disabled="newSheetType !== 'project'"
                                :required="newSheetType === 'project'"
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
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="showSheetForm = false"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit">
                            {{ t('New sheet') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </V2ListPage>
</template>
