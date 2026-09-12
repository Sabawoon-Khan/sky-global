<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import BarChart from '@/components/charts/BarChart.vue';
import { V2Hero, V2ListPage, V2StatCard, V2StatGrid } from '@/components/v2';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn } from '@/lib/format';
import { Percent, Wallet } from '@lucide/vue';

interface CompanyTaxPeriod {
    rate: number;
    rate_percent: number;
    income: number;
    expenses: number;
    taxable_profit: number;
    tax_due: number;
    net_after_tax: number;
    quarter?: number;
    year?: number;
    label?: string;
    period_start?: string;
    period_end?: string;
}

interface CompanyTaxReport {
    current_year: number;
    rate_percent: number;
    year: CompanyTaxPeriod;
    quarters: CompanyTaxPeriod[];
    years: CompanyTaxPeriod[];
}

defineProps<{
    tax?: CompanyTaxReport;
}>();

const { t } = useMisPage();

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
            <template #description>
                {{
                    t(
                        'Corporate income tax is estimated at 20% of taxable profit (income minus expenses) under Afghanistan Income Tax Law.',
                    )
                }}
            </template>
            <template #stats>
                <V2StatGrid v-if="tax">
                    <V2StatCard
                        :delay="0"
                        icon-tone="warm"
                        :title="`${t('Year')} ${tax.current_year}`"
                        :value="formatAfn(tax.year.tax_due)"
                    >
                        <template #icon><Percent /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        :title="t('Taxable profit')"
                        :value="formatAfn(tax.year.taxable_profit)"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Net after tax')"
                        :value="formatAfn(tax.year.net_after_tax)"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        :title="t('Tax rate')"
                        :value="`${tax.rate_percent}%`"
                    >
                        <template #icon><Percent /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <div class="space-y-4">
            <div>
                <h2 class="text-base font-semibold tracking-tight">
                    {{ t('Afghanistan company tax') }}
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{
                        t(
                            'Corporate income tax is estimated at 20% of taxable profit (income minus expenses) under Afghanistan Income Tax Law.',
                        )
                    }}
                </p>
            </div>

            <div v-if="!tax" class="ui-empty-state rounded-md border">
                {{ t('No summary data available.') }}
            </div>

            <template v-else>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium">
                                {{ t('Year') }} {{ tax.current_year }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p
                                class="text-2xl font-bold tabular-nums text-destructive"
                            >
                                {{ formatAfn(tax.year.tax_due) }}
                            </p>
                            <CardDescription class="mt-1">
                                {{ t('Company tax due') }}
                            </CardDescription>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium">
                                {{ t('Taxable profit') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold tabular-nums">
                                {{ formatAfn(tax.year.taxable_profit) }}
                            </p>
                            <CardDescription class="mt-1">
                                {{ t('Income') }} − {{ t('Expenses') }}
                            </CardDescription>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium">
                                {{ t('Net after tax') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p
                                class="text-2xl font-bold tabular-nums"
                                :class="
                                    tax.year.net_after_tax >= 0
                                        ? 'text-green-600 dark:text-green-400'
                                        : 'text-destructive'
                                "
                            >
                                {{ formatAfn(tax.year.net_after_tax) }}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium">
                                {{ t('Tax rate') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold tabular-nums">
                                {{ tax.rate_percent }}%
                            </p>
                            <CardDescription class="mt-1">
                                {{ t('Flat corporate rate') }}
                            </CardDescription>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">
                            {{ t('Quarterly tax') }} — {{ tax.current_year }}
                        </CardTitle>
                        <CardDescription>
                            {{
                                t(
                                    'Estimated company tax by quarter for the current year.',
                                )
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead
                                    class="border-b bg-muted/40 text-start text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-3 py-2 font-medium">
                                            {{ t('Quarter') }}
                                        </th>
                                        <th
                                            class="px-3 py-2 text-end font-medium"
                                        >
                                            {{ t('Income') }}
                                        </th>
                                        <th
                                            class="px-3 py-2 text-end font-medium"
                                        >
                                            {{ t('Expenses') }}
                                        </th>
                                        <th
                                            class="px-3 py-2 text-end font-medium"
                                        >
                                            {{ t('Taxable profit') }}
                                        </th>
                                        <th
                                            class="px-3 py-2 text-end font-medium"
                                        >
                                            {{ t('Tax due') }}
                                        </th>
                                        <th
                                            class="px-3 py-2 text-end font-medium"
                                        >
                                            {{ t('Net after tax') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="row in tax.quarters"
                                        :key="row.label"
                                        class="hover:bg-muted/30"
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
                                            class="px-3 py-2 text-end font-semibold"
                                        >
                                            {{ formatAfn(row.net_after_tax) }}
                                        </td>
                                    </tr>
                                    <tr class="bg-muted/20 font-semibold">
                                        <td class="px-3 py-2">
                                            {{ t('Year total') }}
                                        </td>
                                        <td class="px-3 py-2 text-end">
                                            {{ formatAfn(tax.year.income) }}
                                        </td>
                                        <td class="px-3 py-2 text-end">
                                            {{ formatAfn(tax.year.expenses) }}
                                        </td>
                                        <td class="px-3 py-2 text-end">
                                            {{
                                                formatAfn(
                                                    tax.year.taxable_profit,
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-3 py-2 text-end text-destructive"
                                        >
                                            {{ formatAfn(tax.year.tax_due) }}
                                        </td>
                                        <td class="px-3 py-2 text-end">
                                            {{
                                                formatAfn(
                                                    tax.year.net_after_tax,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">
                            {{ t('Yearly tax') }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('Company tax totals for the last 3 years.') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <BarChart
                            :labels="
                                tax.years.map(
                                    (y) => y.label ?? String(y.year),
                                )
                            "
                            :datasets="[
                                {
                                    label: t('Tax due'),
                                    data: tax.years.map((y) => y.tax_due),
                                    backgroundColor: 'rgba(220, 38, 38, 0.7)',
                                },
                                {
                                    label: t('Taxable profit'),
                                    data: tax.years.map((y) =>
                                        Math.max(0, y.taxable_profit),
                                    ),
                                    backgroundColor: 'rgba(82, 82, 82, 0.55)',
                                },
                            ]"
                        />
                        <div class="overflow-x-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead
                                    class="border-b bg-muted/40 text-start text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-3 py-2 font-medium">
                                            {{ t('Year') }}
                                        </th>
                                        <th
                                            class="px-3 py-2 text-end font-medium"
                                        >
                                            {{ t('Income') }}
                                        </th>
                                        <th
                                            class="px-3 py-2 text-end font-medium"
                                        >
                                            {{ t('Expenses') }}
                                        </th>
                                        <th
                                            class="px-3 py-2 text-end font-medium"
                                        >
                                            {{ t('Taxable profit') }}
                                        </th>
                                        <th
                                            class="px-3 py-2 text-end font-medium"
                                        >
                                            {{ t('Tax due') }}
                                        </th>
                                        <th
                                            class="px-3 py-2 text-end font-medium"
                                        >
                                            {{ t('Net after tax') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="row in tax.years"
                                        :key="row.year"
                                        class="hover:bg-muted/30"
                                    >
                                        <td class="px-3 py-2 font-medium">
                                            {{ row.year }}
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
                                            class="px-3 py-2 text-end font-semibold"
                                        >
                                            {{ formatAfn(row.net_after_tax) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Percent class="size-5" />
                            {{ t('Afghanistan tax rules') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead
                                    class="border-b bg-muted/40 text-start text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-3 py-2 font-medium">
                                            {{ t('Rule') }}
                                        </th>
                                        <th class="px-3 py-2 font-medium">
                                            {{ t('Details') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr>
                                        <td class="px-3 py-2 font-medium">
                                            {{ t('Tax rate') }}
                                        </td>
                                        <td
                                            class="px-3 py-2 text-muted-foreground"
                                        >
                                            {{
                                                t(
                                                    '20% flat corporate income tax on taxable profit',
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-medium">
                                            {{ t('Taxable base') }}
                                        </td>
                                        <td
                                            class="px-3 py-2 text-muted-foreground"
                                        >
                                            {{
                                                t(
                                                    'Total income minus total expenses (project + overhead)',
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-medium">
                                            {{ t('Quarterly') }}
                                        </td>
                                        <td
                                            class="px-3 py-2 text-muted-foreground"
                                        >
                                            {{
                                                t(
                                                    'Q1 Jan–Mar, Q2 Apr–Jun, Q3 Jul–Sep, Q4 Oct–Dec',
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-medium">
                                            {{ t('Losses') }}
                                        </td>
                                        <td
                                            class="px-3 py-2 text-muted-foreground"
                                        >
                                            {{
                                                t(
                                                    'If expenses exceed income, estimated company tax is 0',
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>
    </V2ListPage>
</template>
