<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { ArrowLeft, CalendarDays, ClipboardList, Printer, Save, Send, Users, Wand2 } from '@lucide/vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import MisPage from '@/components/MisPage.vue';
import MisPagination from '@/components/MisPagination.vue';
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
import { cn } from '@/lib/utils';

const EMPLOYEE_TYPE = 'App\\Models\\Hr\\Employee';
const CONTRACTOR_TYPE = 'App\\Models\\Hr\\Contractor';

interface ProjectOption {
    id: number;
    code: string;
    name: string;
}

interface CalendarDay {
    day: number;
    weekday: string;
}

interface StaffRow {
    id: number;
    first_name: string;
    last_name: string;
    attendance_id?: number | null;
    days_present: number;
    days_absent: number;
    days_sick_leave: number;
    days_annual_leave: number;
    days_casual_leave: number;
    days_other: number;
    daily_marks: Record<string, string>;
    overtime_hours: number;
    status?: string | null;
}

interface AttendanceEntry {
    attendance_id?: number | null;
    daily_marks: Record<string, string>;
    days_present: string;
    days_absent: string;
    days_sick_leave: string;
    days_annual_leave: string;
    days_casual_leave: string;
    days_other: string;
    overtime_hours: string;
}

interface Props {
    projects: ProjectOption[];
    employees: Paginated<StaffRow>;
    contractors: Paginated<StaffRow>;
    calendar_days: CalendarDay[];
    mark_options: string[];
    filters?: {
        date_from?: string;
        date_to?: string;
        project_id?: number;
        sheet_id?: number;
        title?: string;
        attendance_type?: 'general' | 'project';
        tab?: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const { t, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Attendance', href: '/hr/attendance' },
            { title: 'Manage attendance', href: '/hr/attendance/create' },
        ],
    },
});

const emptyDailyMarks = (): Record<string, string> =>
    Object.fromEntries(
        props.calendar_days.map((day) => [String(day.day), '']),
    );

const totalsFromMarks = (
    marks: Record<string, string>,
): {
    days_present: number;
    days_absent: number;
    days_sick_leave: number;
    days_annual_leave: number;
    days_casual_leave: number;
    days_other: number;
} => {
    const totals = {
        days_present: 0,
        days_absent: 0,
        days_sick_leave: 0,
        days_annual_leave: 0,
        days_casual_leave: 0,
        days_other: 0,
    };

    for (const mark of Object.values(marks)) {
        switch (mark) {
            case 'P':
                totals.days_present++;
                break;
            case 'A':
                totals.days_absent++;
                break;
            case 'S':
                totals.days_sick_leave++;
                break;
            case 'AL':
                totals.days_annual_leave++;
                break;
            case 'CL':
                totals.days_casual_leave++;
                break;
            case 'O':
                totals.days_other++;
                break;
            default:
                break;
        }
    }

    return totals;
};

const syncTotalsFromMarks = (entry: AttendanceEntry): void => {
    const totals = totalsFromMarks(entry.daily_marks);

    entry.days_present = String(totals.days_present);
    entry.days_absent = String(totals.days_absent);
    entry.days_sick_leave = String(totals.days_sick_leave);
    entry.days_annual_leave = String(totals.days_annual_leave);
    entry.days_casual_leave = String(totals.days_casual_leave);
    entry.days_other = String(totals.days_other);
};

const entryFromStaff = (person: StaffRow): AttendanceEntry => {
    const dailyMarks = {
        ...emptyDailyMarks(),
        ...person.daily_marks,
    };

    const entry: AttendanceEntry = {
        attendance_id: person.attendance_id ?? null,
        daily_marks: dailyMarks,
        days_present: String(person.days_present ?? 0),
        days_absent: String(person.days_absent ?? 0),
        days_sick_leave: String(person.days_sick_leave ?? 0),
        days_annual_leave: String(person.days_annual_leave ?? 0),
        days_casual_leave: String(person.days_casual_leave ?? 0),
        days_other: String(person.days_other ?? 0),
        overtime_hours: String(Number(person.overtime_hours ?? 0)),
    };

    if (Object.values(dailyMarks).some((mark) => mark !== '')) {
        syncTotalsFromMarks(entry);
    }

    return entry;
};

