<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { DollarSign, PieChart } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useMisPage } from '@/composables/useMisPage';

interface ProjectProfitability {
    id: number;
    code: string;
    name: string;
    organization?: string | null;
    income: number;
    expense: number;
    margin: number;
}

interface FinanceSummary {
    total_income?: number;
    total_expense?: number;
    overhead?: number;
    net_margin?: number;
}

interface Props {
    summary?: FinanceSummary;
    projectProfitability: ProjectProfitability[];
}

defineProps<Props>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Analytics', href: '/analytics/bidding' },
            { title: 'Finance', href: '/analytics/finance' },
        ],
    },
});

const formatCurrency = (value?: number | null): string => {
    if (value == null) {
        return '—';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(value);
};
</script>

<template>
    <Head :title="t('Finance Analytics')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                :title="t('Finance Analytics')"
                :description="t('Revenue, expenses, and project profitability')"
            />
            <Button variant="outline" as-child>
                <Link href="/analytics/bidding">{{ t('Bidding Analytics') }}</Link>
            </Button>
        </div>

        <div v-if="summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Total Income') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ formatCurrency(summary.total_income) }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Project Expenses') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-bold text-destructive">
                    {{ formatCurrency(summary.total_expense) }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Overhead') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-bold">
                    {{ formatCurrency(summary.overhead) }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm">{{ t('Net Margin') }}</CardTitle>
                    <DollarSign class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent class="text-2xl font-bold">
                    {{ formatCurrency(summary.net_margin) }}
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <PieChart class="size-5" />
                    {{ t('Project Profitability') }}
                </CardTitle>
                <CardDescription>
                    {{ t('Income vs expense by project') }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="projectProfitability.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No profitability data available.') }}
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-start text-muted-foreground">
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Project')
                                }}</th>
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Client')
                                }}</th>
                                <th class="pb-3 pe-4 text-end font-medium">
                                    {{ t('Income') }}
                                </th>
                                <th class="pb-3 pe-4 text-end font-medium">
                                    {{ t('Expense') }}
                                </th>
                                <th class="pb-3 text-end font-medium">{{
                                    t('Margin')
                                }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="project in projectProfitability"
                                :key="project.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pe-4">
                                    <Link
                                        :href="`/projects/${project.id}`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ project.code }}
                                    </Link>
                                    <div class="text-xs text-muted-foreground">
                                        {{ project.name }}
                                    </div>
                                </td>
                                <td class="py-3 pe-4 text-muted-foreground">
                                    {{ project.organization ?? '—' }}
                                </td>
                                <td class="py-3 pe-4 text-end">
                                    {{ formatCurrency(project.income) }}
                                </td>
                                <td class="py-3 pe-4 text-end">
                                    {{ formatCurrency(project.expense) }}
                                </td>
                                <td
                                    class="py-3 text-end font-medium"
                                    :class="
                                        project.margin >= 0
                                            ? 'text-green-600 dark:text-green-400'
                                            : 'text-destructive'
                                    "
                                >
                                    {{ formatCurrency(project.margin) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
