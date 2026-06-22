<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    AlertCircle,
    CalendarDays,
    CheckCircle2,
    Receipt,
    Users,
    Wallet,
} from '@lucide/vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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

import { useMisPage } from '@/composables/useMisPage';

interface Personnel {
    first_name?: string;
    last_name?: string;
}

interface PayrollItem {
    id: number;
    personnel_type: string;
    personnel_id: number;
    personnel?: Personnel | null;
    project?: { id: number; code: string; name?: string } | null;
    base_amount: number;
    bonus: number;
    deductions: number;
    advance: number;
    net_amount: number;
    currency?: string | null;
    notes?: string | null;
}

interface PayrollRun {
    id: number;
    period_year: number;
    period_month: number;
    status: string;
    processed_by?: { name: string } | null;
    items?: PayrollItem[];
    attachments?: EntityAttachment[];
}

interface PendingAdjustment {
    id: number;
    personnel_type: string;
    personnel_id: number;
    personnel?: Personnel | null;
    project?: { id: number; code: string; name?: string } | null;
    type: string;
    amount: number;
    notes?: string | null;
}

interface Props {
    payrollRun: PayrollRun;
    approvedAttendanceCount: number;
    pendingAdjustments: PendingAdjustment[];
}

const props = defineProps<Props>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Payroll', href: '/hr/payroll' },
            { title: 'Run details', href: '#' },
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

const personnelLabel = (item: PayrollItem): string => {
    if (item.personnel?.first_name || item.personnel?.last_name) {
        return [item.personnel.first_name, item.personnel.last_name]
            .filter(Boolean)
            .join(' ');
    }

    return `#${item.personnel_id}`;
};

const personnelTypeLabel = (type: string): string => {
    const parts = type.split('\\');

    return parts[parts.length - 1] ?? type;
};

const periodLabel = computed(
    () =>
        `${monthName(props.payrollRun.period_month)} ${props.payrollRun.period_year}`,
);

const itemCount = computed(() => props.payrollRun.items?.length ?? 0);

const totalNet = computed(() =>
    (props.payrollRun.items ?? []).reduce(
        (sum, item) => sum + Number(item.net_amount),
        0,
    ),
);

const totalBonus = computed(() =>
    (props.payrollRun.items ?? []).reduce(
        (sum, item) => sum + Number(item.bonus ?? 0),
        0,
    ),
);

const totalDeductions = computed(() =>
    (props.payrollRun.items ?? []).reduce(
        (sum, item) => sum + Number(item.deductions ?? 0),
        0,
    ),
);

const totalAdvance = computed(() =>
    (props.payrollRun.items ?? []).reduce(
        (sum, item) => sum + Number(item.advance ?? 0),
        0,
    ),
);

const isDraft = computed(() => props.payrollRun.status !== 'processed');

const isProcessed = computed(() => props.payrollRun.status === 'processed');

const canEditItems = computed(() => itemCount.value > 0 && isProcessed.value);

const pendingAdjustmentCount = computed(() => props.pendingAdjustments.length);

const attendanceFilterUrl = computed(
    () =>
        `/hr/attendance?year=${props.payrollRun.period_year}&month=${props.payrollRun.period_month}`,
);

const adjustmentsUrl = computed(
    () =>
        `/hr/payroll-adjustments?year=${props.payrollRun.period_year}&month=${props.payrollRun.period_month}`,
);

const adjustmentTypeLabel = (type: string): string => {
    switch (type) {
        case 'bonus':
            return t('Bonus');
        case 'deduction':
            return t('Deduction');
        case 'advance':
            return t('Advance');
        default:
            return type;
    }
};

const pendingPersonnelLabel = (adjustment: PendingAdjustment): string => {
    if (adjustment.personnel?.first_name || adjustment.personnel?.last_name) {
        return [adjustment.personnel.first_name, adjustment.personnel.last_name]
            .filter(Boolean)
            .join(' ');
    }

    return `#${adjustment.personnel_id}`;
};
</script>

