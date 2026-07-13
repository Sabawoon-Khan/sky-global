<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Briefcase,
    Building2,
    Calendar,
    FileText,
    Mail,
    MapPin,
    Phone,
    User,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
import PermanentStaffToggle from '@/components/PermanentStaffToggle.vue';
import PersonnelStatusButtons from '@/components/PersonnelStatusButtons.vue';
import StatusChangeHistory, {
    type StatusChangeLogRecord,
} from '@/components/StatusChangeHistory.vue';
import MisTabs from '@/components/MisTabs.vue';
import PersonnelFormsCard, {
    type PersonnelFormRecord,
} from '@/components/PersonnelFormsCard.vue';
import type { AttachmentTypeOption } from '@/components/PersonnelFormsField.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useMisPage } from '@/composables/useMisPage';

interface Department {
    id: number;
    name: string;
}

interface JobDetail {
    designation?: string | null;
    hire_date?: string | null;
    salary_grade?: string | null;
    department?: Department | null;
}

interface Salary {
    id: number;
    amount: number;
    currency?: string | null;
    effective_from?: string | null;
    effective_to?: string | null;
}

interface Contract {
    id: number;
    contract_number?: string | null;
    start_date?: string | null;
    end_date?: string | null;
}

interface AttendanceRecord {
    id: number;
    year: number;
    month: number;
    days_present: number;
    status: string;
    project?: { code: string } | null;
}

interface AdjustmentRecord {
    id: number;
    type: string;
    amount: number;
    period_year: number;
    period_month: number;
    project?: { code: string } | null;
}

interface DeploymentRecord {
    id: number;
    role: string | null;
    project?: { id: number; code: string; name: string } | null;
}

interface Employee {
    id: number;
    first_name: string;
    last_name: string;
    father_name?: string | null;
    phone?: string | null;
    email?: string | null;
    tazkira_number?: string | null;
    date_of_birth?: string | null;
    gender?: string | null;
    current_address?: string | null;
    status: string;
    is_permanent: boolean;
    job_detail?: JobDetail | null;
    salaries?: Salary[];
    contracts?: Contract[];
    attachments?: EntityAttachment[];
    personnel_attachments?: PersonnelFormRecord[];
    status_change_logs?: StatusChangeLogRecord[];
}

const props = defineProps<{
    employee: Employee;
    attachmentTypes: AttachmentTypeOption[];
    attendances?: AttendanceRecord[];
    payrollAdjustments?: AdjustmentRecord[];
    deployments?: DeploymentRecord[];
}>();

const { t, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Employees', href: '/hr/employees' },
            { title: 'Profile', href: '#' },
        ],
    },
});

type TabId = 'personal' | 'employment' | 'salary' | 'projects' | 'attendance' | 'payroll' | 'documents';

const tabs = computed(() => {
    const items = [
        { id: 'personal' as const, label: t('Personal') },
        { id: 'employment' as const, label: t('Employment') },
        { id: 'salary' as const, label: t('Salary & Contracts') },
    ];

    if (!props.employee.is_permanent) {
        items.push({ id: 'projects' as const, label: t('Projects') });
    }

    items.push(
        { id: 'attendance' as const, label: t('Attendance') },
        { id: 'payroll' as const, label: t('Payroll') },
        { id: 'documents' as const, label: t('Documents') },
    );

    return items;
});

const activeTab = ref<TabId>('personal');

const fullName = computed(
    () => `${props.employee.first_name} ${props.employee.last_name}`,
);

const initials = computed(() => {
    const first = props.employee.first_name.charAt(0);
    const last = props.employee.last_name.charAt(0);

    return `${first}${last}`.toUpperCase();
});

const statusVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (status === 'active') {
        return 'default';
    }

    if (status === 'terminated') {
        return 'destructive';
    }

    return 'secondary';
};

const statusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        active: t('Active'),
        inactive: t('Inactive'),
        terminated: t('Terminated'),
    };

    return labels[status] ?? status;
};

const formatDate = (value?: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );
};

const formatCurrency = (value: number, currency = 'USD'): string =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(value);

const monthName = (month: number): string =>
    new Intl.DateTimeFormat('en-US', { month: 'short' }).format(
        new Date(2000, month - 1, 1),
    );

const currentSalary = computed(() => props.employee.salaries?.[0] ?? null);
</script>

