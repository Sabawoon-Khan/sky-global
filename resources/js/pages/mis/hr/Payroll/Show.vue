<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ArrowLeft,
    CheckCircle2,
    Percent,
    Printer,
    Receipt,
    RefreshCw,
    Trash2,
    UserRound,
    Users,
    Wallet,
} from '@lucide/vue';
import Can from '@/components/Can.vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
import MisTabs from '@/components/MisTabs.vue';
import PayrollPrintPersonnelPicker from '@/components/PayrollPrintPersonnelPicker.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    V2DetailHero,
    V2ListPage,
    V2Panel,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn } from '@/lib/format';

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
    tax: number | string;
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

const props = defineProps<{
    payrollRun: PayrollRun;
}>();

const { t } = useMisPage();

const EMPLOYEE_TYPE = 'App\\Models\\Hr\\Employee';
const CONTRACTOR_TYPE = 'App\\Models\\Hr\\Contractor';

const activeTab = ref('employees');

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

const tabs = computed(() => [
    {
        id: 'employees',
        label: `${t('Employees')} (${employeeItems.value.length})`,
        icon: UserRound,
    },
    {
        id: 'contractors',
        label: `${t('Contractors')} (${contractorItems.value.length})`,
        icon: Users,
    },
]);

const sumField = (
    items: PayrollItem[],
    field: keyof Pick<
        PayrollItem,
        'base_amount' | 'bonus' | 'deductions' | 'tax' | 'advance' | 'net_amount'
    >,
): number =>
    items.reduce((sum, item) => sum + Number(item[field] ?? 0), 0);

const tabTotals = computed(() => ({
    base: sumField(activeItems.value, 'base_amount'),
    bonus: sumField(activeItems.value, 'bonus'),
    deductions: sumField(activeItems.value, 'deductions'),
    tax: sumField(activeItems.value, 'tax'),
    advance: sumField(activeItems.value, 'advance'),
    net: sumField(activeItems.value, 'net_amount'),
}));

const itemCount = computed(() => allItems.value.length);
const totalNet = computed(() => sumField(allItems.value, 'net_amount'));
const totalDeductions = computed(() =>
    sumField(allItems.value, 'deductions'),
);
const totalTax = computed(() => sumField(allItems.value, 'tax'));
const totalBase = computed(() => sumField(allItems.value, 'base_amount'));

const isProcessed = computed(
    () => props.payrollRun.status === 'processed',
);

const typeLabel = computed(() =>
    props.payrollRun.payroll_type === 'project'
        ? t('Project payroll')
        : t('General payroll'),
);

const taxBrackets = [
    { range: '0 – 5,000 AFN', rule: '0%' },
    { range: '5,000 – 12,500 AFN', rule: '2% of amount over 5,000' },
    {
        range: '12,500 – 100,000 AFN',
        rule: '150 AFN + 10% of amount over 12,500',
    },
    {
        range: 'Over 100,000 AFN',
        rule: '8,900 AFN + 20% of amount over 100,000',
    },
] as const;

const adjustmentsUrl = computed(
    () =>
        `/hr/payroll-adjustments?year=${props.payrollRun.period_year}&month=${props.payrollRun.period_month}`,
);

const printDialogOpen = ref(false);
const printSelectedIds = ref<number[]>([]);

const printEmployees = computed(() =>
    employeeItems.value.map((item) => ({
        id: item.id,
        name: personnelLabel(item),
    })),
);

const printContractors = computed(() =>
    contractorItems.value.map((item) => ({
        id: item.id,
        name: personnelLabel(item),
    })),
);

const allPrintIds = computed(() => [
    ...printEmployees.value.map((person) => person.id),
    ...printContractors.value.map((person) => person.id),
]);

function openPrintDialog(): void {
    printSelectedIds.value = [...allPrintIds.value];
    printDialogOpen.value = true;
}

