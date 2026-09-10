<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import MisPage from '@/components/MisPage.vue';
import MisPagination from '@/components/MisPagination.vue';
import MisTabs from '@/components/MisTabs.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useMisPage } from '@/composables/useMisPage';
import { formatCurrency, formatDate, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import {
    approvalStatusActions,
    invoiceStatusActions,
} from '@/lib/status-actions';
import { ChevronDown, Paperclip, Plus } from '@lucide/vue';
import { cn } from '@/lib/utils';

interface FinanceAttachment {
    id: number;
    original_filename: string;
    download_url: string;
}

interface Income {
    id: number;
    description: string;
    amount: number;
    amount_usd?: number | null;
    currency?: string | null;
    received_at?: string | null;
    status?: string | null;
    project?: { id: number; code: string; name: string } | null;
    attachments?: FinanceAttachment[];
}

interface Expense {
    id: number;
    description: string;
    amount: number;
    amount_usd?: number | null;
    currency?: string | null;
    incurred_at?: string | null;
    status?: string | null;
    project?: { id: number; code: string; name: string } | null;
    attachments?: FinanceAttachment[];
}

interface Invoice {
    id: number;
    invoice_number?: string | null;
    status: string;
    total?: number | null;
    subtotal?: number | null;
    tax?: number | null;
    currency?: string | null;
    issue_date?: string | null;
    due_date?: string | null;
    project?: { id: number; code: string; name: string } | null;
    organization?: { id: number; name: string } | null;
    attachments?: FinanceAttachment[];
}

interface SelectOption {
    id: number;
    code?: string;
    name: string;
}

interface GeneralRecord {
    id: number;
    description: string | null;
    category?: string | null;
    amount: number;
    amount_usd?: number | null;
    currency?: string | null;
    transaction_date?: string | null;
    status?: string | null;
    attachments?: FinanceAttachment[];
}

interface FinanceSummary {
    total_income?: number;
    project_income?: number;
    general_income?: number;
    total_expenses?: number;
    project_expenses?: number;
    general_expenses?: number;
    total_invoices?: number;
    outstanding?: number;
    currency_breakdown?: Array<{
        currency: string;
        income: number;
        expenses: number;
        invoices: number;
        outstanding: number;
        net: number;
    }>;
}

interface Props {
    summary?: FinanceSummary;
    incomes?: Income[];
    expenses?: Expense[];
    generalIncomes?: GeneralRecord[];
    generalExpenses?: GeneralRecord[];
    invoices?: Paginated<Invoice>;
    projects?: SelectOption[];
    organizations?: SelectOption[];
}

const props = defineProps<Props>();

const { t, editAction, deleteAction, gateActions } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Finance', href: '/finance' }],
    },
});

const tabs = computed(() => [
    { id: 'overview', label: t('Overview') },
    { id: 'income', label: t('Project Income') },
    { id: 'expenses', label: t('Project Expenses') },
    { id: 'general-income', label: t('Other Income') },
    { id: 'general-expenses', label: t('Overhead & Salaries') },
    { id: 'invoices', label: t('Invoices') },
]);

const activeTab = ref('overview');
const showGeneralIncomeForm = ref(false);
const showGeneralExpenseForm = ref(false);
const showInvoiceForm = ref(false);

const invoiceSubtotal = ref('');
const invoiceTax = ref('');
const invoiceTotal = computed(() => {
    const subtotal = Number(invoiceSubtotal.value) || 0;
    const tax = Number(invoiceTax.value) || 0;

    return (subtotal + tax).toFixed(2);
});

watch(showInvoiceForm, (open) => {
    if (!open) {
        invoiceSubtotal.value = '';
        invoiceTax.value = '';
    }
});

const incomeActions = (item: Income): RowActionItem[] => [
    ...(item.project
        ? [editAction(`/projects/${item.project.id}`, 'projects.view')]
        : []),
    ...gateActions(
        approvalStatusActions({
            url: `/finance/incomes/${item.id}`,
            name: item.description,
            status: item.status ?? 'pending',
            t,
        }),
        'finance.edit',
    ),
    deleteAction(
        {
            href: `/finance/incomes/${item.id}`,
            title: t('Delete income record'),
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                { name: item.description },
            ),
        },
        'finance.delete',
    ),
];

