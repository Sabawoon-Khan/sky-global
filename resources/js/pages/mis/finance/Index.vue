<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
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

interface FinanceSummary {
    total_income?: number;
    total_expenses?: number;
    total_invoices?: number;
    outstanding?: number;
}

interface Props {
    summary?: FinanceSummary;
    incomes?: Income[];
    expenses?: Expense[];
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
    { id: 'income' as const, label: t('Income') },
    { id: 'expenses' as const, label: t('Expenses') },
    { id: 'invoices' as const, label: t('Invoices') },
]);

type TabId = 'income' | 'expenses' | 'invoices';

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
            :description="t('Track project income, expenses, and invoices')"
        />

        <div v-if="summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Total Income') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-lg font-semibold text-green-600 dark:text-green-400">
                    {{ formatCurrency(summary.total_income) }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Total Expenses') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-lg font-semibold text-destructive">
                    {{ formatCurrency(summary.total_expenses) }}
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
