<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
import MisTabs from '@/components/MisTabs.vue';
import PersonnelFormsCard, {
    type PersonnelFormRecord,
} from '@/components/PersonnelFormsCard.vue';
import type { AttachmentTypeOption } from '@/components/PersonnelFormsField.vue';
import Heading from '@/components/Heading.vue';
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
    job_detail?: JobDetail | null;
    salaries?: Salary[];
    contracts?: Contract[];
    attachments?: EntityAttachment[];
    personnel_attachments?: PersonnelFormRecord[];
}

defineProps<{
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

const tabs = [
    { id: 'personal' as const, label: 'Personal' },
    { id: 'employment' as const, label: 'Employment' },
    { id: 'salary' as const, label: 'Salary & Contracts' },
    { id: 'projects' as const, label: 'Projects' },
    { id: 'attendance' as const, label: 'Attendance' },
    { id: 'payroll' as const, label: 'Payroll' },
    { id: 'documents' as const, label: 'Documents' },
];

const activeTab = ref<TabId>('personal');

const formatDate = (value?: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(new Date(value));
};

const formatCurrency = (value: number, currency = 'USD'): string =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency, maximumFractionDigits: 0 }).format(value);

const monthName = (month: number): string =>
    new Intl.DateTimeFormat('en-US', { month: 'short' }).format(new Date(2000, month - 1, 1));
</script>

<template>
    <Head :title="`${employee.first_name} ${employee.last_name}`" />

    <div class="flex w-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Heading
                    :title="`${employee.first_name} ${employee.last_name}`"
                    :description="employee.job_detail?.designation ?? undefined"
                />
                <Badge class="mt-2">{{ employee.status }}</Badge>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link href="/hr/employees">{{ t('Back to list') }}</Link>
                </Button>
                <Button v-if="can('hr.edit')" as-child>
                    <Link :href="`/hr/employees/${employee.id}/edit`">{{ t('Edit') }}</Link>
                </Button>
            </div>
        </div>

        <MisTabs v-model="activeTab" :tabs="tabs.map((tab) => ({ ...tab, label: t(tab.label) }))" />

        <Card v-if="activeTab === 'personal'">
            <CardHeader><CardTitle>{{ t('Personal Information') }}</CardTitle></CardHeader>
            <CardContent class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t("Father's name") }}</span><span>{{ employee.father_name ?? '—' }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t('Tazkira number') }}</span><span>{{ employee.tazkira_number ?? '—' }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t('Date of birth') }}</span><span>{{ formatDate(employee.date_of_birth) }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t('Gender') }}</span><span>{{ employee.gender ?? '—' }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t('Phone') }}</span><span>{{ employee.phone ?? '—' }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t('Email') }}</span><span>{{ employee.email ?? '—' }}</span></div>
                <div v-if="employee.current_address" class="sm:col-span-2">
                    <span class="text-muted-foreground">{{ t('Current address') }}</span>
                    <p class="mt-1 whitespace-pre-wrap">{{ employee.current_address }}</p>
                </div>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'employment'">
            <CardHeader><CardTitle>{{ t('Employment') }}</CardTitle></CardHeader>
            <CardContent class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t('Department') }}</span><span>{{ employee.job_detail?.department?.name ?? '—' }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t('Designation') }}</span><span>{{ employee.job_detail?.designation ?? '—' }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t('Hire date') }}</span><span>{{ formatDate(employee.job_detail?.hire_date) }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t('Salary Grade') }}</span><span>{{ employee.job_detail?.salary_grade ?? '—' }}</span></div>
            </CardContent>
        </Card>

        <div v-else-if="activeTab === 'salary'" class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader><CardTitle>{{ t('Salary History') }}</CardTitle></CardHeader>
                <CardContent>
                    <div v-if="!employee.salaries?.length" class="text-sm text-muted-foreground">{{ t('No salary records.') }}</div>
                    <div v-else class="space-y-2">
                        <div v-for="salary in employee.salaries" :key="salary.id" class="flex items-center justify-between rounded-md border px-3 py-2 text-sm">
                            <span class="text-muted-foreground">{{ formatDate(salary.effective_from) }}<span v-if="salary.effective_to"> — {{ formatDate(salary.effective_to) }}</span></span>
                            <span class="font-medium">{{ formatCurrency(salary.amount, salary.currency ?? 'USD') }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader><CardTitle>{{ t('Contracts') }}</CardTitle></CardHeader>
                <CardContent>
                    <div v-if="!employee.contracts?.length" class="text-sm text-muted-foreground">{{ t('No contracts on file.') }}</div>
                    <div v-else class="space-y-2">
                        <div v-for="contract in employee.contracts" :key="contract.id" class="rounded-md border px-3 py-2 text-sm">
                            <div class="font-medium">{{ contract.contract_number ?? t('Contract') }}</div>
                            <div class="text-muted-foreground">{{ formatDate(contract.start_date) }} — {{ formatDate(contract.end_date) }}</div>
                        </div>
                    </div>
                </CardContent>
            </Card>
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
                            <td class="py-2 text-muted-foreground">{{ a.project?.code ?? '—' }}</td>
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
                            <td class="py-2 text-muted-foreground">{{ adj.project?.code ?? '—' }}</td>
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