const expenseActions = (item: Expense): RowActionItem[] => [
    ...(item.project
        ? [editAction(`/projects/${item.project.id}`, 'projects.view')]
        : []),
    ...gateActions(
        approvalStatusActions({
            url: `/finance/expenses/${item.id}`,
            name: item.description,
            status: item.status ?? 'pending',
            t,
        }),
        'finance.edit',
    ),
    deleteAction(
        {
            href: `/finance/expenses/${item.id}`,
            title: t('Delete expense record'),
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                { name: item.description },
            ),
        },
        'finance.delete',
    ),
];

const invoiceActions = (invoice: Invoice): RowActionItem[] => [
    ...gateActions(
        invoiceStatusActions({
            url: `/finance/invoices/${invoice.id}`,
            label: invoice.invoice_number ?? `#${invoice.id}`,
            status: invoice.status,
            t,
        }),
        'finance.edit',
    ),
    deleteAction(
        {
            href: `/finance/invoices/${invoice.id}`,
            title: t('Delete invoice'),
            description: t('Delete invoice :label? This cannot be undone.', {
                label: invoice.invoice_number ?? `#${invoice.id}`,
            }),
        },
        'finance.delete',
    ),
];
</script>

<template>
    <Head :title="t('Finance')" />

    <MisPage>
