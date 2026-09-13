<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import { Button } from '@/components/ui/button';
import MisExportActions from '@/components/mis/MisExportActions.vue';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn, formatNumber } from '@/lib/format';

interface StatementRow {
    key: string;
    amount: number;
    style: 'item' | 'subtotal' | 'emphasis' | 'total';
}

interface ProjectRow {
    id: number;
    code: string;
    name: string;
    organization?: string | null;
    income: number;
    expense: number;
    margin: number;
    margin_percent?: number;
}

const props = defineProps<{
    year: number | null;
    year_label: string;
    generated_on: string;
    company: { name: string };
    stats: {
        total_income: number;
        total_expense: number;
        operating_net: number;
        net_after_tax?: number;
        margin_percent?: number;
        outstanding?: number;
    };
    statement: StatementRow[];
    currencies: Array<{
        currency: string;
        income: number;
        expense: number;
        net: number;
    }>;
    invoices: {
        billed: number;
        collected: number;
        outstanding: number;
        overdue: number;
    };
    tax: {
        rate_percent: number;
        due: number;
        paid: number;
        remaining: number;
    };
    charts: {
        income_by_category: Array<{ category: string; value: number }>;
        expense_by_category: Array<{ category: string; value: number }>;
    };
    projectProfitability: ProjectRow[];
}>();

const { t } = useMisPage();

const periodLabel = computed(() =>
    props.year === null ? t('All years') : props.year_label,
);

const projectTotals = computed(() =>
    props.projectProfitability.reduce(
        (sum, row) => ({
            income: sum.income + row.income,
            expense: sum.expense + row.expense,
            margin: sum.margin + row.margin,
        }),
        { income: 0, expense: 0, margin: 0 },
    ),
);

const statementLabel = (key: string): string => {
    const labels: Record<string, string> = {
        project_income: t('Project Income'),
        general_income: t('Other Income'),
        total_income: t('Total Income'),
        project_expense: t('Project Expenses'),
        overhead: t('Overhead & Salaries'),
        total_expense: t('Total Expenses'),
        operating_net: t('Operating net'),
        tax_due: t('Tax due'),
        tax_paid: t('Tax paid'),
        net_after_tax: t('Net after tax'),
    };

    return labels[key] ?? key;
};

const categoryLabel = (category: string): string =>
    category === 'Uncategorized' ? t('Uncategorized') : category;

const formatPercent = (value?: number | null): string => {
    if (value == null) {
        return '—';
    }

    return `${formatNumber(value, { maximumFractionDigits: 1 })}%`;
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('autoprint') === '1') {
        window.setTimeout(() => window.print(), 400);
    }
});
</script>

