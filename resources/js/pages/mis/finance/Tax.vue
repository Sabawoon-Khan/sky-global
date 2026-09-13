<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import MisTabs from '@/components/MisTabs.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    V2Hero,
    V2ListPage,
    V2Panel,
    V2StatCard,
    V2StatGrid,
} from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort, useTableSort } from '@/composables/useTableSort';
import SortableTh from '@/components/SortableTh.vue';
import { formatAfn, formatDate } from '@/lib/format';
import {
    Building2,
    Paperclip,
    Percent,
    Plus,
    Printer,
    Users,
    Wallet,
} from '@lucide/vue';

interface CompanyTaxPeriod {
    rate: number;
    rate_percent: number;
    income: number;
    expenses: number;
    taxable_profit: number;
    tax_due: number;
    net_after_tax: number;
    paid?: number;
    remaining?: number;
    their_share_due?: number;
    company_share_due?: number;
    their_paid?: number;
    company_paid?: number;
    their_remaining?: number;
    company_remaining?: number;
    quarter?: number;
    year?: number;
    label?: string;
    period_start?: string;
    period_end?: string;
}

interface TaxPaymentRow {
    id: number;
    period_type: 'quarterly' | 'yearly';
    year: number;
    quarter: number | null;
    label: string;
    rate_percent: number;
    tax_due: number;
    amount: number;
    their_amount: number;
    company_amount: number;
    payment_date: string | null;
    payment_date_label?: string | null;
    payment_method: string | null;
    reference_number: string | null;
    notes: string | null;
    attachments?: Array<{
        id: number;
        original_filename: string;
        download_url: string;
    }>;
}

interface CompanyTaxReport {
    calendar?: string;
    current_year: number;
    current_quarter: number;
    quarterly_rate_percent: number;
    yearly_rate_percent: number;
    paid_this_year: number;
    year: CompanyTaxPeriod;
    quarters: CompanyTaxPeriod[];
    years: CompanyTaxPeriod[];
    daily: CompanyTaxPeriod[];
    weekly: CompanyTaxPeriod[];
    monthly: CompanyTaxPeriod[];
    yearly: CompanyTaxPeriod[];
}

type ReportPeriod = 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'yearly';

const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm';

const props = defineProps<{
    tax?: CompanyTaxReport;
    payments?: TaxPaymentRow[];
}>();

const { t } = useMisPage();
const showPayForm = ref(false);
const periodType = ref<'quarterly' | 'yearly'>('quarterly');
const selectedYear = ref(props.tax?.current_year ?? 1405);
const selectedQuarter = ref(props.tax?.current_quarter ?? 1);
const amount = ref('');
const theirAmount = ref('');
const companyAmount = ref('');
const activeReport = ref<ReportPeriod>('quarterly');

const years = computed(() => {
    const current = props.tax?.current_year ?? 1405;

    return [current, current - 1, current - 2];
});

const currentQuarter = computed(() =>
    props.tax?.quarters.find((row) => row.quarter === props.tax?.current_quarter),
);

const selectedPeriod = computed(() => {
    if (!props.tax) {
        return null;
    }

    if (periodType.value === 'yearly') {
        return (
            props.tax.years.find((row) => row.year === selectedYear.value) ??
            null
        );
    }

    if (selectedYear.value === props.tax.current_year) {
        return (
            props.tax.quarters.find(
                (row) => row.quarter === selectedQuarter.value,
            ) ?? null
        );
    }

    return null;
});

const remaining = computed(() => selectedPeriod.value?.remaining ?? 0);
const theirRemaining = computed(
    () => selectedPeriod.value?.their_remaining ?? 0,
);
const companyRemaining = computed(
    () => selectedPeriod.value?.company_remaining ?? 0,
);

watch(
    [periodType, remaining, theirRemaining, companyRemaining],
    () => {
        if (periodType.value === 'quarterly') {
            theirAmount.value =
                theirRemaining.value > 0 ? String(theirRemaining.value) : '';
            companyAmount.value =
                companyRemaining.value > 0
                    ? String(companyRemaining.value)
                    : '';
            return;
        }

        amount.value = remaining.value > 0 ? String(remaining.value) : '';
    },
    { immediate: true },
);

