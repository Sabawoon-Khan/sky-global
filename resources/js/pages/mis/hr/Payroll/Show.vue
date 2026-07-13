<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    CalendarDays,
    CheckCircle2,
    Printer,
    Receipt,
    UserRound,
    Users,
    Wallet,
} from '@lucide/vue';
import Can from '@/components/Can.vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { cn } from '@/lib/utils';

interface Personnel {
    first_name?: string;
    last_name?: string;
}

interface AttendanceSummary {
    days_present: number;
    days_absent: number;
    days_sick_leave: number;
    days_annual_leave: number;
    days_casual_leave: number;
    days_other: number;
}

interface PayrollItem {
    id: number;
    personnel_type: string;
    personnel_id: number;
    personnel?: Personnel | null;
    project?: { id: number; code: string; name?: string } | null;
    attendance?: AttendanceSummary | null;
    base_amount: number | string;
    bonus: number | string;
    deductions: number | string;
    advance: number | string;
    net_amount: number | string;
    currency?: string | null;
}

interface PayrollRun {
    id: number;
    title: string;
    payroll_type: 'general' | 'project';
    project?: { id: number; code: string; name?: string } | null;
    date_from: string;
    date_to: string;
    period_year: number;
    period_month: number;
    status: string;
    processed_by?: { name: string } | null;
    items?: PayrollItem[];
    attachments?: EntityAttachment[];
}

interface Props {
    payrollRun: PayrollRun;
}

const props = defineProps<Props>();

const { t, deleteAction } = useMisPage();

const EMPLOYEE_TYPE = 'App\\Models\\Hr\\Employee';
const CONTRACTOR_TYPE = 'App\\Models\\Hr\\Contractor';

const activeTab = ref<'employees' | 'contractors'>('employees');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Payroll', href: '/hr/payroll' },
            { title: 'Run details', href: '#' },
        ],
    },
});

const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );

const amount = (value?: number | string | null): string =>
    formatAfn(value == null ? null : Number(value));

const dayCount = (
    item: PayrollItem,
    field: keyof AttendanceSummary,
): number => item.attendance?.[field] ?? 0;

const personnelLabel = (item: PayrollItem): string => {
    if (item.personnel?.first_name || item.personnel?.last_name) {
        return [item.personnel.first_name, item.personnel.last_name]
            .filter(Boolean)
            .join(' ');
    }

    return `#${item.personnel_id}`;
};

const allItems = computed(() => props.payrollRun.items ?? []);

const employeeItems = computed(() =>
    allItems.value.filter((item) => item.personnel_type === EMPLOYEE_TYPE),
);

const contractorItems = computed(() =>
    allItems.value.filter((item) => item.personnel_type === CONTRACTOR_TYPE),
);

const activeItems = computed(() =>
    activeTab.value === 'employees'
        ? employeeItems.value
        : contractorItems.value,
);

const sumField = (
    items: PayrollItem[],
    field: keyof Pick<
        PayrollItem,
        'base_amount' | 'bonus' | 'deductions' | 'advance' | 'net_amount'
    >,
): number =>
    items.reduce((sum, item) => sum + Number(item[field] ?? 0), 0);

const tabTotals = computed(() => ({
    base: sumField(activeItems.value, 'base_amount'),
    bonus: sumField(activeItems.value, 'bonus'),
    deductions: sumField(activeItems.value, 'deductions'),
    advance: sumField(activeItems.value, 'advance'),
    net: sumField(activeItems.value, 'net_amount'),
}));

const itemCount = computed(() => allItems.value.length);

const totalNet = computed(() => sumField(allItems.value, 'net_amount'));

const totalDeductions = computed(() =>
    sumField(allItems.value, 'deductions'),
);

const adjustmentsUrl = computed(
    () =>
        `/hr/payroll-adjustments?year=${props.payrollRun.period_year}&month=${props.payrollRun.period_month}`,
);

const printUrl = computed(
    () => `/hr/payroll/${props.payrollRun.id}/print?autoprint=1`,
);

const runActions = computed((): RowActionItem[] => [
    deleteAction(
        {
            href: `/hr/payroll/${props.payrollRun.id}`,
            title: t('Delete payroll run?'),
            description: t(
                'This payroll run and all its line items will be removed.',
            ),
        },
        'hr.delete',
    ),
]);
</script>