function confirmPrint(): void {
    if (printSelectedIds.value.length === 0) {
        return;
    }

    const params = new URLSearchParams();
    const selected = new Set(printSelectedIds.value);
    const printingAll =
        allPrintIds.value.length > 0 &&
        allPrintIds.value.every((id) => selected.has(id)) &&
        selected.size === allPrintIds.value.length;

    if (!printingAll) {
        params.set('items', printSelectedIds.value.join(','));
    }

    params.set('autoprint', '1');

    window.open(
        `/hr/payroll/${props.payrollRun.id}/print?${params.toString()}`,
        '_blank',
        'noopener,noreferrer',
    );
    printDialogOpen.value = false;
}

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'personnel', label: t('Personnel') },
    { key: 'present', label: t('Present') },
    { key: 'absent', label: t('Absent') },
    { key: 'base', label: t('Base') },
    { key: 'bonus', label: t('Bonus') },
    { key: 'deductions', label: t('Deductions') },
    { key: 'tax', label: t('Tax') },
    { key: 'advance', label: t('Advance') },
    { key: 'net', label: t('Net') },
]);

function confirmDelete(): void {
    if (
        !window.confirm(
            t('This payroll run and all its line items will be removed.'),
        )
    ) {
        return;
    }

    router.delete(`/hr/payroll/${props.payrollRun.id}`);
}
</script>

