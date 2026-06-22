<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { CalendarDays, Plus } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import MisPagination from '@/components/MisPagination.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { attendanceStatusActions } from '@/lib/status-actions';
import { useMisPage } from '@/composables/useMisPage';

const EMPLOYEE_TYPE = 'App\\Models\\Hr\\Employee';
const CONTRACTOR_TYPE = 'App\\Models\\Hr\\Contractor';

interface PersonOption {
    id: number;
    first_name: string;
    last_name: string;
}

interface ProjectOption {
    id: number;
    code: string;
    name: string;
}

interface AttendanceRecord {
    id: number;
    personnel_type: string;
    personnel_id: number;
    personnel_name?: string | null;
    project?: { id: number; code: string; name: string } | null;
    year: number;
    month: number;
    days_present: number;
    days_absent: number;
    days_leave: number;
    overtime_hours?: number | null;
    status: string;
}

interface Props {
    attendances: Paginated<AttendanceRecord>;
    projects: ProjectOption[];
    employees: PersonOption[];
    contractors: PersonOption[];
    filters?: {
        year?: number;
        month?: number;
        project_id?: number;
    };
}

const props = defineProps<Props>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Attendance', href: '/hr/attendance' },
        ],
    },
});

const personnelType = ref(EMPLOYEE_TYPE);

const personnelOptions = computed(() =>
    personnelType.value === EMPLOYEE_TYPE ? props.employees : props.contractors,
);

const personLabel = (person: PersonOption): string =>
    `${person.first_name} ${person.last_name}`.trim();

const monthName = (month: number): string => {
    if (!month || month < 1 || month > 12) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
        new Date(2000, month - 1, 1),
    );
};

const personnelTypeLabel = (type: string): string => {
    const parts = type.split('\\');

    return parts[parts.length - 1] ?? type;
};

const attendanceActions = (record: AttendanceRecord): RowActionItem[] =>
    attendanceStatusActions(record.id, record.status, t);
</script>