const reportRows = computed(() => {
    if (!props.tax) {
        return [];
    }

    if (activeReport.value === 'quarterly') {
        return props.tax.quarters;
    }

    return props.tax[activeReport.value] ?? [];
});

const { sortedRows: sortedReportRows } = provideTableSort(() => reportRows.value, {
    accessors: {
        period: (row) => row.label ?? row.period_start,
        income: (row) => row.income,
        expenses: (row) => row.expenses,
        taxable_profit: (row) => row.taxable_profit,
        tax_due: (row) => row.tax_due,
        their: (row) => row.their_paid ?? row.their_share_due,
        company: (row) => row.company_paid ?? row.company_share_due,
        paid: (row) => row.paid,
        remaining: (row) => row.remaining,
        net: (row) => row.net_after_tax,
    },
});

const paymentSort = useTableSort(() => props.payments ?? [], {
    accessors: {
        date: (row) => row.payment_date,
        period: (row) => row.label,
        reference: (row) => row.reference_number,
        documents: (row) => row.attachments?.length ?? 0,
        their: (row) => row.their_amount,
        company: (row) => row.company_amount,
        amount: (row) => row.amount,
    },
});

const showSplit = computed(() => activeReport.value === 'quarterly');
const showYearlyPaid = computed(() => activeReport.value === 'yearly');

const printHref = computed(
    () => `/finance/tax/print?period=${activeReport.value}`,
);

const paymentMethodLabel = (method: string | null): string => {
    const labels: Record<string, string> = {
        bank_transfer: t('Bank transfer'),
        cash: t('Cash'),
        cheque: t('Cheque'),
        other: t('Other'),
    };

    return method ? (labels[method] ?? method) : '—';
};

const isCurrentRow = (row: CompanyTaxPeriod): boolean => {
    if (activeReport.value === 'quarterly') {
        return row.quarter === props.tax?.current_quarter;
    }

    if (activeReport.value === 'yearly') {
        return row.year === props.tax?.current_year;
    }

    return false;
};