<template>
    <Head :title="payrollRun.title" />

    <V2ListPage>
        <V2DetailHero image="/images/gs-hero-people.png" priority>
            <template #eyebrow>{{ t('Payroll') }}</template>
            <template #title>{{ payrollRun.title }}</template>
            <template #description>
                {{ formatDate(payrollRun.date_from) }} —
                {{ formatDate(payrollRun.date_to) }}
                <span v-if="payrollRun.project">
                    · {{ payrollRun.project.code }}
                    <span v-if="payrollRun.project.name">
                        {{ payrollRun.project.name }}
                    </span>
                </span>
                <span v-if="payrollRun.processed_by">
                    · {{ t('Generated by') }}
                    {{ payrollRun.processed_by.name }}
                </span>
            </template>

            <template #actions>
                <div class="mb-2 flex flex-wrap items-center justify-end gap-2">
                    <Badge variant="outline">{{ typeLabel }}</Badge>
                    <Badge :variant="isProcessed ? 'default' : 'secondary'">
                        {{ payrollRun.status }}
                    </Badge>
                </div>

                <div class="detail-action-group" role="group" :aria-label="t('Actions')">
                    <Link href="/hr/payroll" class="detail-btn">
                        <ArrowLeft />
                        {{ t('Back') }}
                    </Link>

                    <Link :href="adjustmentsUrl" class="detail-btn">
                        <Receipt />
                        {{ t('Adjustments') }}
                    </Link>

                    <button
                        type="button"
                        class="detail-btn"
                        :disabled="itemCount === 0"
                        @click="openPrintDialog"
                    >
                        <Printer />
                        {{ t('Print') }}
                    </button>

                    <Form
                        :action="`/hr/payroll/${payrollRun.id}/process`"
                        method="post"
                        :options="{ preserveScroll: true }"
                        v-slot="{ processing }"
                    >
                        <button
                            type="submit"
                            class="detail-btn primary"
                            :disabled="processing"
                        >
                            <RefreshCw />
                            {{ t('Regenerate') }}
                        </button>
                    </Form>

                    <Can permission="hr.delete">
                        <button
                            type="button"
                            class="detail-btn danger"
                            @click="confirmDelete"
                        >
                            <Trash2 />
                            {{ t('Delete') }}
                        </button>
                    </Can>
                </div>
            </template>

            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :title="t('Personnel')"
                        :value="String(itemCount)"
                    >
                        <template #icon><Users /></template>
                    </V2StatCard>
                    <V2StatCard
                        icon-tone="warm"
                        :title="t('Gross base')"
                        :value="amount(totalBase)"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                    <V2StatCard
                        icon-tone="orange"
                        accent
                        :title="t('Total wage tax')"
                        :value="amount(totalTax)"
                    >
                        <template #icon><Percent /></template>
                    </V2StatCard>
                    <V2StatCard
                        icon-tone="teal"
                        :title="t('Combined net pay')"
                        :value="amount(totalNet)"
                    >
                        <template #icon><CheckCircle2 /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2DetailHero>

        <div class="grid gap-4 lg:grid-cols-5">
            <V2Panel
                class="lg:col-span-3"
                :title="t('Afghanistan wage tax')"
                :description="
                    t(
                        'Wage withholding tax is calculated on taxable pay (base + bonus) using Afghanistan Income Tax Law monthly brackets.',
                    )
                "
            >
                <div class="overflow-x-auto rounded-xl border border-border/70">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="px-3 py-2 text-start font-semibold">
                                    {{ t('Monthly income') }}
                                </th>
                                <th class="px-3 py-2 text-start font-semibold">
                                    {{ t('Withholding') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="bracket in taxBrackets"
                                :key="bracket.range"
                                class="border-b last:border-0"
                            >
                                <td class="px-3 py-2.5 tabular-nums">
                                    {{ bracket.range }}
                                </td>
                                <td class="px-3 py-2.5 text-muted-foreground">
                                    {{ bracket.rule }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </V2Panel>

            <V2Panel class="lg:col-span-2" :title="t('Run totals')">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted-foreground">{{ t('Deductions') }}</dt>
                        <dd class="font-semibold tabular-nums">
                            {{ amount(totalDeductions) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted-foreground">{{ t('Tax withheld this run') }}</dt>
                        <dd class="font-semibold tabular-nums">
                            {{ amount(totalTax) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-border/70 pt-3">
                        <dt class="font-medium">{{ t('Combined net pay') }}</dt>
                        <dd class="text-lg font-semibold tabular-nums text-primary">
                            {{ amount(totalNet) }}
                        </dd>
                    </div>
                </dl>

                <p
                    v-if="itemCount > 0"
                    class="mt-4 rounded-xl border border-border/70 bg-muted/20 px-3 py-2.5 text-xs text-muted-foreground"
                >
                    <CheckCircle2 class="me-1 inline size-3.5 text-primary" />
                    {{
                        t(
                            'To change amounts, add payroll adjustments then click Regenerate.',
                        )
                    }}
                </p>
            </V2Panel>
        </div>

        <MisTabs v-model="activeTab" :tabs="tabs" />

        <div
            v-if="activeItems.length > 0"
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6"
        >
            <V2Panel>
                <p class="text-xs text-muted-foreground">{{ t('Base') }}</p>
                <p class="mt-1 font-semibold tabular-nums">{{ amount(tabTotals.base) }}</p>
            </V2Panel>
            <V2Panel>
                <p class="text-xs text-muted-foreground">{{ t('Bonus') }}</p>
                <p class="mt-1 font-semibold tabular-nums">{{ amount(tabTotals.bonus) }}</p>
            </V2Panel>
            <V2Panel>
                <p class="text-xs text-muted-foreground">{{ t('Deductions') }}</p>
                <p class="mt-1 font-semibold tabular-nums">{{ amount(tabTotals.deductions) }}</p>
            </V2Panel>
            <V2Panel>
                <p class="text-xs text-muted-foreground">{{ t('Tax') }}</p>
                <p class="mt-1 font-semibold tabular-nums">{{ amount(tabTotals.tax) }}</p>
            </V2Panel>
            <V2Panel>
                <p class="text-xs text-muted-foreground">{{ t('Advance') }}</p>
                <p class="mt-1 font-semibold tabular-nums">{{ amount(tabTotals.advance) }}</p>
            </V2Panel>
            <V2Panel>
                <p class="text-xs text-muted-foreground">{{ t('Net') }}</p>
                <p class="mt-1 text-lg font-semibold tabular-nums text-primary">
                    {{ amount(tabTotals.net) }}
                </p>
            </V2Panel>
        </div>

        <V2TablePanel
            table-id="payroll-run-items"
            :columns="tableColumns"
            :delay="false"
        >
            <template #filters>
                <div class="table-top">
                    <div>
                        <h2>{{ t('Line items') }}</h2>
                        <p>
                            {{
                                activeTab === 'employees'
                                    ? t('Employee payroll lines for this run')
                                    : t('Contractor payroll lines for this run')
                            }}
                        </p>
                    </div>
                </div>
            </template>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ t('Personnel') }}</th>
                        <th class="end">{{ t('Present') }}</th>
                        <th class="end">{{ t('Absent') }}</th>
                        <th class="end">{{ t('Sick') }}</th>
                        <th class="end">{{ t('Annual') }}</th>
                        <th class="end">{{ t('Casual') }}</th>
                        <th class="end">{{ t('Other') }}</th>
                        <th class="end">{{ t('Base') }}</th>
                        <th class="end">{{ t('Bonus') }}</th>
                        <th class="end">{{ t('Deductions') }}</th>
                        <th class="end">{{ t('Tax') }}</th>
                        <th class="end">{{ t('Advance') }}</th>
                        <th class="end">{{ t('Net') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="itemCount === 0">
                        <td colspan="14" class="py-10 text-center text-muted-foreground">
                            {{
                                t(
                                    'No attendance found for this period. Record attendance first, then regenerate.',
                                )
                            }}
                        </td>
                    </tr>
                    <tr v-else-if="activeItems.length === 0">
                        <td colspan="14" class="py-10 text-center text-muted-foreground">
                            {{
                                activeTab === 'employees'
                                    ? t('No employees in this payroll run.')
                                    : t('No contractors in this payroll run.')
                            }}
                        </td>
                    </tr>
                    <tr
                        v-for="(item, index) in activeItems"
                        :key="item.id"
                    >
                        <td class="tabular-nums text-muted-foreground">
                            {{ index + 1 }}
                        </td>
                        <td class="font-medium">{{ personnelLabel(item) }}</td>
                        <td class="end tabular-nums">
                            {{ dayCount(item, 'days_present') }}
                        </td>
                        <td class="end tabular-nums text-destructive">
                            {{ dayCount(item, 'days_absent') }}
                        </td>
                        <td class="end tabular-nums">
                            {{ dayCount(item, 'days_sick_leave') }}
                        </td>
                        <td class="end tabular-nums">
                            {{ dayCount(item, 'days_annual_leave') }}
                        </td>
                        <td class="end tabular-nums">
                            {{ dayCount(item, 'days_casual_leave') }}
                        </td>
                        <td class="end tabular-nums">
                            {{ dayCount(item, 'days_other') }}
                        </td>
                        <td class="end tabular-nums">{{ amount(item.base_amount) }}</td>
                        <td class="end tabular-nums text-primary">
                            {{ amount(item.bonus) }}
                        </td>
                        <td class="end tabular-nums text-destructive">
                            {{ amount(item.deductions) }}
                        </td>
                        <td class="end tabular-nums text-destructive">
                            {{ amount(item.tax) }}
                        </td>
                        <td class="end tabular-nums text-destructive">
                            {{ amount(item.advance) }}
                        </td>
                        <td class="end font-semibold tabular-nums">
                            {{ amount(item.net_amount) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </V2TablePanel>

        <EntityAttachments
            v-if="payrollRun.attachments?.length"
            :attachments="payrollRun.attachments"
        />

        <Dialog v-model:open="printDialogOpen">
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{{ t('Print payroll') }}</DialogTitle>
                    <DialogDescription>
                        {{
                            t(
                                'Select employees or contractors to print',
                            )
                        }}
                    </DialogDescription>
                </DialogHeader>

                <PayrollPrintPersonnelPicker
                    v-model="printSelectedIds"
                    :employees="printEmployees"
                    :contractors="printContractors"
                />

                <p
                    v-if="printSelectedIds.length === 0"
                    class="text-sm text-destructive"
                >
                    {{
                        t(
                            'Select at least one employee or contractor to print.',
                        )
                    }}
                </p>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="printDialogOpen = false"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        type="button"
                        :disabled="printSelectedIds.length === 0"
                        @click="confirmPrint"
                    >
                        <Printer class="size-4" />
                        {{ t('Print') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </V2ListPage>
</template>
