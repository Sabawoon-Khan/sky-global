<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { cn } from '@/lib/utils';

interface Income {
    id: number;
    description: string;
    amount: number;
    amount_usd?: number | null;
    currency?: string | null;
    received_at?: string | null;
    project?: { id: number; code: string; name: string } | null;
}

interface Expense {
    id: number;
    description: string;
    amount: number;
    amount_usd?: number | null;
    currency?: string | null;
    incurred_at?: string | null;
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

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Finance', href: '/finance' }],
    },
});

const tabs = [
    { id: 'income', label: 'Income' },
    { id: 'expenses', label: 'Expenses' },
    { id: 'invoices', label: 'Invoices' },
] as const;

type TabId = (typeof tabs)[number]['id'];

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
</script>

<template>
    <Head title="Finance" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Finance"
            description="Track project income, expenses, and invoices"
        />

        <div v-if="summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">Total Income</CardTitle>
                </CardHeader>
                <CardContent class="text-lg font-semibold text-green-600 dark:text-green-400">
                    {{ formatCurrency(summary.total_income) }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">Total Expenses</CardTitle>
                </CardHeader>
                <CardContent class="text-lg font-semibold text-destructive">
                    {{ formatCurrency(summary.total_expenses) }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">Invoices</CardTitle>
                </CardHeader>
                <CardContent class="text-lg font-semibold">
                    {{ summary.total_invoices ?? 0 }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">Outstanding</CardTitle>
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
                <CardTitle>Project Income</CardTitle>
                <CardDescription>Recorded payments and receipts</CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="!incomes?.length"
                    class="text-sm text-muted-foreground"
                >
                    No income records found.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Description</th>
                                <th class="pb-2 pr-4 font-medium">Project</th>
                                <th class="pb-2 pr-4 font-medium">Date</th>
                                <th class="pb-2 text-right font-medium">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in incomes"
                                :key="item.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-2 pr-4">{{ item.description }}</td>
                                <td class="py-2 pr-4 text-muted-foreground">
                                    {{ item.project?.code ?? '—' }}
                                </td>
                                <td class="py-2 pr-4 text-muted-foreground">
                                    {{ formatDate(item.received_at) }}
                                </td>
                                <td class="py-2 text-right font-medium">
                                    {{
                                        formatCurrency(
                                            item.amount_usd ?? item.amount,
                                            item.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'expenses'">
            <CardHeader>
                <CardTitle>Project Expenses</CardTitle>
                <CardDescription>Operational and project costs</CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="!expenses?.length"
                    class="text-sm text-muted-foreground"
                >
                    No expense records found.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Description</th>
                                <th class="pb-2 pr-4 font-medium">Project</th>
                                <th class="pb-2 pr-4 font-medium">Date</th>
                                <th class="pb-2 text-right font-medium">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in expenses"
                                :key="item.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-2 pr-4">{{ item.description }}</td>
                                <td class="py-2 pr-4 text-muted-foreground">
                                    {{ item.project?.code ?? '—' }}
                                </td>
                                <td class="py-2 pr-4 text-muted-foreground">
                                    {{ formatDate(item.incurred_at) }}
                                </td>
                                <td class="py-2 text-right font-medium">
                                    {{
                                        formatCurrency(
                                            item.amount_usd ?? item.amount,
                                            item.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card v-else>
            <CardHeader>
                <CardTitle>Invoices</CardTitle>
                <CardDescription>Client billing and payment status</CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="!invoices?.length"
                    class="text-sm text-muted-foreground"
                >
                    No invoices found.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Invoice #</th>
                                <th class="pb-2 pr-4 font-medium">Client</th>
                                <th class="pb-2 pr-4 font-medium">Due Date</th>
                                <th class="pb-2 pr-4 font-medium">Status</th>
                                <th class="pb-2 text-right font-medium">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="invoice in invoices"
                                :key="invoice.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-2 pr-4 font-medium">
                                    {{ invoice.invoice_number ?? `#${invoice.id}` }}
                                </td>
                                <td class="py-2 pr-4 text-muted-foreground">
                                    {{ invoice.organization?.name ?? '—' }}
                                </td>
                                <td class="py-2 pr-4 text-muted-foreground">
                                    {{ formatDate(invoice.due_date) }}
                                </td>
                                <td class="py-2 pr-4">
                                    <Badge variant="outline">
                                        {{ invoice.status }}
                                    </Badge>
                                </td>
                                <td class="py-2 text-right font-medium">
                                    {{
                                        formatCurrency(
                                            invoice.total_amount,
                                            invoice.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