<template>
    <Head :title="t('Attendance')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            :title="t('Attendance')"
            :description="
                t(
                    'Record monthly attendance, approve records, then process payroll',
                )
            "
        />

        <div class="grid gap-6 xl:grid-cols-3">
            <Card class="xl:col-span-1">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Plus class="size-5" />
                        {{ t('Record attendance') }}
                    </CardTitle>
                    <CardDescription>
                        {{
                            t(
                                'Create a monthly attendance entry for an employee or contractor',
                            )
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        action="/hr/attendance"
                        method="post"
                        class="grid gap-4"
                        :options="{
                            preserveScroll: true,
                            forceFormData: true,
                            resetOnSuccess: true,
                        }"
                        v-slot="{ errors, processing }"
                    >
                        <input
                            type="hidden"
                            name="personnel_type"
                            :value="personnelType"
                        />

                        <div class="grid gap-2">
                            <Label for="personnel_type">{{
                                t('Personnel type')
                            }}</Label>
                            <select
                                id="personnel_type"
                                v-model="personnelType"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option :value="EMPLOYEE_TYPE">
                                    {{ t('Employee') }}
                                </option>
                                <option :value="CONTRACTOR_TYPE">
                                    {{ t('Contractor') }}
                                </option>
                            </select>
                        </div>

                        <div class="grid gap-2">
                            <Label for="personnel_id"
                                >{{ t('Person') }} *</Label
                            >
                            <select
                                id="personnel_id"
                                name="personnel_id"
                                required
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="" disabled selected>
                                    {{ t('Select person') }}
                                </option>
                                <option
                                    v-for="person in personnelOptions"
                                    :key="person.id"
                                    :value="person.id"
                                >
                                    {{ personLabel(person) }}
                                </option>
                            </select>
                            <InputError :message="errors.personnel_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="project_id">{{ t('Project') }}</Label>
                            <select
                                id="project_id"
                                name="project_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">{{ t('None') }}</option>
                                <option
                                    v-for="project in projects"
                                    :key="project.id"
                                    :value="project.id"
                                >
                                    {{ project.code }} — {{ project.name }}
                                </option>
                            </select>
                            <InputError :message="errors.project_id" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="create_year"
                                    >{{ t('Year') }} *</Label
                                >
                                <Input
                                    id="create_year"
                                    name="year"
                                    type="number"
                                    required
                                    :default-value="
                                        filters?.year ??
                                        new Date().getFullYear()
                                    "
                                />
                                <InputError :message="errors.year" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="create_month"
                                    >{{ t('Month') }} *</Label
                                >
                                <Input
                                    id="create_month"
                                    name="month"
                                    type="number"
                                    min="1"
                                    max="12"
                                    required
                                    :default-value="
                                        filters?.month ??
                                        new Date().getMonth() + 1
                                    "
                                />
                                <InputError :message="errors.month" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="grid gap-2">
                                <Label for="days_present">{{
                                    t('Present')
                                }}</Label>
                                <Input
                                    id="days_present"
                                    name="days_present"
                                    type="number"
                                    min="0"
                                    max="31"
                                    default-value="0"
                                />
                                <InputError :message="errors.days_present" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="days_absent">{{
                                    t('Absent')
                                }}</Label>
                                <Input
                                    id="days_absent"
                                    name="days_absent"
                                    type="number"
                                    min="0"
                                    max="31"
                                    default-value="0"
                                />
                                <InputError :message="errors.days_absent" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="days_leave">{{ t('Leave') }}</Label>
                                <Input
                                    id="days_leave"
                                    name="days_leave"
                                    type="number"
                                    min="0"
                                    max="31"
                                    default-value="0"
                                />
                                <InputError :message="errors.days_leave" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="overtime_hours">{{
                                t('Overtime hours')
                            }}</Label>
                            <Input
                                id="overtime_hours"
                                name="overtime_hours"
                                type="number"
                                min="0"
                                step="0.5"
                                default-value="0"
                            />
                            <InputError :message="errors.overtime_hours" />
                        </div>

                        <OptionalAttachmentField :error="errors.attachment" />

                        <Button type="submit" :disabled="processing">
                            {{ t('Create record') }}
                        </Button>
                    </Form>
                </CardContent>
            </Card>

            <Card class="xl:col-span-2">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CalendarDays class="size-5" />
                        {{ t('Attendance Records') }}
                    </CardTitle>
                    <CardDescription>
                        {{
                            t(':count records · filter by period', {
                                count: String(
                                    attendances.meta?.total ??
                                        attendances.data.length,
                                ),
                            })
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <form
                        method="get"
                        action="/hr/attendance"
                        class="flex flex-wrap items-end gap-4"
                    >
                        <div class="grid gap-2">
                            <Label for="year">{{ t('Year') }}</Label>
                            <Input
                                id="year"
                                name="year"
                                type="number"
                                :default-value="
                                    filters?.year ?? new Date().getFullYear()
                                "
                                class="w-28"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="month">{{ t('Month') }}</Label>
                            <Input
                                id="month"
                                name="month"
                                type="number"
                                min="1"
                                max="12"
                                :default-value="
                                    filters?.month ?? new Date().getMonth() + 1
                                "
                                class="w-20"
                            />
                        </div>
                        <Button type="submit" variant="outline">{{
                            t('Filter')
                        }}</Button>
                    </form>

                    <div
                        v-if="attendances.data.length === 0"
                        class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                    >
                        {{ t('No attendance records for this period.') }}
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b text-start text-muted-foreground"
                                >
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Personnel') }}
                                    </th>
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Type') }}
                                    </th>
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Project') }}
                                    </th>
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Period') }}
                                    </th>
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Present') }}
                                    </th>
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Absent') }}
                                    </th>
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Leave') }}
                                    </th>
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('OT Hours') }}
                                    </th>
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Status') }}
                                    </th>
                                    <th class="pb-3 text-end font-medium">
                                        {{ t('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="record in attendances.data"
                                    :key="record.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3 pr-4 font-medium">
                                        {{
                                            record.personnel_name ??
                                            `#${record.personnel_id}`
                                        }}
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground">
                                        {{
                                            personnelTypeLabel(
                                                record.personnel_type,
                                            )
                                        }}
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground">
                                        {{ record.project?.code ?? '—' }}
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground">
                                        {{ monthName(record.month) }}
                                        {{ record.year }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ record.days_present }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ record.days_absent }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ record.days_leave }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ record.overtime_hours ?? 0 }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge variant="outline">{{
                                            record.status
                                        }}</Badge>
                                    </td>
                                    <td class="py-3 text-right">
                                        <RowActionsMenu
                                            :actions="attendanceActions(record)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <MisPagination :pagination="attendances" />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