const todayIso = new Date().toISOString().slice(0, 10);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finance', href: '/finance' },
            { title: 'Tax', href: '/finance/tax' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Tax')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png">
            <template #eyebrow>{{ t('Finance') }}</template>
            <template #title>{{ t('Tax') }}</template>
            <template #stats>
                <V2StatGrid v-if="tax">
                    <V2StatCard
                        :delay="0"
                        icon-tone="warm"
                        :title="`${t('Yearly tax')} · ${tax.current_year} ${t('Hijri Shamsi')}`"
                        :value="formatAfn(tax.year.tax_due)"
                    >
                        <template #icon><Percent /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        :title="`${t('Quarterly tax')} · Q${tax.current_quarter}`"
                        :value="formatAfn(currentQuarter?.tax_due)"
                    >
                        <template #icon><Percent /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Tax paid')"
                        :value="formatAfn(tax.paid_this_year)"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        :title="t('Tax remaining')"
                        :value="formatAfn(tax.year.remaining)"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <div v-if="!tax" class="ui-empty-state rounded-md border">
            {{ t('No summary data available.') }}
        </div>

        <div v-else class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2">
                <div
                    class="rounded-xl border bg-card p-4 shadow-sm"
                >
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t('Quarterly tax') }}
                    </p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums">4%</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs font-medium"
                        >
                            <Users class="size-3.5" />
                            2% {{ t('Paid by them') }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs font-medium"
                        >
                            <Building2 class="size-3.5" />
                            2% {{ t('Paid by company') }}
                        </span>
                    </div>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t('Yearly tax') }}
                    </p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums">10%</p>
                </div>
            </div>

            <V2Panel
                :title="t('Pay tax')"
            >
                <div
                    v-if="currentQuarter"
                    class="mb-4 grid gap-3 sm:grid-cols-2"
                >
                    <div
                        class="rounded-lg border bg-muted/30 px-3 py-3"
                    >
                        <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Users class="size-3.5" />
                            {{ t('Paid by them') }} · Q{{ tax.current_quarter }}
                        </p>
                        <p class="mt-1 text-lg font-semibold tabular-nums">
                            {{ formatAfn(currentQuarter.their_remaining) }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ t('Due') }}
                            {{ formatAfn(currentQuarter.their_share_due) }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border bg-muted/30 px-3 py-3"
                    >
                        <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Building2 class="size-3.5" />
                            {{ t('Paid by company') }} · Q{{ tax.current_quarter }}
                        </p>
                        <p class="mt-1 text-lg font-semibold tabular-nums">
                            {{ formatAfn(currentQuarter.company_remaining) }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ t('Due') }}
                            {{ formatAfn(currentQuarter.company_share_due) }}
                        </p>
                    </div>
                </div>

                <Can permission="finance.create">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="showPayForm = true"
                    >
                        <Plus class="size-4" />
                        {{ t('Pay tax') }}
                    </Button>
                </Can>
            </V2Panel>

            <Dialog :open="showPayForm" @update:open="showPayForm = $event">
                <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    <Form
                        action="/finance/tax/payments"
                        method="post"
                        :options="{
                            preserveScroll: true,
                            resetOnSuccess: true,
                            forceFormData: true,
                        }"
                        validate-files
                        v-slot="{ errors, processing }"
                        @success="showPayForm = false"
                    >
                        <DialogHeader>
                            <DialogTitle>{{ t('Pay tax') }}</DialogTitle>
                        </DialogHeader>

                        <div class="grid gap-4 py-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="tax-period-type">{{
                                        t('Period type')
                                    }}</Label>
                                    <select
                                        id="tax-period-type"
                                        name="period_type"
                                        v-model="periodType"
                                        :class="selectClass"
                                    >
                                        <option value="quarterly">
                                            {{ t('Quarterly tax') }} (4%)
                                        </option>
                                        <option value="yearly">
                                            {{ t('Yearly tax') }} (10%)
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="tax-year">{{ t('Hijri Shamsi year') }}</Label>
                                    <select
                                        id="tax-year"
                                        name="year"
                                        v-model.number="selectedYear"
                                        :class="selectClass"
                                    >
                                        <option
                                            v-for="year in years"
                                            :key="year"
                                            :value="year"
                                        >
                                            {{ year }}
                                        </option>
                                    </select>
                                </div>
                                <div
                                    v-if="periodType === 'quarterly'"
                                    class="grid gap-2"
                                >
                                    <Label for="tax-quarter">{{
                                        t('Quarter')
                                    }}</Label>
                                    <select
                                        id="tax-quarter"
                                        name="quarter"
                                        v-model.number="selectedQuarter"
                                        :class="selectClass"
                                    >
                                        <option :value="1">
                                            {{ t('Q1 (Hamal–Jawza)') }}
                                        </option>
                                        <option :value="2">
                                            {{ t('Q2 (Saratan–Sunbula)') }}
                                        </option>
                                        <option :value="3">
                                            {{ t('Q3 (Mizan–Qaws)') }}
                                        </option>
                                        <option :value="4">
                                            {{ t('Q4 (Jadi–Hoot)') }}
                                        </option>
                                    </select>
                                </div>
                                <template v-if="periodType === 'quarterly'">
                                    <div class="grid gap-2">
                                        <Label for="tax-their-amount">
                                            {{ t('Paid by them') }} (2%) *
                                        </Label>
                                        <Input
                                            id="tax-their-amount"
                                            name="their_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            v-model="theirAmount"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            {{ t('Due') }}
                                            {{
                                                formatAfn(
                                                    selectedPeriod?.their_share_due,
                                                )
                                            }}
                                            · {{ t('Tax remaining') }}
                                            {{ formatAfn(theirRemaining) }}
                                        </p>
                                        <InputError
                                            :message="errors.their_amount"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="tax-company-amount">
                                            {{ t('Paid by company') }} (2%) *
                                        </Label>
                                        <Input
                                            id="tax-company-amount"
                                            name="company_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            v-model="companyAmount"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            {{ t('Due') }}
                                            {{
                                                formatAfn(
                                                    selectedPeriod?.company_share_due,
                                                )
                                            }}
                                            · {{ t('Tax remaining') }}
                                            {{ formatAfn(companyRemaining) }}
                                        </p>
                                        <InputError
                                            :message="errors.company_amount"
                                        />
                                    </div>
                                </template>
                                <div v-else class="grid gap-2">
                                    <Label for="tax-amount"
                                        >{{ t('Amount') }} *</Label
                                    >
                                    <Input
                                        id="tax-amount"
                                        name="amount"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        required
                                        v-model="amount"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        {{ t('Tax due') }}
                                        {{ formatAfn(selectedPeriod?.tax_due) }}
                                        · {{ t('Tax remaining') }}
                                        {{ formatAfn(remaining) }}
                                    </p>
                                    <InputError :message="errors.amount" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="tax-date"
                                        >{{ t('Payment date') }} *</Label
                                    >
                                    <Input
                                        id="tax-date"
                                        name="payment_date"
                                        type="date"
                                        required
                                        :default-value="todayIso"
                                    />
                                    <InputError
                                        :message="errors.payment_date"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="tax-method">{{
                                        t('Payment method')
                                    }}</Label>
                                    <select
                                        id="tax-method"
                                        name="payment_method"
                                        :class="selectClass"
                                    >
                                        <option value="">
                                            {{ t('Select') }}
                                        </option>
                                        <option value="bank_transfer">
                                            {{ t('Bank transfer') }}
                                        </option>
                                        <option value="cash">
                                            {{ t('Cash') }}
                                        </option>
                                        <option value="cheque">
                                            {{ t('Cheque') }}
                                        </option>
                                        <option value="other">
                                            {{ t('Other') }}
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="tax-reference">{{
                                        t('Reference')
                                    }}</Label>
                                    <Input
                                        id="tax-reference"
                                        name="reference_number"
                                    />
                                </div>
                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="tax-notes">{{
                                        t('Notes')
                                    }}</Label>
                                    <Textarea
                                        id="tax-notes"
                                        name="notes"
                                        rows="2"
                                    />
                                </div>
                                <div class="grid gap-2 sm:col-span-2">
                                    <OptionalAttachmentField
                                        name="documents"
                                        label="Required documents"
                                        accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                                        required
                                        multiple
                                        :error="
                                            errors.documents ||
                                            errors['documents.0']
                                        "
                                    />
                                </div>
                        </div>

                        <DialogFooter class="gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                @click="showPayForm = false"
                            >
                                {{ t('Cancel') }}
                            </Button>
                            <Button type="submit" :disabled="processing">
                                {{ t('Pay tax') }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>

            <V2Panel :title="t('Tax report')">
                <template #actions>
                    <Button variant="outline" size="sm" as-child>
                        <a
                            :href="`${printHref}&autoprint=1`"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Printer class="size-4" />
                            {{ t('Print report') }}
                        </a>
                    </Button>
                </template>

                <MisTabs
                    v-model="activeReport"
                    nowrap
                    :tabs="[
                        { id: 'daily', label: t('Daily') },
                        { id: 'weekly', label: t('Weekly') },
                        { id: 'monthly', label: t('Monthly') },
                        { id: 'quarterly', label: t('Quarterly') },
                        { id: 'yearly', label: t('Yearly') },
                    ]"
                />

                <div class="mt-4 overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b bg-muted/40 text-start text-muted-foreground"
                        >
                            <tr>
                                <SortableTh column="period" class="px-3 py-2 font-medium">
                                    {{ t('Period') }}
                                </SortableTh>
                                <SortableTh column="income" align="end" class="px-3 py-2 text-end font-medium">
                                    {{ t('Income') }}
                                </SortableTh>
                                <SortableTh column="expenses" align="end" class="px-3 py-2 text-end font-medium">
                                    {{ t('Expenses') }}
                                </SortableTh>
                                <SortableTh column="taxable_profit" align="end" class="px-3 py-2 text-end font-medium">
                                    {{ t('Taxable profit') }}
                                </SortableTh>
                                <SortableTh column="tax_due" align="end" class="px-3 py-2 text-end font-medium">
                                    {{ t('Tax due') }}
                                </SortableTh>
                                <SortableTh
                                    v-if="showSplit"
                                    column="their"
                                    align="end"
                                    class="px-3 py-2 text-end font-medium"
                                >
                                    {{ t('Paid by them') }}
                                </SortableTh>
                                <SortableTh
                                    v-if="showSplit"
                                    column="company"
                                    align="end"
                                    class="px-3 py-2 text-end font-medium"
                                >
                                    {{ t('Paid by company') }}
                                </SortableTh>
                                <SortableTh
                                    v-if="showYearlyPaid"
                                    column="paid"
                                    align="end"
                                    class="px-3 py-2 text-end font-medium"
                                >
                                    {{ t('Tax paid') }}
                                </SortableTh>
                                <SortableTh
                                    v-if="showSplit || showYearlyPaid"
                                    column="remaining"
                                    align="end"
                                    class="px-3 py-2 text-end font-medium"
                                >
                                    {{ t('Tax remaining') }}
                                </SortableTh>
                                <SortableTh
                                    v-if="!showSplit"
                                    column="net"
                                    align="end"
                                    class="px-3 py-2 text-end font-medium"
                                >
                                    {{ t('Net after tax') }}
                                </SortableTh>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="row in sortedReportRows"
                                :key="`${row.label}-${row.period_start}`"
                                class="hover:bg-muted/30"
                                :class="isCurrentRow(row) && 'bg-primary/5'"
                            >
                                <td class="px-3 py-2 font-medium">
                                    {{ row.label }}
                                </td>
                                <td
                                    class="px-3 py-2 text-end text-green-600 dark:text-green-400"
                                >
                                    {{ formatAfn(row.income) }}
                                </td>
                                <td
                                    class="px-3 py-2 text-end text-destructive"
                                >
                                    {{ formatAfn(row.expenses) }}
                                </td>
                                <td class="px-3 py-2 text-end">
                                    {{ formatAfn(row.taxable_profit) }}
                                </td>
                                <td
                                    class="px-3 py-2 text-end font-semibold text-destructive"
                                >
                                    {{ formatAfn(row.tax_due) }}
                                </td>
                                <td
                                    v-if="showSplit"
                                    class="px-3 py-2 text-end"
                                >
                                    {{ formatAfn(row.their_paid) }}
                                    <span class="block text-xs text-muted-foreground">
                                        {{ t('Due') }}
                                        {{ formatAfn(row.their_share_due) }}
                                    </span>
                                </td>
                                <td
                                    v-if="showSplit"
                                    class="px-3 py-2 text-end"
                                >
                                    {{ formatAfn(row.company_paid) }}
                                    <span class="block text-xs text-muted-foreground">
                                        {{ t('Due') }}
                                        {{ formatAfn(row.company_share_due) }}
                                    </span>
                                </td>
                                <td
                                    v-if="showYearlyPaid"
                                    class="px-3 py-2 text-end"
                                >
                                    {{ formatAfn(row.paid) }}
                                </td>
                                <td
                                    v-if="showSplit || showYearlyPaid"
                                    class="px-3 py-2 text-end font-semibold"
                                >
                                    {{ formatAfn(row.remaining) }}
                                </td>
                                <td
                                    v-if="!showSplit"
                                    class="px-3 py-2 text-end font-semibold"
                                >
                                    {{ formatAfn(row.net_after_tax) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </V2Panel>

            <V2Panel :title="t('Tax payments')">
                <div
                    v-if="!payments?.length"
                    class="ui-empty-state rounded-md border"
                >
                    {{ t('No tax payments recorded.') }}
                </div>
                <div v-else class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b bg-muted/40 text-start text-muted-foreground"
                        >
                            <tr>
                                <SortableTh
                                    column="date"
                                    class="px-3 py-2 font-medium"
                                    :sort-key="paymentSort.sortKey"
                                    :sort-dir="paymentSort.sortDir"
                                    @sort="paymentSort.sortBy"
                                >
                                    {{ t('Date') }}
                                </SortableTh>
                                <SortableTh
                                    column="period"
                                    class="px-3 py-2 font-medium"
                                    :sort-key="paymentSort.sortKey"
                                    :sort-dir="paymentSort.sortDir"
                                    @sort="paymentSort.sortBy"
                                >
                                    {{ t('Period') }}
                                </SortableTh>
                                <SortableTh
                                    column="reference"
                                    class="px-3 py-2 font-medium"
                                    :sort-key="paymentSort.sortKey"
                                    :sort-dir="paymentSort.sortDir"
                                    @sort="paymentSort.sortBy"
                                >
                                    {{ t('Reference') }}
                                </SortableTh>
                                <SortableTh
                                    column="documents"
                                    class="px-3 py-2 font-medium"
                                    :sort-key="paymentSort.sortKey"
                                    :sort-dir="paymentSort.sortDir"
                                    @sort="paymentSort.sortBy"
                                >
                                    {{ t('Required documents') }}
                                </SortableTh>
                                <SortableTh
                                    column="their"
                                    align="end"
                                    class="px-3 py-2 text-end font-medium"
                                    :sort-key="paymentSort.sortKey"
                                    :sort-dir="paymentSort.sortDir"
                                    @sort="paymentSort.sortBy"
                                >
                                    {{ t('Paid by them') }}
                                </SortableTh>
                                <SortableTh
                                    column="company"
                                    align="end"
                                    class="px-3 py-2 text-end font-medium"
                                    :sort-key="paymentSort.sortKey"
                                    :sort-dir="paymentSort.sortDir"
                                    @sort="paymentSort.sortBy"
                                >
                                    {{ t('Paid by company') }}
                                </SortableTh>
                                <SortableTh
                                    column="amount"
                                    align="end"
                                    class="px-3 py-2 text-end font-medium"
                                    :sort-key="paymentSort.sortKey"
                                    :sort-dir="paymentSort.sortDir"
                                    @sort="paymentSort.sortBy"
                                >
                                    {{ t('Amount') }}
                                </SortableTh>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="payment in paymentSort.sortedRows"
                                :key="payment.id"
                                class="hover:bg-muted/30"
                            >
                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{
                                        payment.payment_date_label ||
                                        formatDate(payment.payment_date)
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium">{{
                                            payment.label
                                        }}</span>
                                        <Badge variant="secondary">
                                            {{ payment.rate_percent }}%
                                        </Badge>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{
                                        payment.reference_number ||
                                        paymentMethodLabel(
                                            payment.payment_method,
                                        )
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    <a
                                        v-if="payment.attachments?.length"
                                        :href="
                                            payment.attachments[0].download_url
                                        "
                                        class="inline-flex items-center gap-1 text-primary hover:underline"
                                    >
                                        <Paperclip class="size-3.5 shrink-0" />
                                        <span class="max-w-[10rem] truncate text-xs">
                                            {{
                                                payment.attachments[0]
                                                    .original_filename
                                            }}
                                        </span>
                                        <span
                                            v-if="
                                                payment.attachments.length > 1
                                            "
                                            class="text-xs text-muted-foreground"
                                        >
                                            +{{
                                                payment.attachments.length - 1
                                            }}
                                        </span>
                                    </a>
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                                <td class="px-3 py-2 text-end">
                                    {{ formatAfn(payment.their_amount) }}
                                </td>
                                <td class="px-3 py-2 text-end">
                                    {{ formatAfn(payment.company_amount) }}
                                </td>
                                <td
                                    class="px-3 py-2 text-end font-medium text-destructive"
                                >
                                    {{ formatAfn(payment.amount) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </V2Panel>
        </div>
    </V2ListPage>
</template>