<MisTabs v-model="activeTab" :tabs="tabs" />

        <Card v-if="activeTab === 'overview'">
            <CardHeader class="pb-3">
                <CardTitle class="text-base">{{ t('Overview') }}</CardTitle>
                </CardHeader>
            <CardContent class="space-y-4">
                <div v-if="!summary" class="ui-empty-state">
                    {{ t('No summary data available.') }}
                </div>
                <template v-else>
                    <div class="overflow-x-auto rounded-md border">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/40 text-start text-muted-foreground">
                                <tr>
                                    <th class="px-3 py-2 font-medium">{{ t('Metric') }}</th>
                                    <th class="px-3 py-2 text-end font-medium">{{ t('Total') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ t('Breakdown') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr class="hover:bg-muted/30">
                                    <td class="px-3 py-2 font-medium">{{ t('Total Income') }}</td>
                                    <td class="px-3 py-2 text-end font-semibold text-green-600 dark:text-green-400">
                                        {{ formatCurrency(summary.total_income) }}
                                    </td>
                                    <td class="px-3 py-2 text-muted-foreground">
                                        {{ t('Project') }} {{ formatCurrency(summary.project_income) }} ·
                                        {{ t('Other') }} {{ formatCurrency(summary.general_income) }}
                                    </td>
                                </tr>
                                <tr class="hover:bg-muted/30">
                                    <td class="px-3 py-2 font-medium">{{ t('Total Expenses') }}</td>
                                    <td class="px-3 py-2 text-end font-semibold text-destructive">
                                        {{ formatCurrency(summary.total_expenses) }}
                                    </td>
                                    <td class="px-3 py-2 text-muted-foreground">
                                        {{ t('Project') }} {{ formatCurrency(summary.project_expenses) }} ·
                                        {{ t('Overhead') }} {{ formatCurrency(summary.general_expenses) }}
                                    </td>
                                </tr>
                                <tr class="hover:bg-muted/30">
                                    <td class="px-3 py-2 font-medium">{{ t('Invoices') }}</td>
                                    <td class="px-3 py-2 text-end font-semibold">
                                        {{ summary.total_invoices ?? 0 }}
                                    </td>
                                    <td class="px-3 py-2 text-muted-foreground">—</td>
                                </tr>
                                <tr class="hover:bg-muted/30">
                                    <td class="px-3 py-2 font-medium">{{ t('Outstanding') }}</td>
                                    <td class="px-3 py-2 text-end font-semibold">
                                        {{ formatCurrency(summary.outstanding) }}
                                    </td>
                                    <td class="px-3 py-2 text-muted-foreground">—</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="summary.currency_breakdown?.length"
                        class="overflow-x-auto rounded-md border"
                    >
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/40 text-start text-muted-foreground">
                                <tr>
                                    <th class="px-3 py-2 font-medium">{{ t('Currency') }}</th>
                                    <th class="px-3 py-2 text-end font-medium">{{ t('Income') }}</th>
                                    <th class="px-3 py-2 text-end font-medium">{{ t('Expenses') }}</th>
                                    <th class="px-3 py-2 text-end font-medium">{{ t('Net') }}</th>
                                    <th class="px-3 py-2 text-end font-medium">{{ t('Invoices') }}</th>
                                    <th class="px-3 py-2 text-end font-medium">{{ t('Outstanding') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="row in summary.currency_breakdown"
                                    :key="row.currency"
                                    class="hover:bg-muted/30"
                                >
                                    <td class="px-3 py-2 font-medium">{{ row.currency }}</td>
                                    <td class="px-3 py-2 text-end text-green-600 dark:text-green-400">
                                        {{ formatCurrency(row.income, row.currency) }}
                                    </td>
                                    <td class="px-3 py-2 text-end text-destructive">
                                        {{ formatCurrency(row.expenses, row.currency) }}
                                    </td>
                                    <td class="px-3 py-2 text-end font-semibold">
                                        {{ formatCurrency(row.net, row.currency) }}
                                    </td>
                                    <td class="px-3 py-2 text-end">
                                        {{ formatCurrency(row.invoices, row.currency) }}
                                    </td>
                                    <td class="px-3 py-2 text-end">
                                        {{ formatCurrency(row.outstanding, row.currency) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'income'">
            <CardHeader class="pb-3">
                <CardTitle class="text-base">{{ t('Project Income') }}</CardTitle>
                </CardHeader>
            <CardContent>
                <div
                    v-if="!incomes?.length"
                    class="ui-empty-state"
                >
                    {{ t('No income records found.') }}
                </div>
                <div v-else class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-start text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ t('Description') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Project') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Date') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Status') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Attachment') }}</th>
                                <th class="px-3 py-2 text-end font-medium">{{ t('Amount') }}</th>
                                <th class="px-3 py-2 text-end font-medium">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="item in incomes"
                                :key="item.id"
                                class="hover:bg-muted/30"
                            >
                                <td class="px-3 py-2">{{ item.description }}</td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ item.project?.code ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ formatDate(item.received_at) }}
                                </td>
                                <td class="px-3 py-2">
                                    <Badge variant="outline">
                                        {{ item.status ?? t('pending') }}
                                    </Badge>
                                </td>
                                <td class="px-3 py-2">
                                    <a
                                        v-if="item.attachments?.length"
                                        :href="item.attachments[0].download_url"
                                        class="inline-flex items-center gap-1 text-primary hover:underline"
                                        :title="item.attachments[0].original_filename"
                                    >
                                        <Paperclip class="size-3.5 shrink-0" />
                                        <span class="max-w-[8rem] truncate text-xs">
                                            {{ item.attachments[0].original_filename }}
                                        </span>
                                    </a>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-3 py-2 text-end font-medium">
                                    {{
                                        formatCurrency(
                                            item.amount_usd ?? item.amount,
                                            item.currency,
                                        )
                                    }}
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <RowActionsMenu :actions="incomeActions(item)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'expenses'">
            <CardHeader class="pb-3">
                <CardTitle class="text-base">{{ t('Project Expenses') }}</CardTitle>
                </CardHeader>
            <CardContent>
                <div
                    v-if="!expenses?.length"
                    class="ui-empty-state"
                >
                    {{ t('No expense records found.') }}
                </div>
                <div v-else class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-start text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ t('Description') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Project') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Date') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Status') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Attachment') }}</th>
                                <th class="px-3 py-2 text-end font-medium">{{ t('Amount') }}</th>
                                <th class="px-3 py-2 text-end font-medium">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="item in expenses"
                                :key="item.id"
                                class="hover:bg-muted/30"
                            >
                                <td class="px-3 py-2">{{ item.description }}</td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ item.project?.code ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ formatDate(item.incurred_at) }}
                                </td>
                                <td class="px-3 py-2">
                                    <Badge variant="outline">
                                        {{ item.status ?? t('pending') }}
                                    </Badge>
                                </td>
                                <td class="px-3 py-2">
                                    <a
                                        v-if="item.attachments?.length"
                                        :href="item.attachments[0].download_url"
                                        class="inline-flex items-center gap-1 text-primary hover:underline"
                                        :title="item.attachments[0].original_filename"
                                    >
                                        <Paperclip class="size-3.5 shrink-0" />
                                        <span class="max-w-[8rem] truncate text-xs">
                                            {{ item.attachments[0].original_filename }}
                                        </span>
                                    </a>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-3 py-2 text-end font-medium">
                                    {{
                                        formatCurrency(
                                            item.amount_usd ?? item.amount,
                                            item.currency,
                                        )
                                    }}
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <RowActionsMenu :actions="expenseActions(item)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'general-income'">
            <Collapsible v-model:open="showGeneralIncomeForm">
                <CardHeader class="pb-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <CardTitle class="text-base">{{ t('Other Income') }}</CardTitle>
