<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Calendar,
    FileText,
    Mail,
    MapPin,
    Phone,
    User,
    UserRound,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
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
    status_change_logs?: StatusChangeLogRecord[];
}

const props = defineProps<{
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

type TabId = 'personal' | 'agreements' | 'projects' | 'attendance' | 'payroll' | 'documents';

const tabs = computed(() => [
    { id: 'personal' as const, label: t('Personal') },
    { id: 'agreements' as const, label: t('Agreements & Rates') },
    { id: 'projects' as const, label: t('Projects') },
    { id: 'attendance' as const, label: t('Attendance') },
    { id: 'payroll' as const, label: t('Payroll') },
    { id: 'documents' as const, label: t('Documents') },
]);

const activeTab = ref<TabId>('personal');

const fullName = computed(
    () => `${props.contractor.first_name} ${props.contractor.last_name}`,
);

const initials = computed(() => {
    const first = props.contractor.first_name.charAt(0);
    const last = props.contractor.last_name.charAt(0);

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

const formatCurrency = (value?: number | null, currency = 'USD'): string => {
    if (value == null) {
        return '—';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(value);
};

const monthName = (month: number): string =>
    new Intl.DateTimeFormat('en-US', { month: 'short' }).format(
        new Date(2000, month - 1, 1),
    );

const currentRate = computed(() => props.contractor.rates?.[0] ?? null);

const agreementStartDate = computed(
    () => props.contractor.agreements?.[0]?.start_date ?? null,
);
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
                                <p class="text-muted-foreground">
                                    {{ t('Contractor') }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge :variant="statusVariant(contractor.status)">
                                    {{ statusLabel(contractor.status) }}
                                </Badge>
                                <Badge variant="outline" class="gap-1">
                                    <UserRound class="size-3" />
                                    {{ t('Project-based') }}
                                </Badge>
                            </div>
                            <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                <span v-if="contractor.phone" class="inline-flex items-center gap-1.5">
                                    <Phone class="size-3.5" />
                                    {{ contractor.phone }}
                                </span>
                                <span v-if="contractor.email" class="inline-flex items-center gap-1.5">
                                    <Mail class="size-3.5" />
                                    {{ contractor.email }}
                                </span>
                                <span
                                    v-if="agreementStartDate"
                                    class="inline-flex items-center gap-1.5"
                                >
                                    <Calendar class="size-3.5" />
                                    {{ t('Since') }} {{ formatDate(agreementStartDate) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <PersonnelStatusButtons
                            :url="`/hr/contractors/${contractor.id}`"
                            :name="fullName"
                            :status="contractor.status"
                        />
                        <Button variant="outline" as-child>
                            <Link href="/hr/contractors">{{ t('Back to list') }}</Link>
                        </Button>
                        <Button v-if="can('hr.edit')" as-child>
                            <Link :href="`/hr/contractors/${contractor.id}/edit`">
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
                    <p class="mt-1 text-sm font-medium">{{ contractor.father_name ?? '—' }}</p>
                </div>
                <div class="rounded-lg border bg-muted/20 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t('Tazkira number') }}
                    </p>
                    <p class="mt-1 text-sm font-medium">{{ contractor.tazkira_number ?? '—' }}</p>
                </div>
                <div class="rounded-lg border bg-muted/20 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t('Date of birth') }}
                    </p>
                    <p class="mt-1 text-sm font-medium">{{ formatDate(contractor.date_of_birth) }}</p>
                </div>
                <div class="rounded-lg border bg-muted/20 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t('Gender') }}
                    </p>
                    <p class="mt-1 text-sm font-medium capitalize">{{ contractor.gender ?? '—' }}</p>
                </div>
                <div
                    v-if="contractor.current_address"
                    class="rounded-lg border bg-muted/20 px-4 py-3 sm:col-span-2"
                >
                    <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <MapPin class="size-3.5" />
                        {{ t('Current address') }}
                    </p>
                    <p class="mt-1 whitespace-pre-wrap text-sm">{{ contractor.current_address }}</p>
                </div>
            </CardContent>
        </Card>

        <div v-else-if="activeTab === 'agreements'" class="grid gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-1">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Wallet class="size-5" />
                        {{ t('Current Rates') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="!currentRate" class="text-sm text-muted-foreground">
                        {{ t('No rates configured.') }}
                    </div>
                    <div v-else class="space-y-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ t('Daily') }}
                            </p>
                            <p class="text-2xl font-bold tracking-tight">
                                {{ formatCurrency(currentRate.daily_rate, currentRate.currency ?? 'USD') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ t('Monthly') }}
                            </p>
                            <p class="text-2xl font-bold tracking-tight">
                                {{ formatCurrency(currentRate.monthly_rate, currentRate.currency ?? 'USD') }}
                            </p>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ currentRate.project?.code ?? t('General') }}
                            · {{ t('Effective from') }} {{ formatDate(currentRate.effective_from) }}
                        </p>
                    </div>
                    <div v-if="contractor.rates && contractor.rates.length > 1" class="mt-4 space-y-2 border-t pt-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            {{ t('Rate History') }}
                        </p>
                        <div
                            v-for="rate in contractor.rates.slice(1)"
                            :key="rate.id"
                            class="rounded-md border px-3 py-2 text-sm"
                        >
                            <p class="font-medium">{{ rate.project?.code ?? t('General') }}</p>
                            <p class="text-muted-foreground">
                                {{ formatCurrency(rate.daily_rate, rate.currency ?? 'USD') }}
                                / {{ formatCurrency(rate.monthly_rate, rate.currency ?? 'USD') }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="flex flex-col gap-4 lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="size-5" />
                            {{ t('Agreements') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="!contractor.agreements?.length"
                            class="rounded-lg border border-dashed px-4 py-8 text-center text-sm text-muted-foreground"
                        >
                            {{ t('No agreements on file.') }}
                        </div>
                        <div v-else class="grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="agreement in contractor.agreements"
                                :key="agreement.id"
                                class="rounded-lg border bg-muted/20 px-4 py-3"
                            >
                                <p class="font-medium">
                                    {{ agreement.agreement_number ?? t('Agreement') }}
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ formatDate(agreement.start_date) }} — {{ formatDate(agreement.end_date) }}
                                </p>
                                <p v-if="agreement.notes" class="mt-2 text-sm text-muted-foreground">
                                    {{ agreement.notes }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <StatusChangeHistory
                    :logs="contractor.status_change_logs ?? []"
                    :title="t('Employment Status History')"
                />
            </div>
        </div>

        <Card v-else-if="activeTab === 'projects'">
            <CardHeader><CardTitle>{{ t('Project Assignments') }}</CardTitle></CardHeader>
            <CardContent>
                <div v-if="!deployments?.length" class="text-sm text-muted-foreground">
                    {{ t('Not assigned to any project.') }}
                </div>
                <ul v-else class="divide-y">
                    <li
                        v-for="d in deployments"
                        :key="d.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div>
                            <Link
                                v-if="d.project"
                                :href="`/projects/${d.project.id}`"
                                class="font-medium hover:underline"
                            >
                                {{ d.project.code }} — {{ d.project.name }}
                            </Link>
                            <p v-if="d.role" class="text-sm text-muted-foreground">{{ d.role }}</p>
                        </div>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'attendance'">
            <CardHeader><CardTitle>{{ t('Attendance History') }}</CardTitle></CardHeader>
            <CardContent>
                <div v-if="!attendances?.length" class="text-sm text-muted-foreground">
                    {{ t('No attendance records.') }}
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-muted-foreground">
                            <th class="pb-2 text-start">{{ t('Period') }}</th>
                            <th class="pb-2 text-start">{{ t('Project') }}</th>
                            <th class="pb-2 text-start">{{ t('Present') }}</th>
                            <th class="pb-2 text-start">{{ t('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="a in attendances"
                            :key="a.id"
                            class="border-b last:border-0"
                        >
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
                <div v-if="!payrollAdjustments?.length" class="text-sm text-muted-foreground">
                    {{ t('No payroll adjustments.') }}
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-muted-foreground">
                            <th class="pb-2 text-start">{{ t('Period') }}</th>
                            <th class="pb-2 text-start">{{ t('Type') }}</th>
                            <th class="pb-2 text-start">{{ t('Project') }}</th>
                            <th class="pb-2 text-end">{{ t('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="adj in payrollAdjustments"
                            :key="adj.id"
                            class="border-b last:border-0"
                        >
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