const buildBulkEntries = (staff: StaffRow[]): Record<number, AttendanceEntry> =>
    Object.fromEntries(
        staff.map((person) => [person.id, entryFromStaff(person)]),
    );

const resetBulkEntriesFromProps = (): void => {
    employeeEntries.value = buildBulkEntries(props.employees.data);
    contractorEntries.value = buildBulkEntries(props.contractors.data);
};

const employeeEntries = ref<Record<number, AttendanceEntry>>(
    buildBulkEntries(props.employees.data),
);

const contractorEntries = ref<Record<number, AttendanceEntry>>(
    buildBulkEntries(props.contractors.data),
);

const isSaving = ref(false);
const pendingAction = ref<'save' | 'submit' | null>(null);

const lockedStatuses = new Set(['submitted', 'approved']);

const isLockedStatus = (status?: string | null): boolean =>
    status != null && lockedStatuses.has(status);

const canEditRow = (person: StaffRow): boolean =>
    !isLockedStatus(person.status) || can('hr.edit');

const toFloat = (value: string | number | undefined): number => {
    const parsed = Number.parseFloat(String(value ?? ''));

    return Number.isNaN(parsed) ? 0 : parsed;
};

const submitBulk = (action: 'save' | 'submit'): void => {
    if (action === 'submit') {
        const editableCount = activeStaff.value.filter(canEditRow).length;

        if (editableCount === 0) {
            return;
        }

        const confirmed = window.confirm(
            t(
                'After submitting, you will not be able to edit these records. Continue?',
            ),
        );

        if (!confirmed) {
            return;
        }
    }

    const entries: Record<
        number,
        {
            personnel_id: number;
            attendance_id?: number;
            daily_marks: Record<string, string>;
            days_present: number;
            days_absent: number;
            days_sick_leave: number;
            days_annual_leave: number;
            days_casual_leave: number;
            days_other: number;
            overtime_hours: number;
        }
    > = {};

    for (const person of activeStaff.value) {
        if (!canEditRow(person)) {
            continue;
        }

        const entry = activeEntries.value[person.id];

        if (!entry) {
            continue;
        }

        entries[person.id] = {
            personnel_id: person.id,
            daily_marks: entry.daily_marks,
            days_present: Number(entry.days_present),
            days_absent: Number(entry.days_absent),
            days_sick_leave: Number(entry.days_sick_leave),
            days_annual_leave: Number(entry.days_annual_leave),
            days_casual_leave: Number(entry.days_casual_leave),
            days_other: Number(entry.days_other),
            overtime_hours: toFloat(entry.overtime_hours),
        };

        if (entry.attendance_id) {
            entries[person.id].attendance_id = entry.attendance_id;
        }
    }

    if (Object.keys(entries).length === 0) {
        window.alert(
            t(
                'No editable records on this page. Submitted attendance can only be changed by an administrator.',
            ),
        );

        return;
    }

    isSaving.value = true;
    pendingAction.value = action;

    router.post(
        '/hr/attendance/bulk',
        {
            personnel_type: bulkPersonnelType.value,
            sheet_id: props.filters?.sheet_id ?? null,
            title: sheetTitle.value,
            attendance_type: sheetType.value,
            date_from: filterDateFrom.value,
            date_to: filterDateTo.value,
            year: filterYear.value,
            month: filterMonth.value,
            project_id:
                sheetType.value === 'project'
                    ? (selectedProjectId.value !== '' ? Number(selectedProjectId.value) : null)
                    : null,
            action,
            employee_page: props.employees.meta?.current_page ?? 1,
            contractor_page: props.contractors.meta?.current_page ?? 1,
            entries,
        },
        {
            preserveState: false,
            preserveScroll: true,
            onFinish: () => {
                isSaving.value = false;
                pendingAction.value = null;
            },
        },
    );
};

watch(
    () =>
        [
            props.filters?.date_from,
            props.filters?.date_to,
            props.filters?.project_id,
        ] as const,
    () => {
        resetBulkEntriesFromProps();
    },
);

