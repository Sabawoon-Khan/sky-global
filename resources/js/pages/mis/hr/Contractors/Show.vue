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

interface Agreement {
    id: number;
    agreement_number?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    notes?: string | null;
}

interface Rate {
    id: number;
    daily_rate?: number | null;
    monthly_rate?: number | null;
    currency?: string | null;
    project?: { id: number; code: string; name: string } | null;
    effective_from?: string | null;
    effective_to?: string | null;
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

interface Contractor {
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
    agreements?: Agreement[];
    rates?: Rate[];
    attachments?: EntityAttachment[];
    personnel_attachments?: PersonnelFormRecord[];
}

defineProps<{
    contractor: Contractor;
    attachmentTypes?: AttachmentTypeOption[];
    attendances?: AttendanceRecord[];
    payrollAdjustments?: AdjustmentRecord[];
    deployments?: DeploymentRecord[];
}>();

const { t, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Contractors', href: '/hr/contractors' },
            { title: 'Profile', href: '#' },
        ],
    },
});

type TabId = 'personal' | 'agreements' | 'rates' | 'projects' | 'attendance' | 'payroll' | 'documents';

const tabs = [
    { id: 'personal' as const, label: 'Personal' },
    { id: 'agreements' as const, label: 'Agreements' },
    { id: 'rates' as const, label: 'Rates' },
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

const formatCurrency = (value?: number | null, currency = 'USD'): string => {
    if (value == null) return '—';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency, maximumFractionDigits: 0 }).format(value);
};

const monthName = (month: number): string =>
    new Intl.DateTimeFormat('en-US', { month: 'short' }).format(new Date(2000, month - 1, 1));
</script>

<template>
    <Head :title="`${contractor.first_name} ${contractor.last_name}`" />

    <div class="flex w-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Heading
                    :title="`${contractor.first_name} ${contractor.last_name}`"
                    :description="t('Contractor profile')"
                />
                <Badge class="mt-2">{{ contractor.status }}</Badge>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link href="/hr/contractors">{{ t('Back to list') }}</Link>
                </Button>
                <Button v-if="can('hr.edit')" as-child>
                    <Link :href="`/hr/contractors/${contractor.id}/edit`">{{ t('Edit') }}</Link>
                </Button>
            </div>
        </div>

        <MisTabs v-model="activeTab" :tabs="tabs.map((tab) => ({ ...tab, label: t(tab.label) }))" />

        <Card v-if="activeTab === 'personal'">
            <CardHeader><CardTitle>{{ t('Personal Information') }}</CardTitle></CardHeader>
            <CardContent class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t("Father's name") }}</span><span>{{ contractor.father_name ?? '—' }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t('Tazkira number') }}</span><span>{{ contractor.tazkira_number ?? '—' }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t('Phone') }}</span><span>{{ contractor.phone ?? '—' }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-muted-foreground">{{ t('Email') }}</span><span>{{ contractor.email ?? '—' }}</span></div>
                <div v-if="contractor.current_address" class="sm:col-span-2">
                    <span class="text-muted-foreground">{{ t('Current address') }}</span>
                    <p class="mt-1 whitespace-pre-wrap">{{ contractor.current_address }}</p>
                </div>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'agreements'">
            <CardHeader>
                <CardTitle>{{ t('Agreements') }}</CardTitle>
                <CardDescription>{{ t('Contractor service agreements') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="!contractor.agreements?.length" class="text-sm text-muted-foreground">{{ t('No agreements on file.') }}</div>
                <div v-else class="space-y-2">
                    <div v-for="agreement in contractor.agreements" :key="agreement.id" class="rounded-md border px-3 py-2 text-sm">
                        <div class="font-medium">{{ agreement.agreement_number ?? t('Agreement') }}</div>
                        <div class="text-muted-foreground">{{ formatDate(agreement.start_date) }} — {{ formatDate(agreement.end_date) }}</div>
                        <p v-if="agreement.notes" class="mt-1 text-muted-foreground">{{ agreement.notes }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'rates'">
            <CardHeader>
                <CardTitle>{{ t('Rates') }}</CardTitle>
                <CardDescription>{{ t('Project-specific billing rates') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="!contractor.rates?.length" class="text-sm text-muted-foreground">{{ t('No rates configured.') }}</div>
                <table v-else class="w-full text-sm">
                    <thead><tr class="border-b text-muted-foreground"><th class="pb-2 text-start">{{ t('Project') }}</th><th class="pb-2 text-start">{{ t('Daily') }}</th><th class="pb-2 text-start">{{ t('Monthly') }}</th><th class="pb-2 text-start">{{ t('Effective') }}</th></tr></thead>
                    <tbody>
                        <tr v-for="rate in contractor.rates" :key="rate.id" class="border-b last:border-0">
                            <td class="py-2">{{ rate.project?.code ?? t('General') }}</td>
                            <td class="py-2">{{ formatCurrency(rate.daily_rate, rate.currency ?? 'USD') }}</td>
                            <td class="py-2">{{ formatCurrency(rate.monthly_rate, rate.currency ?? 'USD') }}</td>
                            <td class="py-2 text-muted-foreground">{{ formatDate(rate.effective_from) }}</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'projects'">
            <CardHeader><CardTitle>{{ t('Project Assignments') }}</CardTitle></CardHeader>
            <CardContent>
                <div v-if="!deployments?.length" class="text-sm text-muted-foreground">{{ t('Not assigned to any project.') }}</div>
                <ul v-else class="divide-y">
                    <li v-for="d in deployments" :key="d.id" class="py-3">
                        <Link v-if="d.project" :href="`/projects/${d.project.id}`" class="font-medium hover:underline">{{ d.project.code }} — {{ d.project.name }}</Link>
                        <p v-if="d.role" class="text-sm text-muted-foreground">{{ d.role }}</p>
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
                v-if="attachmentTypes?.length"
                personnel-type="contractor"
                :personnel-id="contractor.id"
                :forms="contractor.personnel_attachments ?? []"
                :attachment-types="attachmentTypes"
                :can-manage="can('hr.create')"
            />
            <EntityAttachments :attachments="contractor.attachments ?? []" />
        </div>
    </div>
</template>
