<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { V2Panel, V2StatCard, V2StatGrid } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn, formatNumber } from '@/lib/format';
import { translateBidStatus, translateProjectStatus } from '@/lib/status-labels';

export interface SystemReportMetric {
    key: string;
    label_key: string;
    value: number;
    format: 'number' | 'currency' | 'percent' | 'text';
}

export interface SystemReportColumn {
    key: string;
    label_key: string;
    format?: string;
}

export interface SystemReportTable {
    key: string;
    title_key: string;
    columns: SystemReportColumn[];
    rows: Array<Record<string, string | number | null>>;
}

export interface SystemReportDetailLink {
    key: string;
    title_key: string;
    screen_href: string;
    print_href: string | null;
}

export interface SystemReportSection {
    key: string;
    module_label_key: string;
    period_label?: string;
    metrics: SystemReportMetric[];
    tables: SystemReportTable[];
    detail_links?: SystemReportDetailLink[];
}

const props = withDefaults(
    defineProps<{
        sections: SystemReportSection[];
        year: number;
        printable?: boolean;
        showPrintActions?: boolean;
    }>(),
    {
        printable: false,
        showPrintActions: true,
    },
);

const { t } = useMisPage();

const statementLabels: Record<string, string> = {
    project_income: 'Project Income',
    general_income: 'Other Income',
    total_income: 'Total Income',
    project_expense: 'Project Expenses',
    overhead: 'Overhead & Salaries',
    total_expense: 'Total Expenses',
    operating_net: 'Operating net',
    tax_due: 'Tax due',
    tax_paid: 'Tax paid',
    net_after_tax: 'Net after tax',
};

function formatMetric(metric: SystemReportMetric): string {
    if (metric.format === 'currency') {
        return formatAfn(metric.value);
    }

    if (metric.format === 'percent') {
        return `${formatNumber(metric.value, { maximumFractionDigits: 1 })}%`;
    }

    return formatNumber(metric.value);
}

function formatCell(
    column: SystemReportColumn,
    value: string | number | null | undefined,
): string {
    if (value == null || value === '') {
        return '—';
    }

    if (column.format === 'currency') {
        return formatAfn(Number(value));
    }

    if (column.format === 'statement_key') {
        const key = String(value);
        const label = statementLabels[key];

        return label ? t(label) : key;
    }

    if (column.key === 'status' && typeof value === 'string') {
        return translateBidStatus(t, value) || translateProjectStatus(t, value) || value;
    }

    return String(value);
}

function sectionPrintHref(sectionKey: string): string {
    return `/analytics/reports/print?year=${props.year}&module=${sectionKey}&autoprint=1`;
}

function printHref(href: string): string {
    return href.includes('?') ? `${href}&autoprint=1` : `${href}?autoprint=1`;
}
</script>

<template>
    <div :class="printable ? 'system-report-print' : 'space-y-6'">
        <section
            v-for="section in sections"
            :key="section.key"
            :id="`report-section-${section.key}`"
            class="report-section"
            :data-section="section.key"
        >
            <V2Panel v-if="!printable">
                <template #header>
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <h2 class="text-base font-semibold">
                            {{ t(section.module_label_key) }}
                        </h2>
                        <span
                            v-if="section.period_label"
                            class="text-sm font-normal text-muted-foreground"
                        >
                            {{ section.period_label }}
                        </span>
                    </div>
                </template>
                <template v-if="showPrintActions" #actions>
                    <Button variant="outline" size="sm" as-child>
                        <a
                            :href="sectionPrintHref(section.key)"
                            target="_blank"
                            rel="noopener"
                        >
                            <Printer class="size-3.5" />
                            {{ t('reports.print_section') }}
                        </a>
                    </Button>
                </template>

                <V2StatGrid class="mb-4">
                    <V2StatCard
                        v-for="metric in section.metrics"
                        :key="metric.key"
                        :title="t(metric.label_key)"
                        :value="formatMetric(metric)"
                    />
                </V2StatGrid>

                <div
                    v-for="table in section.tables"
                    :key="table.key"
                    class="mb-4 last:mb-0"
                >
                    <h3 class="mb-2 text-sm font-semibold text-foreground">
                        {{ t(table.title_key) }}
                    </h3>
                    <div class="overflow-x-auto rounded-md border">
                        <table class="w-full text-sm">
                            <thead
                                class="border-b bg-muted/40 text-start text-muted-foreground"
                            >
                                <tr>
                                    <th
                                        v-for="col in table.columns"
                                        :key="col.key"
                                        class="px-3 py-2 font-medium"
                                    >
                                        {{ t(col.label_key) }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, rowIndex) in table.rows"
                                    :key="rowIndex"
                                    class="border-b last:border-b-0"
                                >
                                    <td
                                        v-for="col in table.columns"
                                        :key="col.key"
                                        class="px-3 py-2"
                                    >
                                        {{ formatCell(col, row[col.key]) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    v-if="section.detail_links?.length"
                    class="mt-4 border-t pt-4"
                >
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ t('reports.detailed_documents') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="link in section.detail_links"
                            :key="link.key"
                            variant="ghost"
                            size="sm"
                            as-child
                        >
                            <Link :href="link.screen_href">
                                {{ t(link.title_key) }}
                            </Link>
                        </Button>
                        <Button
                            v-for="link in section.detail_links.filter((l) => l.print_href)"
                            :key="`${link.key}-print`"
                            variant="outline"
                            size="sm"
                            as-child
                        >
                            <a
                                :href="printHref(link.print_href!)"
                                target="_blank"
                                rel="noopener"
                            >
                                {{ t('Print') }} — {{ t(link.title_key) }}
                            </a>
                        </Button>
                    </div>
                </div>
            </V2Panel>

            <article v-else class="print-section-block">
                <h2 class="print-section-title">
                    {{ t(section.module_label_key) }}
                    <span v-if="section.period_label" class="print-period">
                        ({{ section.period_label }})
                    </span>
                </h2>

                <table class="print-metrics-table">
                    <tbody>
                        <tr
                            v-for="metric in section.metrics"
                            :key="metric.key"
                        >
                            <td class="col-name">{{ t(metric.label_key) }}</td>
                            <td class="col-value">{{ formatMetric(metric) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div v-for="table in section.tables" :key="table.key">
                    <h3 class="print-table-title">{{ t(table.title_key) }}</h3>
                    <table class="print-data-table">
                        <thead>
                            <tr>
                                <th
                                    v-for="col in table.columns"
                                    :key="col.key"
                                >
                                    {{ t(col.label_key) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, rowIndex) in table.rows"
                                :key="rowIndex"
                            >
                                <td
                                    v-for="col in table.columns"
                                    :key="col.key"
                                >
                                    {{ formatCell(col, row[col.key]) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>
</template>

<style scoped>
.print-section-block {
    break-inside: avoid-page;
    margin-bottom: 1.25rem;
}

.print-section-title {
    margin: 0 0 0.5rem;
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.print-period {
    font-weight: 500;
    text-transform: none;
}

.print-metrics-table,
.print-data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.72rem;
    margin-bottom: 0.75rem;
}

.print-metrics-table td,
.print-data-table th,
.print-data-table td {
    border: 1px solid #ccc;
    padding: 0.3rem 0.45rem;
}

.print-data-table th {
    background: #f3f4f6;
    text-align: start;
}

.print-table-title {
    margin: 0.65rem 0 0.35rem;
    font-size: 0.78rem;
    font-weight: 600;
}

.col-name {
    width: 55%;
}

.col-value {
    text-align: end;
    font-weight: 600;
}
</style>