<template>
    <Head :title="`${periodLabel} ${t('Payroll')}`" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Heading
                    :title="periodLabel"
                    :description="
                        t('Review payroll line items generated from approved attendance')
                    "
                />
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <Badge :variant="isProcessed ? 'default' : 'secondary'">
                        {{ payrollRun.status }}
                    </Badge>
                    <span v-if="isProcessed" class="text-sm text-muted-foreground">
                        {{ t('Processed by') }} {{ payrollRun.processed_by?.name ?? '—' }}
                    </span>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link href="/hr/payroll">{{ t('Back to list') }}</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="attendanceFilterUrl">{{ t('View attendance') }}</Link>
                </Button>
                <Button v-if="isDraft" variant="outline" as-child>
                    <Link :href="adjustmentsUrl">{{ t('Manage adjustments') }}</Link>
                </Button>
                <Form
                    v-if="isDraft"
                    :action="`/hr/payroll/${payrollRun.id}/process`"
                    method="post"
                    :options="{ preserveScroll: true }"
                    v-slot="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        {{ t('Process payroll') }}
                    </Button>
                </Form>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('Approved attendance') }}</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-2xl">
                        <CalendarDays class="size-5 text-muted-foreground" />
                        {{ approvedAttendanceCount }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    {{
                        t('Records for :period ready for payroll', {
                            period: periodLabel,
                        })
                    }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('Line items') }}</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-2xl">
                        <Users class="size-5 text-muted-foreground" />
                        {{ itemCount }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    {{ t('Personnel entries in this payroll run') }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('Total net pay') }}</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-2xl">
                        <Wallet class="size-5 text-muted-foreground" />
                        {{ formatCurrency(totalNet) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    {{ t('Combined net amount for this run') }}
                </CardContent>
            </Card>
        </div>

        <Card
            v-if="isDraft"
            class="border-dashed bg-muted/20"
        >
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Receipt class="size-5" />
                    {{ t('Pending adjustments') }} ({{ pendingAdjustmentCount }})
                </CardTitle>
                <CardDescription>
                    {{
                        t(
                            'Bonus, deductions, and advances recorded for :period are applied automatically when you process this run.',
                            { period: periodLabel },
                        )
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-if="pendingAdjustmentCount === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{
                        t(
                            'No adjustments recorded for this month yet. Add advances, bonuses, or deductions before processing payroll.',
                        )
                    }}
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-3 pr-4 font-medium">{{ t('Personnel') }}</th>
                                <th class="pb-3 pr-4 font-medium">{{ t('Project') }}</th>
                                <th class="pb-3 pr-4 font-medium">{{ t('Type') }}</th>
                                <th class="pb-3 pr-4 text-right font-medium">{{ t('Amount') }}</th>
                                <th class="pb-3 font-medium">{{ t('Notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="adjustment in pendingAdjustments"
                                :key="adjustment.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">{{ pendingPersonnelLabel(adjustment) }}</td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ adjustment.project?.code ?? '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    {{ adjustmentTypeLabel(adjustment.type) }}
                                </td>
                                <td
                                    class="py-3 pr-4 text-right font-medium"
                                    :class="
                                        adjustment.type === 'bonus'
                                            ? 'text-green-700 dark:text-green-400'
                                            : 'text-destructive'
                                    "
                                >
                                    {{ formatCurrency(adjustment.amount) }}
                                </td>
                                <td class="py-3 text-muted-foreground">
                                    {{ adjustment.notes ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Button variant="outline" size="sm" class="w-fit" as-child>
                    <Link :href="adjustmentsUrl">{{ t('Add or edit adjustments') }}</Link>
                </Button>
            </CardContent>
        </Card>

        <Card
            v-if="isDraft"
            class="border-dashed bg-muted/20"
        >
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <AlertCircle class="size-5" />
                    {{ t('Next step') }}
                </CardTitle>
                <CardDescription>
                    {{
                        t(
                            'Approve attendance, record any bonus/deduction/advance for :period, then process this run.',
                            { period: periodLabel },
                        )
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="text-sm text-muted-foreground">
                <p>
                    {{ t('You currently have') }}
                    <strong class="text-foreground">{{ approvedAttendanceCount }}</strong>
                    {{ t('approved attendance') }}
                    {{
                        approvedAttendanceCount === 1 ? t('record') : t('records')
                    }}
                    {{ t('and') }}
                    <strong class="text-foreground">{{ pendingAdjustmentCount }}</strong>
                    {{ t('pending') }}
                    {{
                        pendingAdjustmentCount === 1
                            ? t('adjustment')
                            : t('adjustments')
                    }}.
                    {{ t('Click') }}
                    <strong class="text-foreground">{{ t('Process payroll') }}</strong>
                    {{ t('when you are ready to calculate amounts.') }}
                </p>
            </CardContent>
        </Card>

        <Card
            v-else-if="itemCount === 0"
            class="border-dashed bg-muted/20"
        >
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <AlertCircle class="size-5" />
                    {{ t('No line items generated') }}
                </CardTitle>
                <CardDescription>
                    {{
                        t(
                            'This run was processed, but no approved attendance existed for :period.',
                            { period: periodLabel },
                        )
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="flex flex-col gap-3 text-sm text-muted-foreground">
                <p>
                    {{
                        t(
                            'Record attendance, approve it, then create a new payroll run or re-process if your workflow allows it.',
                        )
                    }}
                </p>
                <Button variant="outline" size="sm" class="w-fit" as-child>
                    <Link :href="attendanceFilterUrl">{{ t('Go to attendance') }}</Link>
                </Button>
            </CardContent>
        </Card>

        <Card v-else-if="isProcessed">
            <CardHeader class="pb-2">
                <CardTitle class="flex items-center gap-2 text-base text-green-700 dark:text-green-400">
                    <CheckCircle2 class="size-5" />
                    {{ t('Payroll processed') }}
                </CardTitle>
                <CardDescription>
                    {{
                        t(':count line items · Total net :amount', {
                            count: String(itemCount),
                            amount: formatCurrency(totalNet),
                        })
                    }}
                </CardDescription>
            </CardHeader>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Wallet class="size-5" />
                    {{ t('Line items') }}
                </CardTitle>
                <CardDescription>
                    {{
                        isDraft
                            ? t(
                                  'Line items appear after processing; amounts include pending adjustments for this month',
                              )
                            : t(
                                  'Amounts from attendance; net = base + bonus − deductions − advance',
                              )
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="itemCount === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    {{
                        isDraft
                            ? t(
                                  'Line items will appear here after you process this run.',
                              )
                            : t(
                                  'No payroll amounts were calculated for this period.',
                              )
                    }}
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-if="canEditItems"
                        class="flex flex-wrap gap-4 text-xs text-muted-foreground"
                    >
                        <span>{{ t('Bonus total:') }} {{ formatCurrency(totalBonus) }}</span>
                        <span>{{ t('Deductions total:') }} {{ formatCurrency(totalDeductions) }}</span>
                        <span>{{ t('Advance total:') }} {{ formatCurrency(totalAdvance) }}</span>
                    </div>
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-3 pr-4 font-medium">{{ t('Personnel') }}</th>
                                <th class="pb-3 pr-4 font-medium">{{ t('Type') }}</th>
                                <th class="pb-3 pr-4 font-medium">{{ t('Project') }}</th>
                                <th class="pb-3 pr-4 text-right font-medium">
                                    {{ t('Base') }}
                                </th>
                                <th class="pb-3 pr-4 text-right font-medium">
                                    {{ t('Bonus') }}
                                </th>
                                <th class="pb-3 pr-4 text-right font-medium">
                                    {{ t('Deductions') }}
                                </th>
                                <th class="pb-3 pr-4 text-right font-medium">
                                    {{ t('Advance') }}
                                </th>
                                <th class="pb-3 pr-4 text-right font-medium">{{ t('Net') }}</th>
                                <th
                                    v-if="canEditItems"
                                    class="pb-3 text-right font-medium"
                                >
                                    {{ t('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in payrollRun.items"
                                :key="item.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">
                                    <div>{{ personnelLabel(item) }}</div>
                                    <p
                                        v-if="item.notes && !canEditItems"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ item.notes }}
                                    </p>
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ personnelTypeLabel(item.personnel_type) }}
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ item.project?.code ?? '—' }}
                                </td>
                                <td class="py-3 pr-4 text-right">
                                    {{
                                        formatCurrency(
                                            item.base_amount,
                                            item.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                                <Form
                                    v-if="canEditItems"
                                    :action="`/hr/payroll/${payrollRun.id}/items/${item.id}`"
                                    method="put"
                                    class="contents"
                                    :options="{ preserveScroll: true }"
                                    v-slot="{ errors, processing }"
                                >
                                    <td class="py-3 pr-2 align-top">
                                        <Input
                                            name="bonus"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="h-8 w-24 text-right"
                                            :default-value="item.bonus ?? 0"
                                        />
                                        <InputError :message="errors.bonus" />
                                    </td>
                                    <td class="py-3 pr-2 align-top">
                                        <Input
                                            name="deductions"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="h-8 w-24 text-right"
                                            :default-value="item.deductions ?? 0"
                                        />
                                        <InputError :message="errors.deductions" />
                                    </td>
                                    <td class="py-3 pr-2 align-top">
                                        <Input
                                            name="advance"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="h-8 w-24 text-right"
                                            :default-value="item.advance ?? 0"
                                        />
                                        <InputError :message="errors.advance" />
                                    </td>
                                    <td class="py-3 pr-2 align-top text-right font-medium">
                                        {{
                                            formatCurrency(
                                                item.net_amount,
                                                item.currency ?? 'USD',
                                            )
                                        }}
                                    </td>
                                    <td class="py-3 align-top">
                                        <div class="flex flex-col gap-1">
                                            <Input
                                                name="notes"
                                                :placeholder="t('Notes')"
                                                class="h-8 min-w-32"
                                                :default-value="item.notes ?? ''"
                                            />
                                            <InputError :message="errors.notes" />
                                            <Button
                                                type="submit"
                                                size="sm"
                                                variant="outline"
                                                class="w-fit self-end"
                                                :disabled="processing"
                                            >
                                                {{ t('Save') }}
                                            </Button>
                                        </div>
                                    </td>
                                </Form>
                                <template v-else>
                                    <td class="py-3 pr-4 text-right text-green-700 dark:text-green-400">
                                        {{
                                            formatCurrency(
                                                item.bonus ?? 0,
                                                item.currency ?? 'USD',
                                            )
                                        }}
                                    </td>
                                    <td class="py-3 pr-4 text-right text-destructive">
                                        {{
                                            formatCurrency(
                                                item.deductions,
                                                item.currency ?? 'USD',
                                            )
                                        }}
                                    </td>
                                    <td class="py-3 pr-4 text-right text-destructive">
                                        {{
                                            formatCurrency(
                                                item.advance ?? 0,
                                                item.currency ?? 'USD',
                                            )
                                        }}
                                    </td>
                                    <td class="py-3 text-right font-medium">
                                        {{
                                            formatCurrency(
                                                item.net_amount,
                                                item.currency ?? 'USD',
                                            )
                                        }}
                                    </td>
                                </template>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </CardContent>
        </Card>

        <EntityAttachments
            v-if="payrollRun.attachments?.length"
            :attachments="payrollRun.attachments"
        />
    </div>
</template>
