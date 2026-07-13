<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { CalendarDays, ClipboardList, Plus, Printer } from '@lucide/vue';
import Can from '@/components/Can.vue';
import MisPage from '@/components/MisPage.vue';
import MisPagination from '@/components/MisPagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useMisPage } from '@/composables/useMisPage';
import type { Paginated } from '@/lib/format';
import { formatNumber } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { cn } from '@/lib/utils';

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

interface Props {
    sheets: Paginated<AttendanceSheet>;
    projects: ProjectOption[];
    filters?: {
        date_from?: string;
        date_to?: string;
        year?: number;
        month?: number;
        project_id?: number;
    };
}

const props = defineProps<Props>();

const { t, editAction, deleteAction } = useMisPage();

const newSheetType = ref<'general' | 'project'>('general');
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

const sheetStats = computed(() => [
    {
        label: t('Total sheets'),
        value: formatNumber(props.sheets.meta?.total ?? props.sheets.data.length),
        icon: ClipboardList,
        accent: 'bg-primary/10 text-primary',
    },
    {
        label: t('On this page'),
        value: formatNumber(props.sheets.data.length),
        icon: CalendarDays,
        accent: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    },
]);

const openSheetUrl = (sheet: AttendanceSheet): string =>
    `/hr/attendance/create?sheet_id=${sheet.id}`;

const printSheetUrl = (sheet: AttendanceSheet): string =>
    `/hr/attendance/print?sheet_id=${sheet.id}`;

const sheetActions = (sheet: AttendanceSheet): RowActionItem[] => [
    editAction(openSheetUrl(sheet), 'hr.create'),
    {
        label: t('Print'),
        icon: Printer,
        onClick: () => {
            window.open(
                `${printSheetUrl(sheet)}&autoprint=1`,
                '_blank',
                'noopener,noreferrer',
            );
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

    <MisPage>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <ClipboardList class="size-5" />
                </div>
                <div>
                    <h1 class="text-xl font-semibold tracking-tight">
                        {{ t('Attendance') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ t('Open a sheet to record or update daily marks.') }}
                    </p>
                </div>
            </div>
            <Can permission="hr.create">
                <form
                    method="get"
                    action="/hr/attendance/create"
                    class="flex flex-wrap items-end gap-2 rounded-xl border bg-card p-3 shadow-sm"
                >
                    <div class="grid gap-1.5">
                        <Label for="new_attendance_type" class="text-xs font-medium">
                            {{ t('Type') }}
                        </Label>
                        <select
                            id="new_attendance_type"
                            v-model="newSheetType"
                            name="attendance_type"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs"
                        >
                            <option value="general">{{ t('General') }}</option>
                            <option value="project">{{ t('Project') }}</option>
                        </select>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="date_from" class="text-xs font-medium">
                            {{ t('From') }}
                        </Label>
                        <Input
                            id="date_from"
                            name="date_from"
                            type="date"
                            required
                            class="h-9 w-36"
                            :default-value="filters?.date_from"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="date_to" class="text-xs font-medium">
                            {{ t('To') }}
                        </Label>
                        <Input
                            id="date_to"
                            name="date_to"
                            type="date"
                            required
                            class="h-9 w-36"
                            :default-value="filters?.date_to"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="new_sheet_project" class="text-xs font-medium">
                            {{ t('Project') }}
                        </Label>
                        <select
                            id="new_sheet_project"
                            v-model="newSheetProjectId"
                            name="project_id"
                            :disabled="newSheetType !== 'project'"
                            :required="newSheetType === 'project'"
                            class="h-9 min-w-[9rem] rounded-md border border-input bg-background px-3 text-sm shadow-xs disabled:opacity-50"
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
                    <Button type="submit" class="h-9 gap-1.5 shadow-sm">
                        <Plus class="size-3.5" />
                        {{ t('New sheet') }}
                    </Button>
                </form>
            </Can>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div
                v-for="stat in sheetStats"
                :key="stat.label"
                class="flex items-center gap-3 rounded-xl border bg-card px-4 py-3 shadow-sm"
            >
                <div
                    :class="
                        cn(
                            'flex size-9 items-center justify-center rounded-lg',
                            stat.accent,
                        )
                    "
                >
                    <component :is="stat.icon" class="size-4" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">{{ stat.label }}</p>
                    <p class="text-2xl font-bold tabular-nums tracking-tight">
                        {{ stat.value }}
                    </p>
                </div>
            </div>
        </div>

        <Card class="overflow-hidden border-border/60 shadow-sm">
            <CardHeader class="border-b bg-muted/10 pb-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <CardTitle class="text-base font-semibold">
                        {{ listTitle }}
                    </CardTitle>
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
                            class="h-9 min-w-[8rem] rounded-md border border-input bg-background px-3 text-sm shadow-xs"
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
                        <Button type="submit" variant="outline" class="h-9 shadow-sm">
                            {{ t('Filter') }}
                        </Button>
                    </form>
                </div>
            </CardHeader>
            <CardContent class="space-y-4 pt-4">
                <div
                    v-if="sheets.data.length === 0"
                    class="rounded-xl border border-dashed bg-muted/10 px-4 py-12 text-center text-sm text-muted-foreground"
                >
                    {{ t('No attendance sheets.') }}
                </div>

                <div v-else class="overflow-x-auto rounded-xl border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/30 text-start text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="px-4 py-3 font-semibold">{{ t('Sheet') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ t('Type') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ t('Date range') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ t('Project') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ t('Staff') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ t('Status') }}</th>
                                <th class="px-4 py-3 text-end font-semibold">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="sheet in sheets.data"
                                :key="sheet.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/20"
                            >
                                <td class="px-4 py-3">
                                    <Link
                                        :href="openSheetUrl(sheet)"
                                        class="font-medium hover:text-primary hover:underline"
                                    >
                                        {{ sheet.title }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge
                                        :variant="typeVariant(sheet.attendance_type)"
                                    >
                                        {{ typeLabel(sheet.attendance_type) }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground tabular-nums">
                                    {{ formatDate(sheet.date_from) }}
                                    —
                                    {{ formatDate(sheet.date_to) }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ sheet.project?.code ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex min-w-[2rem] justify-center rounded-md bg-muted px-2 py-0.5 text-xs font-semibold tabular-nums">
                                        {{ sheet.staff_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="statusVariant(sheet.status)">
                                        {{ statusLabel(sheet.status) }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <RowActionsMenu :actions="sheetActions(sheet)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <MisPagination :pagination="sheets" />
            </CardContent>
        </Card>
    </MisPage>
</template>