<template>
    <Head :title="t('Finance report')" />

    <div class="sheet-page">
        <div class="sheet-toolbar no-print">
            <Button variant="ghost" size="sm" as-child>
                <Link
                    :href="`/analytics/finance?year=${year === null ? 'all' : year}`"
                >
                    <ArrowLeft class="size-4" />
                    {{ t('Finance Analytics') }}
                </Link>
            </Button>
            <div class="flex items-center gap-2">
                <span class="period-hint">{{ periodLabel }}</span>
                <MisExportActions variant="outline" filename="finance-report" />
            </div>
        </div>

        <article class="sheet-document" data-export-root>
            <header class="sheet-header">
                <AppLogoImage class="sheet-logo sheet-logo--left" />
                <div class="sheet-header-center">
                    <h1 class="sheet-title">{{ t('Finance report') }}</h1>
                    <p class="sheet-subtitle">{{ company.name }}</p>
                    <p class="sheet-subtitle">{{ periodLabel }}</p>
                </div>
                <AppLogoImage class="sheet-logo sheet-logo--right" />
            </header>

            <div class="sheet-meta">
                <p>
                    <strong>{{ t('Period') }}:</strong>
                    {{ periodLabel }}
                </p>
                <p>
                    <strong>{{ t('Margin %') }}:</strong>
                    {{ formatPercent(stats.margin_percent) }}
                </p>
                <p>
                    <strong>{{ t('Date') }}:</strong>
                    {{ generated_on }}
                </p>
            </div>

            <h2 class="sheet-section">{{ t('Profit and Loss') }}</h2>
            <div class="sheet-table-wrap">
                <table class="sheet-table">
                    <thead>
                        <tr>
                            <th>{{ t('Field') }}</th>
                            <th>{{ t('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in statement"
                            :key="row.key"
                            :class="`is-${row.style}`"
                        >
                            <td class="col-name">{{ statementLabel(row.key) }}</td>
                            <td class="col-amount">{{ formatAfn(row.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="sheet-grid">
                <div>
                    <h2 class="sheet-section">{{ t('Invoices') }}</h2>
                    <table class="sheet-table">
                        <tbody>
                            <tr>
                                <td class="col-name">{{ t('Billed') }}</td>
                                <td class="col-amount">
                                    {{ formatAfn(invoices.billed) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="col-name">{{ t('Collected') }}</td>
                                <td class="col-amount">
                                    {{ formatAfn(invoices.collected) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="col-name">{{ t('Outstanding') }}</td>
                                <td class="col-amount">
                                    {{ formatAfn(invoices.outstanding) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="col-name">{{ t('Overdue') }}</td>
                                <td class="col-amount">
                                    {{ formatAfn(invoices.overdue) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h2 class="sheet-section">{{ t('Tax') }}</h2>
                    <table class="sheet-table">
                        <tbody>
                            <tr>
                                <td class="col-name">
                                    {{ t('Tax due') }} · {{ tax.rate_percent }}%
                                </td>
                                <td class="col-amount">{{ formatAfn(tax.due) }}</td>
                            </tr>
                            <tr>
                                <td class="col-name">{{ t('Tax paid') }}</td>
                                <td class="col-amount">{{ formatAfn(tax.paid) }}</td>
                            </tr>
                            <tr>
                                <td class="col-name">{{ t('Tax remaining') }}</td>
                                <td class="col-amount">
                                    {{ formatAfn(tax.remaining) }}
                                </td>
                            </tr>
                            <tr class="is-total">
                                <td class="col-name">{{ t('Net after tax') }}</td>
                                <td class="col-amount">
                                    {{ formatAfn(stats.net_after_tax) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <template v-if="currencies.length">
                <h2 class="sheet-section">{{ t('Currency') }}</h2>
                <div class="sheet-table-wrap">
                    <table class="sheet-table">
                        <thead>
                            <tr>
                                <th>{{ t('Currency') }}</th>
                                <th>{{ t('Income') }}</th>
                                <th>{{ t('Expenses') }}</th>
                                <th>{{ t('Net') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in currencies" :key="row.currency">
                                <td class="col-name">{{ row.currency }}</td>
                                <td class="col-amount">{{ formatAfn(row.income) }}</td>
                                <td class="col-amount">{{ formatAfn(row.expense) }}</td>
                                <td class="col-amount col-net">
                                    {{ formatAfn(row.net) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <div
                v-if="
                    charts.income_by_category.length ||
                    charts.expense_by_category.length
                "
                class="sheet-grid"
            >
                <div v-if="charts.income_by_category.length">
                    <h2 class="sheet-section">{{ t('Income by category') }}</h2>
                    <table class="sheet-table">
                        <thead>
                            <tr>
                                <th>{{ t('Category') }}</th>
                                <th>{{ t('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in charts.income_by_category"
                                :key="row.category"
                            >
                                <td class="col-name">
                                    {{ categoryLabel(row.category) }}
                                </td>
                                <td class="col-amount">{{ formatAfn(row.value) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="charts.expense_by_category.length">
                    <h2 class="sheet-section">{{ t('Expenses by category') }}</h2>
                    <table class="sheet-table">
                        <thead>
                            <tr>
                                <th>{{ t('Category') }}</th>
                                <th>{{ t('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in charts.expense_by_category"
                                :key="row.category"
                            >
                                <td class="col-name">
                                    {{ categoryLabel(row.category) }}
                                </td>
                                <td class="col-amount">{{ formatAfn(row.value) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <h2 class="sheet-section">{{ t('Project Profitability') }}</h2>
            <p v-if="!projectProfitability.length" class="sheet-empty">
                {{ t('No profitability data available.') }}
            </p>
            <div v-else class="sheet-table-wrap">
                <table class="sheet-table">
                    <thead>
                        <tr>
                            <th>{{ t('Project') }}</th>
                            <th>{{ t('Client') }}</th>
                            <th>{{ t('Income') }}</th>
                            <th>{{ t('Expense') }}</th>
                            <th>{{ t('Margin') }}</th>
                            <th>{{ t('Margin %') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="project in projectProfitability" :key="project.id">
                            <td class="col-name">
                                {{ project.code }}
                                <div class="project-name">{{ project.name }}</div>
                            </td>
                            <td>{{ project.organization ?? '—' }}</td>
                            <td class="col-amount">{{ formatAfn(project.income) }}</td>
                            <td class="col-amount">{{ formatAfn(project.expense) }}</td>
                            <td class="col-amount">{{ formatAfn(project.margin) }}</td>
                            <td class="col-amount">
                                {{ formatPercent(project.margin_percent) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="totals-row">
                            <td class="col-name font-bold" colspan="2">
                                {{ t('Total') }}
                            </td>
                            <td class="col-amount font-bold">
                                {{ formatAfn(projectTotals.income) }}
                            </td>
                            <td class="col-amount font-bold">
                                {{ formatAfn(projectTotals.expense) }}
                            </td>
                            <td class="col-amount font-bold">
                                {{ formatAfn(projectTotals.margin) }}
                            </td>
                            <td class="col-amount font-bold">
                                {{
                                    formatPercent(
                                        projectTotals.income > 0
                                            ? (projectTotals.margin /
                                                  projectTotals.income) *
                                                  100
                                            : 0,
                                    )
                                }}
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
    margin: 0.15rem 0 0;
    font-size: 0.8rem;
    color: #444;
}

.sheet-meta {
    font-size: 0.8rem;
    line-height: 1.6;
    margin-bottom: 0.5rem;
    color: #111;
}

.sheet-meta p {
    margin: 0;
}

.sheet-section {
    margin: 1rem 0 0.4rem;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #111;
}

.sheet-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: 1fr 1fr;
}

.sheet-empty {
    padding: 1.5rem 1rem;
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

.col-net,
.is-emphasis td,
.is-total td,
.totals-row td {
    background: #efefef;
    font-weight: 700;
}

.is-subtotal td {
    background: #f3f4f6;
    font-weight: 700;
}

.project-name {
    font-size: 0.7rem;
    font-weight: 400;
    color: #444;
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

@media (max-width: 720px) {
    .sheet-grid {
        grid-template-columns: 1fr;
    }
}
</style>
