<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useMisPage } from '@/composables/useMisPage';
import { cn } from '@/lib/utils';
import type { RowActionItem } from '@/lib/row-actions';
import {
    approvalStatusActions,
    invoiceStatusActions,
} from '@/lib/status-actions';

interface Income {
    id: number;
    description: string;
    amount: number;
    amount_usd?: number | null;
    currency?: string | null;
    received_at?: string | null;
    status?: string | null;
    project?: { id: number; code: string; name: string } | null;
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
}

interface Invoice {
    id: number;
    invoice_number?: string | null;
    status: string;
    total_amount?: number | null;
    currency?: string | null;
    due_date?: string | null;
    organization?: { id: number; name: string } | null;
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
    invoices?: Invoice[];
}

defineProps<Props>();

const { t, editAction, deleteAction, gateActions } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Finance', href: '/finance' }],
    },
});

const tabs = computed(() => [
    { id: 'income' as const, label: t('Project Income') },
    { id: 'expenses' as const, label: t('Project Expenses') },
    { id: 'general-income' as const, label: t('Other Income') },
    { id: 'general-expenses' as const, label: t('Overhead & Salaries') },
    { id: 'invoices' as const, label: t('Invoices') },
]);

type TabId = 'income' | 'expenses' | 'general-income' | 'general-expenses' | 'invoices';

const activeTab = ref<TabId>('income');

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

