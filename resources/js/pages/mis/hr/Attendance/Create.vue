<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { ArrowLeft, ClipboardList, Save } from '@lucide/vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import MisPagination from '@/components/MisPagination.vue';
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

const EMPLOYEE_TYPE = 'App\\Models\\Hr\\Employee';
const CONTRACTOR_TYPE = 'App\\Models\\Hr\\Contractor';

interface ProjectOption {
    id: number;
    code: string;
    name: string;
}

interface StaffRow {
    id: number;
    first_name: string;
    last_name: string;
    attendance_id?: number | null;
    days_present: number;
    days_absent: number;
    days_leave: number;
    overtime_hours: number;
    status?: string | null;
}

interface AttendanceEntry {
    attendance_id?: number | null;
    days_present: string;
    days_absent: string;
    days_leave: string;
    overtime_hours: string;
}

interface Props {
    projects: ProjectOption[];
    employees: Paginated<StaffRow>;
    contractors: Paginated<StaffRow>;
    summary?: {
        recorded: number;
        missing: number;
    };
    filters?: {
        year?: number;
        month?: number;
        project_id?: number;
        tab?: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const { t } = useMisPage();
const selectedProjectId = ref(
    props.filters?.project_id ? String(props.filters.project_id) : '',
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Attendance', href: '/hr/attendance' },
            { title: 'Manage attendance', href: '/hr/attendance/create' },
        ],
    },
});

const bulkEntries = ref<Record<number, AttendanceEntry>>({});

const filterYear = computed(
    () => props.filters?.year ?? new Date().getFullYear(),
);

const filterMonth = computed(
    () => props.filters?.month ?? new Date().getMonth() + 1,
);

const activeTab = computed(() =>
    props.filters?.tab === 'contractors' ? 'contractors' : 'employees',
);

const activeStaff = computed(() =>
    activeTab.value === 'employees'
        ? props.employees.data
        : props.contractors.data,
);

const activePagination = computed(() =>
    activeTab.value === 'employees' ? props.employees : props.contractors,
);

const indexHref = computed(() => {
    const params = new URLSearchParams({
        year: String(filterYear.value),
    });

    if (props.filters?.project_id) {
        params.set('project_id', String(props.filters.project_id));
    }

    return `/hr/attendance?${params.toString()}`;
});

const periodLabel = computed(() => {
    const month = filterMonth.value;

    if (!month || month < 1 || month > 12) {
        return String(filterYear.value);
    }

    const monthName = new Intl.DateTimeFormat('en-US', {
        month: 'long',
    }).format(new Date(2000, month - 1, 1));

    return `${monthName} ${filterYear.value}`;
});

const createBaseParams = computed(() => {
    const params = new URLSearchParams({
        year: String(filterYear.value),
        month: String(filterMonth.value),
    });

    if (props.filters?.project_id) {
        params.set('project_id', String(props.filters.project_id));
    }

    return params;
});

const tabHref = (tab: 'employees' | 'contractors'): string => {
    const params = new URLSearchParams(createBaseParams.value);
    params.set('tab', tab);

    return `/hr/attendance/create?${params.toString()}`;
};

const entryFromStaff = (person: StaffRow): AttendanceEntry => ({
    attendance_id: person.attendance_id ?? null,
    days_present: String(
        person.attendance_id != null ? person.days_present : 22,
    ),
    days_absent: String(person.days_absent ?? 0),
    days_leave: String(person.days_leave ?? 0),
    overtime_hours: String(person.overtime_hours ?? 0),
});

const syncEntriesForStaff = (staff: StaffRow[]): void => {
    for (const person of staff) {
        bulkEntries.value[person.id] = entryFromStaff(person);
    }
};

watch(
    () => props.filters?.project_id,
    (projectId) => {
        selectedProjectId.value = projectId ? String(projectId) : '';
    },
);

watch(
    () => [props.employees.data, props.contractors.data],
    () => {
        syncEntriesForStaff([
            ...props.employees.data,
            ...props.contractors.data,
        ]);
    },
    { immediate: true, deep: true },
);

const personLabel = (person: StaffRow): string =>
    `${person.first_name} ${person.last_name}`.trim();

const hasAttendance = (person: StaffRow): boolean =>
    person.attendance_id != null;

const statusVariant = (
    status?: string | null,
): 'default' | 'secondary' | 'outline' => {
    if (status === 'approved') {
        return 'default';
    }

    if (status === 'draft') {
        return 'secondary';
    }

    return 'outline';
};