watch(
    () => [props.employees.data, props.contractors.data, props.calendar_days] as const,
    () => {
        resetBulkEntriesFromProps();
    },
    { deep: true },
);

watch(
    () => [props.filters?.attendance_type, props.filters?.title, props.filters?.date_from] as const,
    () => {
        sheetType.value = props.filters?.attendance_type === 'project' ? 'project' : 'general';
        sheetTitle.value = props.filters?.title?.trim() || defaultSheetTitle(sheetType.value);
    },
);

watch(
    () => props.filters?.project_id,
    (value) => {
        selectedProjectId.value = value != null ? String(value) : '';
    },
);

watch(
    () => props.calendar_days,
    (days) => {
        if (days.length === 0) {
            return;
        }

        bulkFromDay.value = String(days[0].day);
        bulkToDay.value = String(days[days.length - 1].day);
    },
    { deep: true },
);

const filterDateFrom = computed(
    () => props.filters?.date_from ?? new Date().toISOString().slice(0, 10),
);

const filterDateTo = computed(
    () => props.filters?.date_to ?? new Date().toISOString().slice(0, 10),
);

const filterYear = computed(() => {
    const date = new Date(`${filterDateFrom.value}T00:00:00`);

    return date.getFullYear();
});

const filterMonth = computed(() => {
    const date = new Date(`${filterDateFrom.value}T00:00:00`);

    return date.getMonth() + 1;
});

const activeTab = computed(() =>
    props.filters?.tab === 'contractors' ? 'contractors' : 'employees',
);

const activeStaff = computed(() =>
    activeTab.value === 'employees'
        ? props.employees.data
        : props.contractors.data,
);

const activeEntries = computed<Record<number, AttendanceEntry>>(() =>
    activeTab.value === 'employees'
        ? employeeEntries.value
        : contractorEntries.value,
);

const activePagination = computed(() =>
    activeTab.value === 'employees' ? props.employees : props.contractors,
);

const selectedProject = computed(() => {
    if (selectedProjectId.value === '') {
        return null;
    }

    const projectId = Number(selectedProjectId.value);

    return (
        props.projects.find(
            (project) => project.id === projectId,
        ) ?? null
    );
});

const indexHref = computed(() => {
    const params = new URLSearchParams({
        date_from: filterDateFrom.value,
        date_to: filterDateTo.value,
    });

    if (selectedProjectId.value !== '') {
        params.set('project_id', selectedProjectId.value);
    }

    return `/hr/attendance?${params.toString()}`;
});

const periodLabel = computed(() => {
    const from = filterDateFrom.value;
    const to = filterDateTo.value;

    if (from === to) {
        return from;
    }

    return `${from} – ${to}`;
});

const defaultSheetTitle = (type: 'general' | 'project'): string =>
    type === 'project'
        ? `Project Attendance - ${filterDateFrom.value.slice(0, 7)}`
        : `General Attendance - ${filterDateFrom.value.slice(0, 7)}`;

const sheetType = ref<'general' | 'project'>(
    props.filters?.attendance_type === 'project' ? 'project' : 'general',
);

const sheetTitle = ref<string>(
    props.filters?.title?.trim() || defaultSheetTitle(sheetType.value),
);

const selectedProjectId = ref<string>(
    props.filters?.project_id != null ? String(props.filters.project_id) : '',
);

watch(sheetType, (type) => {
    if (type === 'general') {
        selectedProjectId.value = '';
    }
});

const loadFormSheetId = computed(() => {
    if (props.filters?.sheet_id == null) {
        return null;
    }

    const loadedType =
        props.filters.attendance_type === 'project' ? 'project' : 'general';

    if (loadedType !== sheetType.value) {
        return null;
    }

    if (sheetType.value === 'project') {
        const loadedProjectId =
            props.filters.project_id != null
                ? String(props.filters.project_id)
                : '';

        if (loadedProjectId !== selectedProjectId.value) {
            return null;
        }
    }

    return props.filters.sheet_id;
});