</div>
                        <Can permission="finance.create">
                            <CollapsibleTrigger as-child>
                                <Button variant="outline" size="sm">
                                    <Plus class="me-1 size-4" />
                                    {{ t('Add') }}
                                    <ChevronDown
                                        class="ms-1 size-4 transition-transform"
                                        :class="cn(showGeneralIncomeForm && 'rotate-180')"
                                    />
                                </Button>
                            </CollapsibleTrigger>
                        </Can>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <Can permission="finance.create">
                        <CollapsibleContent class="rounded-md border bg-muted/20 p-4">
                            <Form
                                action="/finance/general-incomes"
                                method="post"
                                class="grid gap-3 sm:grid-cols-2"
                                :options="{ preserveScroll: true, resetOnSuccess: true, forceFormData: true }"
                                validate-files
                                v-slot="{ errors, processing }"
                            >
                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="gi-description">{{ t('Description') }}</Label>
                                    <Textarea id="gi-description" name="description" rows="2" required />
                                    <InputError :message="errors.description" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="gi-category">{{ t('Category') }}</Label>
                                    <Input id="gi-category" name="category" :placeholder="t('e.g. Grant, Investment')" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="gi-amount">{{ t('Amount') }} *</Label>
                                    <Input id="gi-amount" name="amount" type="number" min="0" step="0.01" required />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="gi-date">{{ t('Date') }} *</Label>
                                    <Input id="gi-date" name="transaction_date" type="date" required />
                                </div>
                                <div class="grid gap-2 sm:col-span-2">
                                    <OptionalAttachmentField
                                        :label="t('Receipt')"
                                        :error="errors.attachment"
                                    />
                                </div>
                                <div class="flex items-end sm:col-span-2">
                                    <Button type="submit" size="sm" :disabled="processing">
                                        {{ t('Save') }}
                                    </Button>
                                </div>
                            </Form>
                        </CollapsibleContent>
                    </Can>
                <div
                    v-if="!generalIncomes?.length"
                    class="ui-empty-state"
                >
                    {{ t('No other income records.') }}
                </div>
                <div v-else class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-start text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ t('Description') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Category') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Date') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Attachment') }}</th>
                                <th class="px-3 py-2 text-end font-medium">{{ t('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="item in generalIncomes"
                                :key="item.id"
                                class="hover:bg-muted/30"
                            >
                                <td class="px-3 py-2">{{ item.description }}</td>
                                <td class="px-3 py-2 text-muted-foreground">{{ item.category ?? '—' }}</td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ formatDate(item.transaction_date) }}
                                </td>
                                <td class="px-3 py-2">
                                    <a
                                        v-if="item.attachments?.length"
                                        :href="item.attachments[0].download_url"
                                        class="inline-flex items-center gap-1 text-primary hover:underline"
                                        :title="item.attachments[0].original_filename"
                                    >
                                        <Paperclip class="size-3.5 shrink-0" />
                                        <span class="max-w-[8rem] truncate text-xs">
                                            {{ item.attachments[0].original_filename }}
                                        </span>
                                    </a>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-3 py-2 text-end font-medium text-green-600 dark:text-green-400">
                                    {{ formatCurrency(item.amount, item.currency) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </CardContent>
            </Collapsible>
        </Card>

        <Card v-else-if="activeTab === 'general-expenses'">
            <Collapsible v-model:open="showGeneralExpenseForm">
                <CardHeader class="pb-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <CardTitle class="text-base">{{ t('Overhead & Salaries') }}</CardTitle>
</div>
                        <Can permission="finance.create">
                            <CollapsibleTrigger as-child>
                                <Button variant="outline" size="sm">
                                    <Plus class="me-1 size-4" />
                                    {{ t('Add') }}
                                    <ChevronDown
                                        class="ms-1 size-4 transition-transform"
                                        :class="cn(showGeneralExpenseForm && 'rotate-180')"
                                    />
                                </Button>
                            </CollapsibleTrigger>
                        </Can>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <Can permission="finance.create">
                        <CollapsibleContent class="rounded-md border bg-muted/20 p-4">
                            <Form
                                action="/finance/general-expenses"
                                method="post"
                                class="grid gap-3 sm:grid-cols-2"
                                :options="{ preserveScroll: true, resetOnSuccess: true, forceFormData: true }"
                                validate-files
                                v-slot="{ errors, processing }"
                            >
                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="ge-description">{{ t('Description') }}</Label>
                                    <Textarea id="ge-description" name="description" rows="2" required />
                                    <InputError :message="errors.description" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="ge-category">{{ t('Category') }}</Label>
                                    <select id="ge-category" name="category" class="h-9 rounded-md border border-input px-3 text-sm">
                                        <option value="">{{ t('Select category') }}</option>
                                        <option value="rent">{{ t('Office Rent') }}</option>
                                        <option value="salary">{{ t('Salary') }}</option>
                                        <option value="utilities">{{ t('Utilities') }}</option>
                                        <option value="equipment">{{ t('Equipment') }}</option>
                                        <option value="other">{{ t('Other') }}</option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="ge-amount">{{ t('Amount') }} *</Label>
                                    <Input id="ge-amount" name="amount" type="number" min="0" step="0.01" required />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="ge-date">{{ t('Date') }} *</Label>
                                    <Input id="ge-date" name="transaction_date" type="date" required />
                                </div>
                                <div class="grid gap-2 sm:col-span-2">
                                    <OptionalAttachmentField
                                        :label="t('Receipt')"
                                        :error="errors.attachment"
                                    />
                                </div>
                                <div class="flex items-end sm:col-span-2">
                                    <Button type="submit" size="sm" :disabled="processing">
                                        {{ t('Save') }}
                                    </Button>
                                </div>
                            </Form>
                        </CollapsibleContent>
                    </Can>
                <div
                    v-if="!generalExpenses?.length"
                    class="ui-empty-state"
                >
                    {{ t('No overhead records.') }}
                </div>
                <div v-else class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-start text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ t('Description') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Category') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Date') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Attachment') }}</th>
                                <th class="px-3 py-2 text-end font-medium">{{ t('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="item in generalExpenses"
                                :key="item.id"
                                class="hover:bg-muted/30"
                            >
                                <td class="px-3 py-2">{{ item.description }}</td>
                                <td class="px-3 py-2 text-muted-foreground">{{ item.category ?? '—' }}</td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ formatDate(item.transaction_date) }}
                                </td>
                                <td class="px-3 py-2">
                                    <a
                                        v-if="item.attachments?.length"
                                        :href="item.attachments[0].download_url"
                                        class="inline-flex items-center gap-1 text-primary hover:underline"
                                        :title="item.attachments[0].original_filename"
                                    >
                                        <Paperclip class="size-3.5 shrink-0" />
                                        <span class="max-w-[8rem] truncate text-xs">
                                            {{ item.attachments[0].original_filename }}
                                        </span>
                                    </a>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-3 py-2 text-end font-medium text-destructive">
                                    {{ formatCurrency(item.amount, item.currency) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </CardContent>
            </Collapsible>
        </Card>

        <Card v-else-if="activeTab === 'invoices'">
            <Collapsible v-model:open="showInvoiceForm">
                <CardHeader class="pb-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <CardTitle class="text-base">{{ t('Invoices') }}</CardTitle>
                        </div>
                        <Can permission="finance.create">
                            <CollapsibleTrigger as-child>
                                <Button variant="outline" size="sm">
                                    <Plus class="me-1 size-4" />
                                    {{ t('Add') }}
                                    <ChevronDown
                                        class="ms-1 size-4 transition-transform"
                                        :class="cn(showInvoiceForm && 'rotate-180')"
                                    />
                                </Button>
                            </CollapsibleTrigger>
                        </Can>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <Can permission="finance.create">
                        <CollapsibleContent class="rounded-md border bg-muted/20 p-4">
                            <Form
                                action="/finance/invoices"
                                method="post"
                                class="grid gap-3 sm:grid-cols-2"
                                :options="{ preserveScroll: true, resetOnSuccess: true, forceFormData: true }"
                                validate-files
                                v-slot="{ errors, processing }"
                                @success="showInvoiceForm = false"
                            >
                                <div class="grid gap-2">
                                    <Label for="inv-number">{{ t('Invoice #') }} *</Label>
                                    <Input id="inv-number" name="invoice_number" required />
                                    <InputError :message="errors.invoice_number" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="inv-status">{{ t('Status') }}</Label>
                                    <select
                                        id="inv-status"
                                        name="status"
                                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="draft">{{ t('Draft') }}</option>
                                        <option value="sent">{{ t('Sent') }}</option>
                                        <option value="paid">{{ t('Paid') }}</option>
                                        <option value="overdue">{{ t('Overdue') }}</option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="inv-org">{{ t('Client') }}</Label>
                                    <select
                                        id="inv-org"
                                        name="organization_id"
                                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="">{{ t('Select client') }}</option>
                                        <option
                                            v-for="org in props.organizations ?? []"
                                            :key="org.id"
                                            :value="org.id"
                                        >
                                            {{ org.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="inv-project">{{ t('Project') }}</Label>
                                    <select
                                        id="inv-project"
                                        name="project_id"
                                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="">{{ t('Optional project') }}</option>
                                        <option
                                            v-for="project in props.projects ?? []"
                                            :key="project.id"
                                            :value="project.id"
                                        >
                                            {{ project.code }} — {{ project.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="inv-issue">{{ t('Issue date') }} *</Label>
                                    <Input id="inv-issue" name="issue_date" type="date" required />
                                    <InputError :message="errors.issue_date" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="inv-due">{{ t('Due date') }}</Label>
                                    <Input id="inv-due" name="due_date" type="date" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="inv-subtotal">{{ t('Subtotal') }} *</Label>
                                    <Input
                                        id="inv-subtotal"
                                        name="subtotal"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                        v-model="invoiceSubtotal"
                                    />
                                    <InputError :message="errors.subtotal" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="inv-tax">{{ t('Tax') }}</Label>
                                    <Input
                                        id="inv-tax"
                                        name="tax"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        v-model="invoiceTax"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="inv-total">{{ t('Total') }}</Label>
                                    <Input
                                        id="inv-total"
                                        type="number"
                                        :model-value="invoiceTotal"
                                        readonly
                                        class="bg-muted/40"
                                    />
                                    <input type="hidden" name="total" :value="invoiceTotal" />
                                    <InputError :message="errors.total" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="inv-currency">{{ t('Currency') }}</Label>
                                    <Input id="inv-currency" name="currency" maxlength="3" value="AFN" />
                                </div>
                                <div class="grid gap-2 sm:col-span-2">
                                    <OptionalAttachmentField
                                        :label="t('Attachment')"
                                        :error="errors.attachment"
                                    />
                                </div>
                                <div class="flex items-end sm:col-span-2">
                                    <Button type="submit" size="sm" :disabled="processing">
                                        {{ t('Save invoice') }}
                                    </Button>
                                </div>
                            </Form>
                        </CollapsibleContent>
                    </Can>

                    <div
                        v-if="!props.invoices?.data?.length"
                        class="ui-empty-state"
                    >
                        {{ t('No invoices found.') }}
                    </div>
                    <div v-else class="space-y-4">
                        <div class="overflow-x-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead class="border-b bg-muted/40 text-start text-muted-foreground">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">{{ t('Invoice #') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('Client') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('Project') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('Due Date') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('Status') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('Attachment') }}</th>
                                        <th class="px-3 py-2 text-end font-medium">{{ t('Amount') }}</th>
                                        <th class="px-3 py-2 text-end font-medium">{{ t('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="invoice in props.invoices.data"
                                        :key="invoice.id"
                                        class="hover:bg-muted/30"
                                    >
                                        <td class="px-3 py-2 font-medium">
                                            {{ invoice.invoice_number ?? `#${invoice.id}` }}
                                        </td>
                                        <td class="px-3 py-2 text-muted-foreground">
                                            {{ invoice.organization?.name ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-muted-foreground">
                                            {{ invoice.project?.code ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-muted-foreground">
                                            {{ formatDate(invoice.due_date) }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <Badge variant="outline">{{ invoice.status }}</Badge>
                                        </td>
                                        <td class="px-3 py-2">
                                            <a
                                                v-if="invoice.attachments?.length"
                                                :href="invoice.attachments[0].download_url"
                                                class="inline-flex items-center gap-1 text-primary hover:underline"
                                                :title="invoice.attachments[0].original_filename"
                                            >
                                                <Paperclip class="size-3.5 shrink-0" />
                                                <span class="max-w-[8rem] truncate text-xs">
                                                    {{ invoice.attachments[0].original_filename }}
                                                </span>
                                            </a>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </td>
                                        <td class="px-3 py-2 text-end font-medium">
                                            {{ formatCurrency(invoice.total, invoice.currency) }}
                                        </td>
                                        <td class="px-3 py-2 text-end">
                                            <RowActionsMenu :actions="invoiceActions(invoice)" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <MisPagination
                            v-if="props.invoices"
                            :pagination="props.invoices"
                        />
                    </div>
                </CardContent>
            </Collapsible>
        </Card>
    </MisPage>
</template>