const formError = computed(() => {
    const errors = page.props.errors as Record<string, string | string[]>;

    return errors.entries ?? errors.project_id ?? null;
});

const bulkPersonnelType = computed(() =>
    activeTab.value === 'employees' ? EMPLOYEE_TYPE : CONTRACTOR_TYPE,
);

const sheetProjectId = computed(() =>
    selectedProjectId.value ? Number(selectedProjectId.value) : '',
);

const monthOptions = Array.from({ length: 12 }, (_, index) => ({
    value: index + 1,
    label: new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
        new Date(2000, index, 1),
    ),
}));
</script>

<template>
    <Head :title="t('Manage attendance')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Button variant="ghost" size="sm" class="mb-2 -ms-2" as-child>
                    <Link :href="indexHref">
                        <ArrowLeft class="size-4" />
                        {{ t('Back to attendance') }}
                    </Link>
                </Button>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('Manage attendance') }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ periodLabel }}
                </p>
                <p
                    v-if="summary"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{
                        t(':recorded recorded · :missing missing', {
                            recorded: String(summary.recorded),
                            missing: String(summary.missing),
                        })
                    }}
                </p>
            </div>
        </div>

        <Card>
            <CardHeader class="pb-3">
                <CardTitle class="text-base">{{ t('Period') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <form
                    method="get"
                    action="/hr/attendance/create"
                    class="flex flex-wrap items-end gap-4"
                >
                    <input type="hidden" name="tab" :value="activeTab" />
                    <div class="grid gap-2">
                        <Label for="create_filter_year">{{ t('Year') }}</Label>
                        <Input
                            id="create_filter_year"
                            name="year"
                            type="number"
                            min="2000"
                            max="2100"
                            :default-value="filterYear"
                            class="w-28"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="create_filter_month">{{ t('Month') }}</Label>
                        <select
                            id="create_filter_month"
                            name="month"
                            class="flex h-9 w-40 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
                        >
                            <option
                                v-for="option in monthOptions"
                                :key="option.value"
                                :value="option.value"
                                :selected="option.value === filterMonth"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="create_filter_project">{{
                            t('Project')
                        }}</Label>
                        <select
                            id="create_filter_project"
                            name="project_id"
                            class="flex h-9 min-w-48 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">{{ t('None') }}</option>
                            <option
                                v-for="project in projects"
                                :key="project.id"
                                :value="project.id"
                                :selected="
                                    filters?.project_id === project.id
                                "
                            >
                                {{ project.code }} — {{ project.name }}
                            </option>
                        </select>
                    </div>
                    <Button type="submit" variant="outline">
                        {{ t('Change period') }}
                    </Button>
                </form>
            </CardContent>
        </Card>

        <Can permission="hr.create">
            <Card>
                <CardHeader class="space-y-4">
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <ClipboardList class="size-5" />
                            {{ t('Monthly attendance sheet') }}
                        </CardTitle>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                t(
                                    'Update existing rows or fill in missing staff for this month, then save.',
                                )
                            }}
                        </p>
                    </div>

                    <div class="flex gap-1 rounded-lg border bg-muted/30 p-1">
                        <Link
                            :href="tabHref('employees')"
                            class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
                            :class="
                                activeTab === 'employees'
                                    ? 'bg-background shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            preserve-scroll
                        >
                            {{ t('Employees') }}
                        </Link>
                        <Link
                            :href="tabHref('contractors')"
                            class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
                            :class="
                                activeTab === 'contractors'
                                    ? 'bg-background shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            preserve-scroll
                        >
                            {{ t('Contractors') }}
                        </Link>
                    </div>
                </CardHeader>

                <CardContent>
                    <Form
                        action="/hr/attendance/bulk"
                        method="post"
                        class="space-y-4"
                        :options="{ preserveScroll: true }"
                        v-slot="{ errors, processing }"
                    >
                        <input
                            type="hidden"
                            name="personnel_type"
                            :value="bulkPersonnelType"
                        />
                        <input
                            type="hidden"
                            name="year"
                            :value="filterYear"
                        />
                        <input
                            type="hidden"
                            name="month"
                            :value="filterMonth"
                        />
                        <input
                            type="hidden"
                            name="project_id"
                            :value="sheetProjectId"
                        />
                        <input
                            type="hidden"
                            name="employee_page"
                            :value="employees.meta?.current_page ?? 1"
                        />
                        <input
                            type="hidden"
                            name="contractor_page"
                            :value="contractors.meta?.current_page ?? 1"
                        />
                        <template
                            v-for="person in activeStaff"
                            :key="`entry-${person.id}`"
                        >
                            <input
                                type="hidden"
                                :name="`entries[${person.id}][personnel_id]`"
                                :value="person.id"
                            />
                            <input
                                v-if="bulkEntries[person.id]?.attendance_id"
                                type="hidden"
                                :name="`entries[${person.id}][attendance_id]`"
                                :value="bulkEntries[person.id].attendance_id"
                            />
                        </template>

                        <div class="grid gap-2 sm:max-w-xs">
                            <Label for="sheet_project_id">{{
                                t('Project')
                            }}</Label>
                            <select
                                id="sheet_project_id"
                                v-model="selectedProjectId"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">{{ t('None') }}</option>
                                <option
                                    v-for="project in projects"
                                    :key="project.id"
                                    :value="String(project.id)"
                                >
                                    {{ project.code }} — {{ project.name }}
                                </option>
                            </select>
                        </div>

                        <div class="overflow-x-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr
                                        class="border-b bg-muted/30 text-start text-muted-foreground"
                                    >
                                        <th class="px-4 py-3 font-medium">
                                            {{
                                                activeTab === 'employees'
                                                    ? t('Employee')
                                                    : t('Contractor')
                                            }}
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            {{ t('Status') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-end font-medium"
                                        >
                                            {{ t('Present') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-end font-medium"
                                        >
                                            {{ t('Absent') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-end font-medium"
                                        >
                                            {{ t('Leave') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-end font-medium"
                                        >
                                            {{ t('OT Hours') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="person in activeStaff"
                                        :key="person.id"
                                        class="border-b last:border-0"
                                        :class="
                                            hasAttendance(person)
                                                ? 'bg-muted/10'
                                                : ''
                                        "
                                    >
                                        <td class="px-4 py-3 font-medium">
                                            {{ personLabel(person) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <Badge
                                                v-if="hasAttendance(person)"
                                                :variant="
                                                    statusVariant(person.status)
                                                "
                                            >
                                                {{ person.status }}
                                            </Badge>
                                            <Badge
                                                v-else
                                                variant="outline"
                                            >
                                                {{ t('Missing') }}
                                            </Badge>
                                        </td>
                                        <td class="px-4 py-2 text-end">
                                            <Input
                                                v-model="
                                                    bulkEntries[person.id]
                                                        .days_present
                                                "
                                                :name="`entries[${person.id}][days_present]`"
                                                type="number"
                                                min="0"
                                                max="31"
                                                class="ms-auto h-9 w-20 text-end"
                                            />
                                        </td>
                                        <td class="px-4 py-2 text-end">
                                            <Input
                                                v-model="
                                                    bulkEntries[person.id]
                                                        .days_absent
                                                "
                                                :name="`entries[${person.id}][days_absent]`"
                                                type="number"
                                                min="0"
                                                max="31"
                                                class="ms-auto h-9 w-20 text-end"
                                            />
                                        </td>
                                        <td class="px-4 py-2 text-end">
                                            <Input
                                                v-model="
                                                    bulkEntries[person.id]
                                                        .days_leave
                                                "
                                                :name="`entries[${person.id}][days_leave]`"
                                                type="number"
                                                min="0"
                                                max="31"
                                                class="ms-auto h-9 w-20 text-end"
                                            />
                                        </td>
                                        <td class="px-4 py-2 text-end">
                                            <Input
                                                v-model="
                                                    bulkEntries[person.id]
                                                        .overtime_hours
                                                "
                                                :name="`entries[${person.id}][overtime_hours]`"
                                                type="number"
                                                min="0"
                                                step="0.5"
                                                class="ms-auto h-9 w-20 text-end"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <InputError :message="formError ?? errors.entries" />

                        <MisPagination :pagination="activePagination" />

                        <div
                            class="sticky bottom-0 flex flex-wrap items-center justify-between gap-3 border-t bg-background/95 py-4 backdrop-blur supports-[backdrop-filter]:bg-background/80"
                        >
                            <p class="text-sm text-muted-foreground">
                                {{
                                    t('Showing :count people on this page', {
                                        count: String(activeStaff.length),
                                    })
                                }}
                            </p>
                            <div class="flex gap-2">
                                <Button variant="outline" as-child>
                                    <Link :href="indexHref">{{
                                        t('Cancel')
                                    }}</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="
                                        processing || activeStaff.length === 0
                                    "
                                >
                                    <Save class="size-4" />
                                    {{ t('Save') }}
                                </Button>
                            </div>
                        </div>
                    </Form>
                </CardContent>
            </Card>
        </Can>
    </div>
</template>