const formatDate = (value?: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );
};

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

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            :title="t('Finance')"
            :description="t('Project finance, overhead, salaries, and other income')"
        />

        <div v-if="summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Total Income') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-lg font-semibold text-green-600 dark:text-green-400">
                    {{ formatCurrency(summary.total_income) }}
                    <p class="mt-1 text-xs font-normal text-muted-foreground">
                        {{ t('Project') }} {{ formatCurrency(summary.project_income) }} ·
                        {{ t('Other') }} {{ formatCurrency(summary.general_income) }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Total Expenses') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-lg font-semibold text-destructive">
                    {{ formatCurrency(summary.total_expenses) }}
                    <p class="mt-1 text-xs font-normal text-muted-foreground">
                        {{ t('Project') }} {{ formatCurrency(summary.project_expenses) }} ·
                        {{ t('Overhead') }} {{ formatCurrency(summary.general_expenses) }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Invoices') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-lg font-semibold">
                    {{ summary.total_invoices ?? 0 }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Outstanding') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-lg font-semibold">
                    {{ formatCurrency(summary.outstanding) }}
                </CardContent>
            </Card>
        </div>

        <div
            v-if="summary?.currency_breakdown?.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        >
            <Card
                v-for="row in summary.currency_breakdown"
                :key="row.currency"
                class="border-dashed"
            >
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">
                        {{ row.currency }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('Currency summary') }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-1 text-sm">
                    <p class="flex items-center justify-between">
                        <span class="text-muted-foreground">{{ t('Income') }}</span>
                        <span class="font-medium text-green-600 dark:text-green-400">
                            {{ formatCurrency(row.income, row.currency) }}
                        </span>
                    </p>
                    <p class="flex items-center justify-between">
                        <span class="text-muted-foreground">{{ t('Expenses') }}</span>
                        <span class="font-medium text-destructive">
                            {{ formatCurrency(row.expenses, row.currency) }}
                        </span>
                    </p>
                    <p class="flex items-center justify-between">
                        <span class="text-muted-foreground">{{ t('Net') }}</span>
                        <span class="font-semibold">
                            {{ formatCurrency(row.net, row.currency) }}
                        </span>
                    </p>
                    <p class="flex items-center justify-between">
                        <span class="text-muted-foreground">{{ t('Invoices') }}</span>
                        <span class="font-medium">
                            {{ formatCurrency(row.invoices, row.currency) }}
                        </span>
                    </p>
                    <p class="flex items-center justify-between">
                        <span class="text-muted-foreground">{{ t('Outstanding') }}</span>
                        <span class="font-medium">
                            {{ formatCurrency(row.outstanding, row.currency) }}
                        </span>
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="flex flex-wrap gap-2 border-b pb-1">
            <Button
                v-for="tab in tabs"
                :key="tab.id"
                variant="ghost"
                size="sm"
                :class="
                    cn(
                        'rounded-b-none',
                        activeTab === tab.id &&
                            'border-b-2 border-primary bg-muted/50',
                    )
                "
                @click="activeTab = tab.id"
            >
                {{ tab.label }}
            </Button>
        </div>

        <Card v-if="activeTab === 'income'">
            <CardHeader>
                <CardTitle>{{ t('Project Income') }}</CardTitle>
                <CardDescription>{{
                    t('Recorded payments and receipts')
                }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="!incomes?.length"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No income records found.') }}
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-start text-muted-foreground">
                                <th class="pb-2 pe-4 font-medium">{{
                                    t('Description')
                                }}</th>
                                <th class="pb-2 pe-4 font-medium">{{
                                    t('Project')
                                }}</th>
                                <th class="pb-2 pe-4 font-medium">{{
                                    t('Date')
                                }}</th>
                                <th class="pb-2 pe-4 font-medium">{{
                                    t('Status')
                                }}</th>
                                <th class="pb-2 pe-4 text-end font-medium">{{
                                    t('Amount')
                                }}</th>
                                <th class="pb-2 text-end font-medium">{{
                                    t('Actions')
                                }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in incomes"
                                :key="item.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-2 pe-4">{{ item.description }}</td>
                                <td class="py-2 pe-4 text-muted-foreground">
                                    {{ item.project?.code ?? '—' }}
                                </td>
                                <td class="py-2 pe-4 text-muted-foreground">
                                    {{ formatDate(item.received_at) }}
                                </td>
                                <td class="py-2 pe-4">
                                    <Badge variant="outline">
                                        {{ item.status ?? t('pending') }}
                                    </Badge>
                                </td>
                                <td class="py-2 text-end font-medium">
                                    {{
                                        formatCurrency(
                                            item.amount_usd ?? item.amount,
                                            item.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                                <td class="py-2 text-end">
                                    <RowActionsMenu :actions="incomeActions(item)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'expenses'">
            <CardHeader>
                <CardTitle>{{ t('Project Expenses') }}</CardTitle>
                <CardDescription>{{
                    t('Operational and project costs')
                }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="!expenses?.length"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No expense records found.') }}
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-start text-muted-foreground">
                                <th class="pb-2 pe-4 font-medium">{{
                                    t('Description')
                                }}</th>
                                <th class="pb-2 pe-4 font-medium">{{
                                    t('Project')
                                }}</th>
                                <th class="pb-2 pe-4 font-medium">{{
                                    t('Date')
                                }}</th>
                                <th class="pb-2 pe-4 font-medium">{{
                                    t('Status')
                                }}</th>
                                <th class="pb-2 pe-4 text-end font-medium">{{
                                    t('Amount')
                                }}</th>
                                <th class="pb-2 text-end font-medium">{{
                                    t('Actions')
                                }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in expenses"
                                :key="item.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-2 pe-4">{{ item.description }}</td>
                                <td class="py-2 pe-4 text-muted-foreground">
                                    {{ item.project?.code ?? '—' }}
                                </td>
                                <td class="py-2 pe-4 text-muted-foreground">
                                    {{ formatDate(item.incurred_at) }}
                                </td>
                                <td class="py-2 pe-4">
                                    <Badge variant="outline">
                                        {{ item.status ?? t('pending') }}
                                    </Badge>
                                </td>
                                <td class="py-2 text-end font-medium">
                                    {{
                                        formatCurrency(
                                            item.amount_usd ?? item.amount,
                                            item.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                                <td class="py-2 text-end">
                                    <RowActionsMenu :actions="expenseActions(item)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <div v-else-if="activeTab === 'general-income'" class="grid gap-4 xl:grid-cols-3">
            <Can permission="finance.create">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Add Other Income') }}</CardTitle>
                    <CardDescription>{{ t('Non-project income such as investments or grants') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        action="/finance/general-incomes"
                        method="post"
                        class="grid gap-3"
                        :options="{ preserveScroll: true, resetOnSuccess: true }"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="gi-description">{{ t('Description') }}</Label>
                            <Textarea id="gi-description" name="description" rows="3" required />
                            <InputError :message="errors.description" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="gi-category">{{ t('Category') }}</Label>
                            <Input id="gi-category" name="category" :placeholder="t('e.g. Grant, Investment')" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="gi-amount">{{ t('Amount') }} *</Label>
                                <Input id="gi-amount" name="amount" type="number" min="0" step="0.01" required />
                            </div>
                            <div class="grid gap-2">
                                <Label for="gi-date">{{ t('Date') }} *</Label>
                                <Input id="gi-date" name="transaction_date" type="date" required />
                            </div>
                        </div>
                        <Button type="submit" :disabled="processing">{{ t('Save') }}</Button>
                    </Form>
                </CardContent>
            </Card>
            </Can>
            <Card class="xl:col-span-2">
                <CardHeader>
                    <CardTitle>{{ t('Other Income') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="!generalIncomes?.length" class="text-sm text-muted-foreground">
                        {{ t('No other income records.') }}
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-start text-muted-foreground">
                                    <th class="pb-2 pe-4 font-medium">{{ t('Description') }}</th>
                                    <th class="pb-2 pe-4 font-medium">{{ t('Category') }}</th>
                                    <th class="pb-2 pe-4 font-medium">{{ t('Date') }}</th>
                                    <th class="pb-2 text-end font-medium">{{ t('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in generalIncomes" :key="item.id" class="border-b last:border-0">
                                    <td class="py-2 pe-4">{{ item.description }}</td>
                                    <td class="py-2 pe-4 text-muted-foreground">{{ item.category ?? '—' }}</td>
                                    <td class="py-2 pe-4 text-muted-foreground">{{ formatDate(item.transaction_date) }}</td>
                                    <td class="py-2 text-end font-medium text-green-600 dark:text-green-400">
                                        {{ formatCurrency(item.amount_usd ?? item.amount, item.currency ?? 'USD') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div v-else-if="activeTab === 'general-expenses'" class="grid gap-4 xl:grid-cols-3">
            <Can permission="finance.create">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Add Overhead / Salary') }}</CardTitle>
                    <CardDescription>{{ t('Office rent, salaries, utilities — not tied to a project') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        action="/finance/general-expenses"
                        method="post"
                        class="grid gap-3"
                        :options="{ preserveScroll: true, resetOnSuccess: true, forceFormData: true }"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="ge-description">{{ t('Description') }}</Label>
                            <Textarea id="ge-description" name="description" rows="3" required />
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
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="ge-amount">{{ t('Amount') }} *</Label>
                                <Input id="ge-amount" name="amount" type="number" min="0" step="0.01" required />
                            </div>
                            <div class="grid gap-2">
                                <Label for="ge-date">{{ t('Date') }} *</Label>
                                <Input id="ge-date" name="transaction_date" type="date" required />
                            </div>
                        </div>
                        <Button type="submit" :disabled="processing">{{ t('Save') }}</Button>
                    </Form>
                </CardContent>
            </Card>
            </Can>
            <Card class="xl:col-span-2">
                <CardHeader>
                    <CardTitle>{{ t('Overhead & Salaries') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="!generalExpenses?.length" class="text-sm text-muted-foreground">
                        {{ t('No overhead records.') }}
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-start text-muted-foreground">
                                    <th class="pb-2 pe-4 font-medium">{{ t('Description') }}</th>
                                    <th class="pb-2 pe-4 font-medium">{{ t('Category') }}</th>
                                    <th class="pb-2 pe-4 font-medium">{{ t('Date') }}</th>
                                    <th class="pb-2 text-end font-medium">{{ t('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in generalExpenses" :key="item.id" class="border-b last:border-0">
                                    <td class="py-2 pe-4">{{ item.description }}</td>
                                    <td class="py-2 pe-4 text-muted-foreground">{{ item.category ?? '—' }}</td>
                                    <td class="py-2 pe-4 text-muted-foreground">{{ formatDate(item.transaction_date) }}</td>
                                    <td class="py-2 text-end font-medium text-destructive">
                                        {{ formatCurrency(item.amount_usd ?? item.amount, item.currency ?? 'USD') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card v-else>
            <CardHeader>
                <CardTitle>{{ t('Invoices') }}</CardTitle>
                <CardDescription>{{
                    t('Client billing and payment status')
                }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="!invoices?.length"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No invoices found.') }}
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-start text-muted-foreground">
                                <th class="pb-2 pe-4 font-medium">{{
                                    t('Invoice #')
                                }}</th>
                                <th class="pb-2 pe-4 font-medium">{{
                                    t('Client')
                                }}</th>
                                <th class="pb-2 pe-4 font-medium">{{
                                    t('Due Date')
                                }}</th>
                                <th class="pb-2 pe-4 font-medium">{{
                                    t('Status')
                                }}</th>
                                <th class="pb-2 pe-4 text-end font-medium">{{
                                    t('Amount')
                                }}</th>
                                <th class="pb-2 text-end font-medium">{{
                                    t('Actions')
                                }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="invoice in invoices"
                                :key="invoice.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-2 pe-4 font-medium">
                                    {{ invoice.invoice_number ?? `#${invoice.id}` }}
                                </td>
                                <td class="py-2 pe-4 text-muted-foreground">
                                    {{ invoice.organization?.name ?? '—' }}
                                </td>
                                <td class="py-2 pe-4 text-muted-foreground">
                                    {{ formatDate(invoice.due_date) }}
                                </td>
                                <td class="py-2 pe-4">
                                    <Badge variant="outline">
                                        {{ invoice.status }}
                                    </Badge>
                                </td>
                                <td class="py-2 text-end font-medium">
                                    {{
                                        formatCurrency(
                                            invoice.total_amount,
                                            invoice.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                                <td class="py-2 text-end">
                                    <RowActionsMenu
                                        :actions="invoiceActions(invoice)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