const bulkMark = ref<string>(props.mark_options[0] ?? 'P');
const bulkFromDay = ref<string>(
    props.calendar_days[0] ? String(props.calendar_days[0].day) : '1',
);
const bulkToDay = ref<string>(
    props.calendar_days[props.calendar_days.length - 1]
        ? String(props.calendar_days[props.calendar_days.length - 1].day)
        : '1',
);
const bulkMode = ref<'all' | 'empty'>('empty');

const createBaseParams = computed(() => {
    const params = new URLSearchParams({
        date_from: filterDateFrom.value,
        date_to: filterDateTo.value,
        attendance_type: sheetType.value,
        title: sheetTitle.value,
    });

    if (sheetType.value === 'project' && selectedProjectId.value !== '') {
        params.set('project_id', selectedProjectId.value);
    }

    if (loadFormSheetId.value != null) {
        params.set('sheet_id', String(loadFormSheetId.value));
    }

    return params;
});

const printHref = computed(() => {
    const params = new URLSearchParams(createBaseParams.value);
    params.set('autoprint', '1');

    return `/hr/attendance/print?${params.toString()}`;
});

const tabHref = (tab: 'employees' | 'contractors'): string => {
    const params = new URLSearchParams(createBaseParams.value);
    params.set('tab', tab);

    return `/hr/attendance/create?${params.toString()}`;
};

const applyBulkMark = (): void => {
    if (bulkMark.value === '') {
        return;
    }

    const from = Number(bulkFromDay.value);
    const to = Number(bulkToDay.value);

    if (Number.isNaN(from) || Number.isNaN(to)) {
        return;
    }

    const start = Math.min(from, to);
    const end = Math.max(from, to);

    applyMarkToRange(start, end, bulkMark.value, bulkMode.value);
};

const applyMarkToRange = (
    start: number,
    end: number,
    mark: string,
    mode: 'all' | 'empty',
): void => {
    for (const person of activeStaff.value) {
        if (!canEditRow(person)) {
            continue;
        }

        const entry = activeEntries.value[person.id];

        if (!entry) {
            continue;
        }

        for (let day = start; day <= end; day++) {
            const key = String(day);

            if (!(key in entry.daily_marks)) {
                continue;
            }

            if (mode === 'empty' && entry.daily_marks[key] !== '') {
                continue;
            }

            entry.daily_marks[key] = mark;
        }

        syncTotalsFromMarks(entry);
    }
};

const fillFullMonthPresent = (): void => {
    if (props.calendar_days.length === 0) {
        return;
    }

    const start = props.calendar_days[0].day;
    const end = props.calendar_days[props.calendar_days.length - 1].day;
    applyMarkToRange(start, end, 'P', 'empty');
};

const clearAllMarksOnPage = (): void => {
    for (const person of activeStaff.value) {
        if (!canEditRow(person)) {
            continue;
        }

        const entry = activeEntries.value[person.id];

        if (!entry) {
            continue;
        }

        for (const key of Object.keys(entry.daily_marks)) {
            entry.daily_marks[key] = '';
        }

        syncTotalsFromMarks(entry);
    }
};

const personLabel = (person: StaffRow): string =>
    `${person.first_name} ${person.last_name}`.trim();

const hasAttendance = (person: StaffRow): boolean =>
    person.attendance_id != null;

const markLabel = (mark: string): string => {
    switch (mark) {
        case 'P':
            return t('Present');
        case 'A':
            return t('Absent');
        case 'S':
            return t('Sick Leave');
        case 'AL':
            return t('Annual Leave');
        case 'CL':
            return t('Casual Leave');
        case 'O':
            return t('Other');
        default:
            return mark;
    }
};

const markTone = (mark: string): string => {
    switch (mark) {
        case 'P':
            return 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-400 border-emerald-500/30';
        case 'A':
            return 'bg-rose-500/12 text-rose-700 dark:text-rose-400 border-rose-500/30';
        case 'S':
            return 'bg-amber-500/12 text-amber-800 dark:text-amber-400 border-amber-500/30';
        case 'AL':
            return 'bg-sky-500/12 text-sky-700 dark:text-sky-400 border-sky-500/30';
        case 'CL':
            return 'bg-violet-500/12 text-violet-700 dark:text-violet-400 border-violet-500/30';
        case 'O':
            return 'bg-slate-500/12 text-slate-700 dark:text-slate-400 border-slate-500/30';
        default:
            return 'bg-muted/40 text-muted-foreground border-border/60 hover:bg-muted/60';
    }
};

