<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Wallet } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface PayrollItem {
    id: number;
    personnel_type: string;
    personnel_id: number;
    personnel_name?: string | null;
    project?: { id: number; code: string } | null;
    base_amount: number;
    deductions: number;
    net_amount: number;
    currency?: string | null;
}

interface PayrollRun {
    id: number;
    period_year: number;
    period_month: number;
    status: string;
    processed_by?: { name: string } | null;
    items?: PayrollItem[];
    items_count?: number;
    total_net?: number;
}

interface Props {
    payrollRuns: PayrollRun[];
}

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Payroll', href: '/hr/payroll' },
        ],
    },
});

const monthName = (month: number): string =>
    new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
        new Date(2000, month - 1, 1),
    );

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
</script>

<template>
    <Head title="Payroll" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Payroll"
            description="Monthly payroll runs and disbursements"
        />

        <div
            v-if="payrollRuns.length === 0"
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            No payroll runs recorded.
        </div>

        <div v-else class="space-y-4">
            <Card v-for="run in payrollRuns" :key="run.id">
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Wallet class="size-5" />
                                {{ monthName(run.period_month) }}
                                {{ run.period_year }}
                            </CardTitle>
                            <CardDescription>
                                Processed by
                                {{ run.processed_by?.name ?? '—' }} ·
                                {{ run.items_count ?? run.items?.length ?? 0 }}
                                items
                            </CardDescription>
                        </div>
                        <Badge>{{ run.status }}</Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="run.total_net != null"
                        class="mb-4 text-sm font-medium"
                    >
                        Total net: {{ formatCurrency(run.total_net) }}
                    </div>

                    <div
                        v-if="!run.items?.length"
                        class="text-sm text-muted-foreground"
                    >
                        No line items for this run.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="pb-2 pr-4 font-medium">Personnel</th>
                                    <th class="pb-2 pr-4 font-medium">Type</th>
                                    <th class="pb-2 pr-4 font-medium">Project</th>
                                    <th class="pb-2 pr-4 text-right font-medium">
                                        Base
                                    </th>
                                    <th class="pb-2 pr-4 text-right font-medium">
                                        Deductions
                                    </th>
                                    <th class="pb-2 text-right font-medium">Net</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in run.items"
                                    :key="item.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 pr-4">
                                        {{
                                            item.personnel_name ??
                                            `#${item.personnel_id}`
                                        }}
                                    </td>
                                    <td class="py-2 pr-4 capitalize text-muted-foreground">
                                        {{ item.personnel_type }}
                                    </td>
                                    <td class="py-2 pr-4 text-muted-foreground">
                                        {{ item.project?.code ?? '—' }}
                                    </td>
                                    <td class="py-2 pr-4 text-right">
                                        {{
                                            formatCurrency(
                                                item.base_amount,
                                                item.currency ?? 'USD',
                                            )
                                        }}
                                    </td>
                                    <td class="py-2 pr-4 text-right text-destructive">
                                        {{
                                            formatCurrency(
                                                item.deductions,
                                                item.currency ?? 'USD',
                                            )
                                        }}
                                    </td>
                                    <td class="py-2 text-right font-medium">
                                        {{
                                            formatCurrency(
                                                item.net_amount,
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
        </div>
    </div>
</template>