<template>
    <Head :title="payrollRun.title" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ payrollRun.title }}
                </h1>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                    <Badge variant="default">{{ payrollRun.status }}</Badge>
                    <span>{{ formatDate(payrollRun.date_from) }} — {{ formatDate(payrollRun.date_to) }}</span>
                    <span v-if="payrollRun.project">{{ payrollRun.project.code }}</span>
                </div>
                <p v-if="payrollRun.processed_by" class="mt-1 text-sm text-muted-foreground">
                    {{ t('Generated by') }} {{ payrollRun.processed_by.name }}
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <Button v-if="isProcessed" variant="outline" as-child>
                    <a
                        :href="`/hr/payroll/${payrollRun.id}/print?autoprint=1`"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <Printer class="size-4" />
                        {{ t('Print') }}
                    </a>
                </Button>
                <Button variant="outline" as-child>
                    <Link href="/hr/payroll">{{ t('Back to list') }}</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="adjustmentsUrl">
                        <Receipt class="size-4" />
                        {{ t('Adjustments') }}
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <a :href="printUrl" target="_blank" rel="noopener noreferrer">
                        <Printer class="size-4" />
                        {{ t('Print') }}
                    </a>
                </Button>
                <Form
                    :action="`/hr/payroll/${payrollRun.id}/process`"
                    method="post"
                    :options="{ preserveScroll: true }"
                    v-slot="{ processing }"
                >
                    <Button type="submit" variant="outline" :disabled="processing">
                        {{ t('Regenerate') }}
                    </Button>
                </Form>
                <Can permission="hr.delete">
                    <RowActionsMenu :actions="runActions" />
                </Can>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-2xl">
                        <Users class="size-5 text-muted-foreground" />
                        {{ itemCount }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    {{ employeeItems.length }} {{ t('employees') }},
                    {{ contractorItems.length }} {{ t('contractors') }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-2xl">
                        <CalendarDays class="size-5 text-muted-foreground" />
                        {{ amount(totalDeductions) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    {{ t('Total deductions') }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-2xl">
                        <Wallet class="size-5 text-muted-foreground" />
                        {{ amount(totalNet) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    {{ t('Combined net pay') }}
                </CardContent>
            </Card>
        </div>

        <Card v-if="itemCount > 0">
            <CardHeader class="pb-2">
                <CardTitle class="flex items-center gap-2 text-base text-green-700 dark:text-green-400">
                    <CheckCircle2 class="size-5" />
                    {{ t('Payroll generated from attendance') }}
                </CardTitle>
            </CardHeader>
            <CardContent class="text-sm text-muted-foreground">
                {{
                    t(
                        'To change amounts, add payroll adjustments then click Regenerate.',
                    )
                }}
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <CardTitle class="flex items-center gap-2">
                        <Wallet class="size-5" />
                        {{ t('Line items') }}
                    </CardTitle>
                    <div class="inline-flex rounded-lg border bg-muted/30 p-1">
                        <button
                            type="button"
                            :class="
                                cn(
                                    'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                    activeTab === 'employees'
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground',
                                )
                            "
                            @click="activeTab = 'employees'"
                        >
                            <UserRound class="size-4" />
                            {{ t('Employees') }}
                            <span class="rounded bg-muted px-1.5 py-0.5 text-xs tabular-nums">
                                {{ employeeItems.length }}
                            </span>
                        </button>
                        <button
                            type="button"
                            :class="
                                cn(
                                    'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                    activeTab === 'contractors'
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground',
                                )
                            "
                            @click="activeTab = 'contractors'"
                        >
                            <Users class="size-4" />
                            {{ t('Contractors') }}
                            <span class="rounded bg-muted px-1.5 py-0.5 text-xs tabular-nums">
                                {{ contractorItems.length }}
                            </span>
                        </button>
                    </div>
                </div>
                <div
                    v-if="activeItems.length > 0"
                    class="flex flex-wrap gap-4 text-sm text-muted-foreground"
                >
                    <span>{{ t('Base') }}: <strong class="text-foreground">{{ amount(tabTotals.base) }}</strong></span>
                    <span>{{ t('Bonus') }}: <strong class="text-foreground">{{ amount(tabTotals.bonus) }}</strong></span>
                    <span>{{ t('Deductions') }}: <strong class="text-foreground">{{ amount(tabTotals.deductions) }}</strong></span>
                    <span>{{ t('Advance') }}: <strong class="text-foreground">{{ amount(tabTotals.advance) }}</strong></span>
                    <span>{{ t('Net') }}: <strong class="text-base text-foreground">{{ amount(tabTotals.net) }}</strong></span>
                </div>
            </CardHeader>
            <CardContent>
                <div
                    v-if="itemCount === 0"
                    class="ui-empty-state"
                >
                    {{ t('No attendance found for this period. Record attendance first, then regenerate.') }}
                </div>
                <div
                    v-else-if="activeItems.length === 0"
                    class="rounded-xl border border-dashed bg-muted/10 px-4 py-10 text-center text-sm text-muted-foreground"
                >
                    {{
                        activeTab === 'employees'
                            ? t('No employees in this payroll run.')
                            : t('No contractors in this payroll run.')
                    }}
                </div>
                <div v-else class="overflow-x-auto rounded-xl border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="px-3 py-3 text-start font-semibold">#</th>
                                <th class="px-3 py-3 text-start font-semibold">{{ t('Personnel') }}</th>
                                <th class="px-2 py-3 text-center font-semibold">{{ t('Present') }}</th>
                                <th class="px-2 py-3 text-center font-semibold">{{ t('Absent') }}</th>
                                <th class="px-2 py-3 text-center font-semibold">{{ t('Sick') }}</th>
                                <th class="px-2 py-3 text-center font-semibold">{{ t('Annual') }}</th>
                                <th class="px-2 py-3 text-center font-semibold">{{ t('Casual') }}</th>
                                <th class="px-2 py-3 text-center font-semibold">{{ t('Other') }}</th>
                                <th class="px-3 py-3 text-end font-semibold">{{ t('Base') }}</th>
                                <th class="px-3 py-3 text-end font-semibold">{{ t('Bonus') }}</th>
                                <th class="px-3 py-3 text-end font-semibold">{{ t('Deductions') }}</th>
                                <th class="px-3 py-3 text-end font-semibold">{{ t('Advance') }}</th>
                                <th class="px-3 py-3 text-end font-semibold">{{ t('Net') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in activeItems"
                                :key="item.id"
                                class="border-b last:border-0 hover:bg-muted/20"
                            >
                                <td class="px-3 py-3 text-muted-foreground tabular-nums">
                                    {{ index + 1 }}
                                </td>
                                <td class="px-3 py-3 font-medium">
                                    {{ personnelLabel(item) }}
                                </td>
                                <td class="px-2 py-3 text-center tabular-nums">{{ dayCount(item, 'days_present') }}</td>
                                <td class="px-2 py-3 text-center tabular-nums text-destructive">{{ dayCount(item, 'days_absent') }}</td>
                                <td class="px-2 py-3 text-center tabular-nums">{{ dayCount(item, 'days_sick_leave') }}</td>
                                <td class="px-2 py-3 text-center tabular-nums">{{ dayCount(item, 'days_annual_leave') }}</td>
                                <td class="px-2 py-3 text-center tabular-nums">{{ dayCount(item, 'days_casual_leave') }}</td>
                                <td class="px-2 py-3 text-center tabular-nums">{{ dayCount(item, 'days_other') }}</td>
                                <td class="px-3 py-3 text-end text-base tabular-nums">{{ amount(item.base_amount) }}</td>
                                <td class="px-3 py-3 text-end text-base tabular-nums text-green-700 dark:text-green-400">{{ amount(item.bonus) }}</td>
                                <td class="px-3 py-3 text-end text-base tabular-nums text-destructive">{{ amount(item.deductions) }}</td>
                                <td class="px-3 py-3 text-end text-base tabular-nums text-destructive">{{ amount(item.advance) }}</td>
                                <td class="px-3 py-3 text-end text-base font-semibold tabular-nums">{{ amount(item.net_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <EntityAttachments
            v-if="payrollRun.attachments?.length"
            :attachments="payrollRun.attachments"
        />
    </div>
</template>
