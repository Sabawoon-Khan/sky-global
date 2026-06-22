<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
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
}

defineProps<{
    employee: Employee;
}>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Employees', href: '/hr/employees' },
            { title: 'Profile', href: '#' },
        ],
    },
});

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
</script>

<template>
    <Head :title="`${employee.first_name} ${employee.last_name}`" />

    <div class="flex flex-1 flex-col gap-6 p-4">
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
                <Button as-child>
                    <Link :href="`/hr/employees/${employee.id}/edit`">{{ t('Edit') }}</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Personal Information') }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">{{ t("Father's name") }}</span>
                        <span>{{ employee.father_name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">{{ t('Tazkira number') }}</span>
                        <span>{{ employee.tazkira_number ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">{{ t('Date of birth') }}</span>
                        <span>{{ formatDate(employee.date_of_birth) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">{{ t('Gender') }}</span>
                        <span>{{ employee.gender ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">{{ t('Phone') }}</span>
                        <span>{{ employee.phone ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">{{ t('Email') }}</span>
                        <span>{{ employee.email ?? '—' }}</span>
                    </div>
                    <div v-if="employee.current_address">
                        <span class="text-muted-foreground">{{ t('Current address') }}</span>
                        <p class="mt-1">{{ employee.current_address }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Employment') }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">{{ t('Department') }}</span>
                        <span>{{ employee.job_detail?.department?.name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">{{ t('Hire date') }}</span>
                        <span>{{ formatDate(employee.job_detail?.hire_date) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">{{ t('Salary Grade') }}</span>
                        <span>{{ employee.job_detail?.salary_grade ?? '—' }}</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Salary History') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="!employee.salaries?.length"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('No salary records.') }}
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="salary in employee.salaries"
                            :key="salary.id"
                            class="flex items-center justify-between rounded-md border px-3 py-2 text-sm"
                        >
                            <span class="text-muted-foreground">
                                {{ formatDate(salary.effective_from) }}
                                <span v-if="salary.effective_to">
                                    — {{ formatDate(salary.effective_to) }}
                                </span>
                            </span>
                            <span class="font-medium">
                                {{
                                    formatCurrency(
                                        salary.amount,
                                        salary.currency ?? 'USD',
                                    )
                                }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Contracts') }}</CardTitle>
                    <CardDescription>{{ t('Employment contract records') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="!employee.contracts?.length"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('No contracts on file.') }}
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="contract in employee.contracts"
                            :key="contract.id"
                            class="rounded-md border px-3 py-2 text-sm"
                        >
                            <div class="font-medium">
                                {{ contract.contract_number ?? t('Contract') }}
                            </div>
                            <div class="text-muted-foreground">
                                {{ formatDate(contract.start_date) }} —
                                {{ formatDate(contract.end_date) }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <EntityAttachments :attachments="employee.attachments ?? []" />
    </div>
</template>
