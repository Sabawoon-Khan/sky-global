<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import { Button } from '@/components/ui/button';
import MisExportActions from '@/components/mis/MisExportActions.vue';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn } from '@/lib/format';

interface TaxRow {
    label?: string;
    income: number;
    expenses: number;
    taxable_profit: number;
    tax_due: number;
    net_after_tax: number;
    their_share_due?: number;
    company_share_due?: number;
    their_paid?: number;
    company_paid?: number;
}

interface Totals {
    income: number;
    expenses: number;
    taxable_profit: number;
    tax_due: number;
    net_after_tax: number;
}

const props = defineProps<{
    period: string;
    period_label: string;
    rate_percent: number;
    rate_note: string;
    show_split?: boolean;
    rows: TaxRow[];
    totals: Totals;
    generated_on: string;
    current_year: number;
    calendar?: string;
}>();

const { t } = useMisPage();

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('autoprint') === '1') {
        window.setTimeout(() => window.print(), 400);
    }
});
</script>

<template>
    <Head :title="t(period_label)" />

    <div class="sheet-page">
        <div class="sheet-toolbar no-print">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/finance/tax">
                    <ArrowLeft class="size-4" />
                    {{ t('Back to tax') }}
                </Link>
            </Button>
            <div class="flex items-center gap-2">
                <span class="period-hint">{{ t(period_label) }}</span>
                <MisExportActions variant="outline" />
            </div>
        </div>

        <article class="sheet-document" data-export-root>
            <header class="sheet-header">
                <AppLogoImage class="sheet-logo sheet-logo--left" />
                <div class="sheet-header-center">
                    <h1 class="sheet-title">{{ t('Company tax report') }}</h1>
                    <p class="sheet-subtitle">{{ t(period_label) }}</p>
                </div>
                <AppLogoImage class="sheet-logo sheet-logo--right" />
            </header>

            <div class="sheet-meta">
                <p>
                    <strong>{{ t('Period') }}:</strong>
                    {{ t(period_label) }}
                </p>
                <p>
                    <strong>{{ t('Year') }}:</strong>
                    {{ current_year }}
                    {{ t('Hijri Shamsi') }}
                </p>
                <p>
                    <strong>{{ t('Tax rate') }}:</strong>
                    {{ rate_percent }}% — {{ t(rate_note) }}
                </p>
                <p>
                    <strong>{{ t('Date') }}:</strong>
                    {{ generated_on }}
                </p>
            </div>

            <p v-if="!rows.length" class="sheet-empty">
                {{ t('No summary data available.') }}
            </p>

            <div v-else class="sheet-table-wrap">
                <table class="sheet-table">
                    <thead>
                        <tr>
                            <th>{{ t('Period') }}</th>
                            <th>{{ t('Income') }}</th>
                            <th>{{ t('Expenses') }}</th>
                            <th>{{ t('Taxable profit') }}</th>
                            <th>{{ t('Tax due') }}</th>
                            <th v-if="show_split">{{ t('Paid by them') }}</th>
                            <th v-if="show_split">{{ t('Paid by company') }}</th>
                            <th>{{ t('Net after tax') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.label">
                            <td class="col-name">{{ row.label }}</td>
                            <td class="col-amount">
                                {{ formatAfn(row.income) }}
                            </td>
                            <td class="col-amount">
                                {{ formatAfn(row.expenses) }}
                            </td>
                            <td class="col-amount">
                                {{ formatAfn(row.taxable_profit) }}
                            </td>
                            <td class="col-amount">
                                {{ formatAfn(row.tax_due) }}
                            </td>
                            <td v-if="show_split" class="col-amount">
                                {{ formatAfn(row.their_share_due) }}
                            </td>
                            <td v-if="show_split" class="col-amount">
                                {{ formatAfn(row.company_share_due) }}
                            </td>
                            <td class="col-amount col-net">
                                {{ formatAfn(row.net_after_tax) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="totals-row">
                            <td class="col-name font-bold">{{ t('Total') }}</td>
                            <td class="col-amount font-bold">
                                {{ formatAfn(totals.income) }}
                            </td>
                            <td class="col-amount font-bold">
                                {{ formatAfn(totals.expenses) }}
                            </td>
                            <td class="col-amount font-bold">
                                {{ formatAfn(totals.taxable_profit) }}
                            </td>
                            <td class="col-amount font-bold">
                                {{ formatAfn(totals.tax_due) }}
                            </td>
                            <td v-if="show_split" class="col-amount font-bold">
                                {{
                                    formatAfn(
                                        rows.reduce(
                                            (sum, row) =>
                                                sum +
                                                (row.their_share_due ?? 0),
                                            0,
                                        ),
                                    )
                                }}
                            </td>
                            <td v-if="show_split" class="col-amount font-bold">
                                {{
                                    formatAfn(
                                        rows.reduce(
                                            (sum, row) =>
                                                sum +
                                                (row.company_share_due ?? 0),
                                            0,
                                        ),
                                    )
                                }}
                            </td>
                            <td class="col-amount col-net font-bold">
                                {{ formatAfn(totals.net_after_tax) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <footer class="sheet-signatures">
                <p>{{ t('Prepared By') }}: ______________________</p>
                <p>{{ t('Approved By') }}: ______________________</p>
            </footer>
        </article>
    </div>
</template>

<style scoped>
.sheet-page {
    min-height: 100vh;
    background: #e5e7eb;
    padding: 1rem;
}

.sheet-toolbar {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    max-width: 960px;
    margin: 0 auto 0.75rem;
    align-items: center;
}

.period-hint {
    font-size: 0.875rem;
    color: #6b7280;
}

.sheet-document {
    max-width: 960px;
    margin: 0 auto;
    background: #fff;
    padding: 1.25rem 1.5rem 1.75rem;
}

.sheet-header {
    display: grid;
    grid-template-columns: 4rem 1fr 4rem;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.sheet-logo {
    width: 3.5rem;
    height: 3.5rem;
}

.sheet-logo--right {
    justify-self: end;
}

.sheet-header-center {
    text-align: center;
}

.sheet-title {
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.3;
    text-transform: uppercase;
    color: #111;
}

.sheet-subtitle {
    margin: 0.25rem 0 0;
    font-size: 0.8rem;
    color: #444;
}

.sheet-meta {
    font-size: 0.8rem;
    line-height: 1.6;
    margin-bottom: 0.75rem;
    color: #111;
}

.sheet-meta p {
    margin: 0;
}

.sheet-empty {
    padding: 2rem 1rem;
    text-align: center;
    font-size: 0.875rem;
    color: #6b7280;
}

.sheet-table-wrap {
    overflow-x: auto;
}

.sheet-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
    color: #111;
}

.sheet-table th,
.sheet-table td {
    border: 1px solid #111;
    padding: 0.4rem 0.45rem;
    vertical-align: middle;
    text-align: center;
}

.sheet-table thead th {
    background: #d9d9d9;
    font-weight: 700;
    font-size: 0.72rem;
}

.col-name {
    text-align: start;
    min-width: 7rem;
}

.col-amount {
    text-align: end;
    white-space: nowrap;
    font-size: 0.85rem;
    font-weight: 600;
}

.col-net {
    background: #efefef;
    font-weight: 700;
}

.totals-row td {
    background: #f3f4f6;
}

.sheet-signatures {
    display: flex;
    justify-content: space-between;
    gap: 2rem;
    margin-top: 1.5rem;
    font-size: 0.8rem;
    color: #111;
}

.sheet-signatures p {
    margin: 0;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    .sheet-page {
        background: #fff;
        padding: 0;
    }

    .no-print {
        display: none !important;
    }

    .sheet-document {
        padding: 0;
        max-width: none;
    }
}
</style>