const totalTone = (key: 'present' | 'absent' | 'sick'): string => {
    switch (key) {
        case 'present':
            return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
        case 'absent':
            return 'bg-rose-500/10 text-rose-700 dark:text-rose-400';
        case 'sick':
            return 'bg-amber-500/10 text-amber-800 dark:text-amber-400';
    }
};

const isWeekend = (weekday: string): boolean =>
    weekday === 'Sat' || weekday === 'Sun';

const sheetMeta = computed(() => [
    {
        label: t('Period'),
        value: periodLabel.value,
        icon: CalendarDays,
    },
    {
        label: t('Days'),
        value: String(props.calendar_days.length),
        icon: ClipboardList,
    },
    {
        label: activeTab.value === 'employees' ? t('Employees') : t('Contractors'),
        value: String(activeStaff.value.length),
        icon: Users,
    },
]);

const hasEditableRows = computed(() =>
    activeStaff.value.some(canEditRow),
);

const formError = computed(() => {
    const errors = page.props.errors as Record<string, string | string[]>;

    return errors.entries ?? errors.project_id ?? null;
});

const bulkPersonnelType = computed(() =>
    activeTab.value === 'employees' ? EMPLOYEE_TYPE : CONTRACTOR_TYPE,
);
</script>

<template>
    <Head :title="t('Manage attendance')" />

    <MisPage>
        <!-- Header -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <Button variant="ghost" size="sm" class="-ms-2 h-8 gap-1.5 px-2 text-muted-foreground" as-child>
                    <Link :href="indexHref">
                        <ArrowLeft class="size-3.5" />
                        {{ t('Back') }}
                    </Link>
                </Button>
                <div class="flex items-center gap-2.5">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <ClipboardList class="size-5" />
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold tracking-tight">
                            {{ sheetTitle || t('Manage attendance') }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ periodLabel }}
                            <span v-if="selectedProject" class="text-foreground/70">
                                · {{ selectedProject.code }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            <Button variant="outline" size="sm" class="gap-1.5 shadow-sm" as-child>
                <a :href="printHref" target="_blank" rel="noopener noreferrer">
                    <Printer class="size-3.5" />
                    {{ t('Print') }}
                </a>
            </Button>
        </div>

        <!-- Sheet setup -->
        <Card class="overflow-hidden border-border/60 shadow-sm">
            <CardHeader class="border-b bg-muted/20 py-3">
                <CardTitle class="text-sm font-medium text-muted-foreground">
                    {{ t('Sheet settings') }}
                </CardTitle>
            </CardHeader>
            <CardContent class="pt-4">
                <form
                    method="get"
                    action="/hr/attendance/create"
                    class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6 lg:items-end"
                >
                    <input type="hidden" name="tab" :value="activeTab" />
                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="attendance_title" class="text-xs font-medium">
                            {{ t('Title') }}
                        </Label>
                        <Input
                            id="attendance_title"
                            v-model="sheetTitle"
                            name="title"
                            required
                            class="h-9"
                            :placeholder="t('Monthly attendance sheet')"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="attendance_type" class="text-xs font-medium">
                            {{ t('Type') }}
                        </Label>
                        <select
                            id="attendance_type"
                            v-model="sheetType"
                            name="attendance_type"
                            class="flex h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs"
                        >
                            <option value="general">{{ t('General') }}</option>
                            <option value="project">{{ t('Project') }}</option>
                        </select>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="create_filter_date_from" class="text-xs font-medium">
                            {{ t('From') }}
                        </Label>
                        <Input
                            id="create_filter_date_from"
                            name="date_from"
                            type="date"
                            required
                            class="h-9"
                            :default-value="filterDateFrom"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="create_filter_date_to" class="text-xs font-medium">
                            {{ t('To') }}
                        </Label>
                        <Input
                            id="create_filter_date_to"
                            name="date_to"
                            type="date"
                            required
                            class="h-9"
                            :default-value="filterDateTo"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="create_filter_project" class="text-xs font-medium">
                            {{ t('Project') }}
                        </Label>
                        <select
                            id="create_filter_project"
                            v-model="selectedProjectId"
                            name="project_id"
                            :disabled="sheetType !== 'project'"
                            :required="sheetType === 'project'"
                            class="flex h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs disabled:opacity-50"
                        >
                            <option value="">{{ t('All staff') }}</option>
                            <option
                                v-for="project in projects"
                                :key="project.id"
                                :value="project.id"
                            >
                                {{ project.code }} — {{ project.name }}
                            </option>
                        </select>
                    </div>
                    <Button type="submit" class="h-9 w-full lg:w-auto">
                        {{ t('Load') }}
                    </Button>
                </form>
            </CardContent>
        </Card>

        <Can permission="hr.create">
            <!-- Meta chips -->
            <div class="grid gap-3 sm:grid-cols-3">
                <div
                    v-for="item in sheetMeta"
                    :key="item.label"
                    class="flex items-center gap-3 rounded-xl border bg-card px-4 py-3 shadow-sm"
                >
                    <div class="flex size-9 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                        <component :is="item.icon" class="size-4" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">{{ item.label }}</p>
                        <p class="text-sm font-semibold tabular-nums">{{ item.value }}</p>
                    </div>
                </div>
            </div>

            <Card class="overflow-hidden border-border/60 shadow-sm">
                <CardHeader class="space-y-4 border-b bg-muted/10 pb-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <CardTitle class="text-base font-semibold">
                            {{ t('Daily marks') }}
                        </CardTitle>
                        <div class="inline-flex rounded-lg border bg-background p-1 shadow-sm">
                            <Link
                                :href="tabHref('employees')"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="
                                    activeTab === 'employees'
                                        ? 'bg-primary text-primary-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                                preserve-scroll
                            >
                                {{ t('Employees') }}
                            </Link>
                            <Link
                                :href="tabHref('contractors')"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="
                                    activeTab === 'contractors'
                                        ? 'bg-primary text-primary-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                                preserve-scroll
                            >
                                {{ t('Contractors') }}
                            </Link>
                        </div>
                    </div>

                    <!-- Mark legend -->
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="mark in mark_options"
                            :key="mark"
                            :class="
                                cn(
                                    'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium',
                                    markTone(mark),
                                )
                            "
                        >
                            <span class="font-semibold">{{ mark }}</span>
                            <span class="opacity-80">{{ markLabel(mark) }}</span>
                        </span>
                    </div>
                </CardHeader>

                <CardContent class="space-y-4 pt-4">
                    <form @submit.prevent>
                        <!-- Bulk toolbar -->
                        <div class="flex flex-wrap items-center gap-3 rounded-xl border bg-muted/20 p-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="secondary"
                                    class="h-8 gap-1.5 shadow-sm"
                                    :disabled="!hasEditableRows"
                                    @click="fillFullMonthPresent"
                                >
                                    <Wand2 class="size-3.5" />
                                    {{ t('Fill P') }}
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    class="h-8"
                                    :disabled="!hasEditableRows"
                                    @click="clearAllMarksOnPage"
                                >
                                    {{ t('Clear') }}
                                </Button>
                            </div>
                            <div class="hidden h-6 w-px bg-border sm:block" />
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="text-muted-foreground">{{ t('Apply') }}</span>
                                <select
                                    v-model="bulkMark"
                                    :class="
                                        cn(
                                            'h-8 min-w-[3.5rem] rounded-lg border px-2 text-center text-xs font-semibold shadow-xs',
                                            markTone(bulkMark),
                                        )
                                    "
                                >
                                    <option
                                        v-for="mark in mark_options"
                                        :key="`bulk-mark-${mark}`"
                                        :value="mark"
                                    >
                                        {{ mark }}
                                    </option>
                                </select>
                                <select
                                    v-model="bulkFromDay"
                                    class="h-8 min-w-[3rem] rounded-lg border border-input bg-background px-2 text-center text-xs tabular-nums shadow-xs"
                                >
                                    <option
                                        v-for="day in calendar_days"
                                        :key="`bulk-from-${day.day}`"
                                        :value="String(day.day)"
                                    >
                                        {{ day.day }}
                                    </option>
                                </select>
                                <span class="text-muted-foreground">–</span>
                                <select
                                    v-model="bulkToDay"
                                    class="h-8 min-w-[3rem] rounded-lg border border-input bg-background px-2 text-center text-xs tabular-nums shadow-xs"
                                >
                                    <option
                                        v-for="day in calendar_days"
                                        :key="`bulk-to-${day.day}`"
                                        :value="String(day.day)"
                                    >
                                        {{ day.day }}
                                    </option>
                                </select>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    class="h-8 shadow-sm"
                                    @click="applyBulkMark"
                                >
                                    {{ t('Apply to all') }}
                                </Button>
                            </div>
                        </div>

                        <div
                            v-if="activeStaff.length === 0"
                            class="rounded-xl border border-dashed bg-muted/10 px-4 py-12 text-center"
                        >
                            <p class="text-sm text-muted-foreground">
                                {{
                                    filters?.project_id
                                        ? t(
                                              'No staff assigned to this project for :period.',
                                              { period: periodLabel },
                                          )
                                        : t('No active staff found.')
                                }}
                            </p>
                        </div>

                        <div
                            v-else
                            class="overflow-x-auto rounded-xl border shadow-sm"
                        >
                            <table class="w-full min-w-max text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-muted-foreground">
                                        <th
                                            rowspan="2"
                                            class="sticky start-0 z-20 min-w-[9rem] border-e bg-muted/30 px-3 py-2 text-start text-xs font-semibold uppercase tracking-wide"
                                        >
                                            {{
                                                activeTab === 'employees'
                                                    ? t('Employee')
                                                    : t('Contractor')
                                            }}
                                        </th>
                                        <th
                                            v-for="day in calendar_days"
                                            :key="`wd-${day.day}`"
                                            class="px-0.5 py-1 text-center text-[10px] font-medium uppercase"
                                            :class="isWeekend(day.weekday) ? 'bg-muted/50' : ''"
                                        >
                                            {{ day.weekday }}
                                        </th>
                                        <th
                                            colspan="4"
                                            class="border-s bg-muted/40 px-2 py-1 text-center text-[10px] font-semibold uppercase tracking-wide"
                                        >
                                            {{ t('Totals') }}
                                        </th>
                                    </tr>
                                    <tr class="border-b bg-muted/20 text-muted-foreground">
                                        <th
                                            v-for="day in calendar_days"
                                            :key="`d-${day.day}`"
                                            class="px-0.5 py-1 text-center text-[11px] font-semibold tabular-nums"
                                            :class="isWeekend(day.weekday) ? 'bg-muted/40' : ''"
                                        >
                                            {{ day.day }}
                                        </th>
                                        <th class="border-s px-2 py-1 text-center text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                            P
                                        </th>
                                        <th class="px-2 py-1 text-center text-[10px] font-medium text-rose-600 dark:text-rose-400">
                                            A
                                        </th>
                                        <th class="px-2 py-1 text-center text-[10px] font-medium text-amber-600 dark:text-amber-400">
                                            S
                                        </th>
                                        <th class="px-2 py-1 text-center text-[10px] font-medium">
                                            OT
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="person in activeStaff"
                                        :key="`${person.id}-${person.attendance_id ?? 'new'}`"
                                        class="border-b transition-colors last:border-0 hover:bg-muted/20"
                                    >
                                        <td
                                            class="sticky start-0 z-10 border-e bg-background px-3 py-2 shadow-[2px_0_6px_-2px_rgba(0,0,0,0.06)]"
                                        >
                                            <p
                                                class="truncate font-medium"
                                                :title="personLabel(person)"
                                            >
                                                {{ personLabel(person) }}
                                            </p>
                                            <p
                                                v-if="hasAttendance(person) && !canEditRow(person)"
                                                class="mt-0.5 text-[10px] capitalize text-muted-foreground"
                                            >
                                                {{ person.status }}
                                            </p>
                                        </td>
                                        <td
                                            v-for="day in calendar_days"
                                            :key="`${person.id}-${day.day}`"
                                            class="px-0.5 py-1"
                                            :class="isWeekend(day.weekday) ? 'bg-muted/15' : ''"
                                        >
                                            <select
                                                :class="
                                                    cn(
                                                        'mx-auto block h-8 w-9 cursor-pointer rounded-lg border text-center text-[11px] font-semibold shadow-xs transition-colors disabled:cursor-not-allowed disabled:opacity-40',
                                                        markTone(
                                                            activeEntries[person.id].daily_marks[
                                                                String(day.day)
                                                            ],
                                                        ),
                                                    )
                                                "
                                                :disabled="!canEditRow(person)"
                                                v-model="
                                                    activeEntries[person.id].daily_marks[
                                                        String(day.day)
                                                    ]
                                                "
                                                @change="
                                                    syncTotalsFromMarks(
                                                        activeEntries[person.id],
                                                    )
                                                "
                                            >
                                                <option value="" />
                                                <option
                                                    v-for="mark in mark_options"
                                                    :key="mark"
                                                    :value="mark"
                                                >
                                                    {{ mark }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="border-s px-2 py-1 text-center">
                                            <span
                                                :class="
                                                    cn(
                                                        'inline-flex min-w-[1.75rem] justify-center rounded-md px-1.5 py-0.5 text-xs font-semibold tabular-nums',
                                                        totalTone('present'),
                                                    )
                                                "
                                            >
                                                {{
                                                    activeEntries[person.id]
                                                        ?.days_present ?? 0
                                                }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-1 text-center">
                                            <span
                                                :class="
                                                    cn(
                                                        'inline-flex min-w-[1.75rem] justify-center rounded-md px-1.5 py-0.5 text-xs font-semibold tabular-nums',
                                                        totalTone('absent'),
                                                    )
                                                "
                                            >
                                                {{
                                                    activeEntries[person.id]
                                                        ?.days_absent ?? 0
                                                }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-1 text-center">
                                            <span
                                                :class="
                                                    cn(
                                                        'inline-flex min-w-[1.75rem] justify-center rounded-md px-1.5 py-0.5 text-xs font-semibold tabular-nums',
                                                        totalTone('sick'),
                                                    )
                                                "
                                            >
                                                {{
                                                    activeEntries[person.id]
                                                        ?.days_sick_leave ?? 0
                                                }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-1 text-center">
                                            <Input
                                                v-if="activeEntries[person.id]"
                                                v-model="
                                                    activeEntries[person.id]
                                                        .overtime_hours
                                                "
                                                type="number"
                                                min="0"
                                                step="0.5"
                                                :disabled="!canEditRow(person)"
                                                class="mx-auto h-8 w-14 border-muted-foreground/20 px-1 text-center text-xs tabular-nums shadow-xs"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <InputError :message="formError" class="mt-2" />

                        <MisPagination :pagination="activePagination" class="mt-3" />

                        <div
                            class="sticky bottom-0 -mx-1 mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-background/95 px-4 py-3 shadow-lg backdrop-blur supports-[backdrop-filter]:bg-background/80"
                        >
                            <p class="text-sm text-muted-foreground">
                                <span class="font-medium text-foreground tabular-nums">
                                    {{ activeStaff.length }}
                                </span>
                                {{ t('on this page') }}
                            </p>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="indexHref">{{ t('Cancel') }}</Link>
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="gap-1.5"
                                    :disabled="
                                        isSaving ||
                                        activeStaff.length === 0 ||
                                        !hasEditableRows
                                    "
                                    @click="submitBulk('save')"
                                >
                                    <Save class="size-3.5" />
                                    {{
                                        pendingAction === 'save'
                                            ? t('Saving...')
                                            : t('Save')
                                    }}
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    class="gap-1.5 shadow-sm"
                                    :disabled="
                                        isSaving ||
                                        activeStaff.length === 0 ||
                                        !hasEditableRows
                                    "
                                    @click="submitBulk('submit')"
                                >
                                    <Send class="size-3.5" />
                                    {{
                                        pendingAction === 'submit'
                                            ? t('Submitting...')
                                            : t('Submit')
                                    }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </Can>
    </MisPage>
</template>