<template>
    <Head :title="fullName" />

    <div class="flex w-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <Card class="overflow-hidden border-0 bg-gradient-to-br from-muted/60 via-background to-background shadow-sm">
            <CardContent class="p-6">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <Avatar class="size-16 border-2 border-background shadow-md">
                            <AvatarFallback class="bg-primary/10 text-lg font-semibold text-primary">
                                {{ initials }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="space-y-2">
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight">
                                    {{ fullName }}
                                </h1>
                                <p
                                    v-if="employee.job_detail?.designation"
                                    class="text-muted-foreground"
                                >
                                    {{ employee.job_detail.designation }}
                                    <span v-if="employee.job_detail?.department?.name">
                                        · {{ employee.job_detail.department.name }}
                                    </span>
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge :variant="statusVariant(employee.status)">
                                    {{ statusLabel(employee.status) }}
                                </Badge>
                                <Badge
                                    :variant="employee.is_permanent ? 'default' : 'outline'"
                                    class="gap-1"
                                >
                                    <Building2 class="size-3" />
                                    {{
                                        employee.is_permanent
                                            ? t('Permanent staff')
                                            : t('Project-based')
                                    }}
                                </Badge>
                            </div>
                            <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                <span v-if="employee.phone" class="inline-flex items-center gap-1.5">
                                    <Phone class="size-3.5" />
                                    {{ employee.phone }}
                                </span>
                                <span v-if="employee.email" class="inline-flex items-center gap-1.5">
                                    <Mail class="size-3.5" />
                                    {{ employee.email }}
                                </span>
                                <span
                                    v-if="employee.job_detail?.hire_date"
                                    class="inline-flex items-center gap-1.5"
                                >
                                    <Calendar class="size-3.5" />
                                    {{ t('Hired') }} {{ formatDate(employee.job_detail.hire_date) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <PersonnelStatusButtons
                            :url="`/hr/employees/${employee.id}`"
                            :name="fullName"
                            :status="employee.status"
                        />
                        <Button variant="outline" as-child>
                            <Link href="/hr/employees">{{ t('Back to list') }}</Link>
                        </Button>
                        <Button v-if="can('hr.edit')" as-child>
                            <Link :href="`/hr/employees/${employee.id}/edit`">
                                {{ t('Edit') }}
                            </Link>
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <MisTabs v-model="activeTab" :tabs="tabs" />

        <Card v-if="activeTab === 'personal'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <User class="size-5" />
                    {{ t('Personal Information') }}
                </CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border bg-muted/20 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t("Father's name") }}
                    </p>
                    <p class="mt-1 text-sm font-medium">{{ employee.father_name ?? '—' }}</p>
                </div>
                <div class="rounded-lg border bg-muted/20 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t('Tazkira number') }}
                    </p>
                    <p class="mt-1 text-sm font-medium">{{ employee.tazkira_number ?? '—' }}</p>
                </div>
                <div class="rounded-lg border bg-muted/20 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t('Date of birth') }}
                    </p>
                    <p class="mt-1 text-sm font-medium">{{ formatDate(employee.date_of_birth) }}</p>
                </div>
                <div class="rounded-lg border bg-muted/20 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t('Gender') }}
                    </p>
                    <p class="mt-1 text-sm font-medium capitalize">{{ employee.gender ?? '—' }}</p>
                </div>
                <div
                    v-if="employee.current_address"
                    class="rounded-lg border bg-muted/20 px-4 py-3 sm:col-span-2"
                >
                    <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <MapPin class="size-3.5" />
                        {{ t('Current address') }}
                    </p>
                    <p class="mt-1 whitespace-pre-wrap text-sm">{{ employee.current_address }}</p>
                </div>
            </CardContent>
        </Card>

        <div v-else-if="activeTab === 'employment'" class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Briefcase class="size-5" />
                        {{ t('Employment Details') }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="grid gap-3 text-sm">
                    <div class="flex items-center justify-between rounded-md border px-3 py-2.5">
                        <span class="text-muted-foreground">{{ t('Department') }}</span>
                        <span class="font-medium">{{ employee.job_detail?.department?.name ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-md border px-3 py-2.5">
                        <span class="text-muted-foreground">{{ t('Designation') }}</span>
                        <span class="font-medium">{{ employee.job_detail?.designation ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-md border px-3 py-2.5">
                        <span class="text-muted-foreground">{{ t('Hire date') }}</span>
                        <span class="font-medium">{{ formatDate(employee.job_detail?.hire_date) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-md border px-3 py-2.5">
                        <span class="text-muted-foreground">{{ t('Salary Grade') }}</span>
                        <span class="font-medium">{{ employee.job_detail?.salary_grade ?? '—' }}</span>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Staff Type') }}</CardTitle>
                    <CardDescription>
                        {{ t('Choose whether this employee works at the office or on projects.') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <PermanentStaffToggle
                        :employee-id="employee.id"
                        :is-permanent="employee.is_permanent"
                    />
                </CardContent>
            </Card>
        </div>

        <div v-else-if="activeTab === 'salary'" class="grid gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-1">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Wallet class="size-5" />
                        {{ t('Current Salary') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="!currentSalary" class="text-sm text-muted-foreground">
                        {{ t('No salary records.') }}
                    </div>
                    <div v-else class="space-y-1">
                        <p class="text-3xl font-bold tracking-tight">
                            {{ formatCurrency(currentSalary.amount, currentSalary.currency ?? 'USD') }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ t('Effective from') }} {{ formatDate(currentSalary.effective_from) }}
                        </p>
                    </div>
                    <div v-if="employee.salaries && employee.salaries.length > 1" class="mt-4 space-y-2 border-t pt-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            {{ t('Salary History') }}
                        </p>
                        <div
                            v-for="salary in employee.salaries.slice(1)"
                            :key="salary.id"
                            class="flex items-center justify-between rounded-md border px-3 py-2 text-sm"
                        >
                            <span class="text-muted-foreground">
                                {{ formatDate(salary.effective_from) }}
                                <span v-if="salary.effective_to"> — {{ formatDate(salary.effective_to) }}</span>
                            </span>
                            <span class="font-medium">
                                {{ formatCurrency(salary.amount, salary.currency ?? 'USD') }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="flex flex-col gap-4 lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="size-5" />
                            {{ t('Contracts') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!employee.contracts?.length" class="rounded-lg border border-dashed px-4 py-8 text-center text-sm text-muted-foreground">
                            {{ t('No contracts on file.') }}
                        </div>
                        <div v-else class="grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="contract in employee.contracts"
                                :key="contract.id"
                                class="rounded-lg border bg-muted/20 px-4 py-3"
                            >
                                <p class="font-medium">
                                    {{ contract.contract_number ?? t('Contract') }}
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ formatDate(contract.start_date) }} — {{ formatDate(contract.end_date) }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <StatusChangeHistory
                    :logs="employee.status_change_logs ?? []"
                    :title="t('Employment Status History')"
                />
            </div>
        </div>

        <Card v-else-if="activeTab === 'projects'">
            <CardHeader><CardTitle>{{ t('Project Assignments') }}</CardTitle></CardHeader>
            <CardContent>
                <div v-if="!deployments?.length" class="text-sm text-muted-foreground">{{ t('Not assigned to any project.') }}</div>
                <ul v-else class="divide-y">
                    <li v-for="d in deployments" :key="d.id" class="flex items-center justify-between py-3">
                        <div>
                            <Link v-if="d.project" :href="`/projects/${d.project.id}`" class="font-medium hover:underline">{{ d.project.code }} — {{ d.project.name }}</Link>
                            <p v-if="d.role" class="text-sm text-muted-foreground">{{ d.role }}</p>
                        </div>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'attendance'">
            <CardHeader><CardTitle>{{ t('Attendance History') }}</CardTitle></CardHeader>
            <CardContent>
                <div v-if="!attendances?.length" class="text-sm text-muted-foreground">{{ t('No attendance records.') }}</div>
                <table v-else class="w-full text-sm">
                    <thead><tr class="border-b text-muted-foreground"><th class="pb-2 text-start">{{ t('Period') }}</th><th class="pb-2 text-start">{{ t('Project') }}</th><th class="pb-2 text-start">{{ t('Present') }}</th><th class="pb-2 text-start">{{ t('Status') }}</th></tr></thead>
                    <tbody>
                        <tr v-for="a in attendances" :key="a.id" class="border-b last:border-0">
                            <td class="py-2">{{ monthName(a.month) }} {{ a.year }}</td>
                            <td class="py-2 text-muted-foreground">{{ a.project?.code ?? (employee.is_permanent ? t('General') : '—') }}</td>
                            <td class="py-2">{{ a.days_present }}</td>
                            <td class="py-2"><Badge variant="outline">{{ a.status }}</Badge></td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'payroll'">
            <CardHeader><CardTitle>{{ t('Payroll Adjustments') }}</CardTitle></CardHeader>
            <CardContent>
                <div v-if="!payrollAdjustments?.length" class="text-sm text-muted-foreground">{{ t('No payroll adjustments.') }}</div>
                <table v-else class="w-full text-sm">
                    <thead><tr class="border-b text-muted-foreground"><th class="pb-2 text-start">{{ t('Period') }}</th><th class="pb-2 text-start">{{ t('Type') }}</th><th class="pb-2 text-start">{{ t('Project') }}</th><th class="pb-2 text-end">{{ t('Amount') }}</th></tr></thead>
                    <tbody>
                        <tr v-for="adj in payrollAdjustments" :key="adj.id" class="border-b last:border-0">
                            <td class="py-2">{{ monthName(adj.period_month) }} {{ adj.period_year }}</td>
                            <td class="py-2"><Badge variant="outline">{{ adj.type }}</Badge></td>
                            <td class="py-2 text-muted-foreground">{{ adj.project?.code ?? (employee.is_permanent ? t('General') : '—') }}</td>
                            <td class="py-2 text-end font-medium">{{ formatCurrency(adj.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>

        <div v-else class="grid gap-4">
            <PersonnelFormsCard
                personnel-type="employee"
                :personnel-id="employee.id"
                :forms="employee.personnel_attachments ?? []"
                :attachment-types="attachmentTypes"
                :can-manage="can('hr.create')"
            />
            <EntityAttachments :attachments="employee.attachments ?? []" />
        </div>
    </div>
</template>
