<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import { Button } from '@/components/ui/button';
import MisExportActions from '@/components/mis/MisExportActions.vue';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn, formatCurrency, formatDate } from '@/lib/format';

interface LedgerLine {
    row_number: number;
    description: string;
    category?: string | null;
    date_label: string;
    amount_afn: number;
    amount_usd: number;
    document_ref?: string | null;
    notes?: string | null;
}

interface LedgerSection {
    key: string;
    label: string;
    lines: LedgerLine[];
    subtotal_afn: number;
    subtotal_usd: number;
}

interface LedgerReport {
    period_label: string;
    sections: LedgerSection[];
    totals: {
        income_afn: number;
        income_usd: number;
        tax_afn: number;
        expense_afn: number;
        net_afn: number;
    };
}

const props = defineProps<{
    ledger: LedgerReport;
}>();

const { t } = useMisPage();

onMounted(() => {
    if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
        window.setTimeout(() => window.print(), 400);
    }
});
</script>

<template>
    <Head :title="t('Monthly ledger')" />

    <div
        class="min-h-screen bg-background p-4 text-foreground print:bg-white print:p-0"
    >
        <div
            class="no-print mx-auto mb-3 flex max-w-[1100px] justify-between"
        >
            <Button variant="ghost" size="sm" as-child>
                <Link href="/finance/ledger">
                    <ArrowLeft class="size-4" />
                    {{ t('Back to ledger') }}
                </Link>
            </Button>
            <MisExportActions variant="outline" />
        </div>

        <article
            class="mx-auto max-w-[1100px] rounded-xl border border-border bg-card p-6 text-card-foreground shadow-soft print:max-w-none print:rounded-none print:border-0 print:bg-white print:p-0 print:text-black print:shadow-none"
            dir="rtl"
            data-export-root
        >
            <header
                class="mb-3 flex items-center gap-4 border-b-2 border-primary pb-3 print:border-black"
            >
                <AppLogoImage class="h-12 w-auto" />
                <div class="flex-1 text-center">
                    <h1 class="text-xl font-bold text-foreground print:text-black">
                        {{ t('Monthly list of accounts and expenses') }}
                    </h1>
                </div>
            </header>

            <table class="ledger-print-table w-full border-collapse text-sm">
                <thead>
                    <tr>
                        <th>{{ t('No.') }}</th>
                        <th>{{ t('Details') }}</th>
                        <th>{{ t('Date') }}</th>
                        <th>{{ t('Amount (AFN)') }}</th>
                        <th>{{ t('Documents') }}</th>
                        <th>{{ t('Amount (USD)') }}</th>
                        <th>{{ t('Notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template
                        v-for="section in ledger.sections"
                        :key="section.key"
                    >
                        <tr>
                            <td
                                colspan="7"
                                class="print-section"
                            >
                                {{ section.label }}
                            </td>
                        </tr>
                        <tr
                            v-for="line in section.lines"
                            :key="`${section.key}-${line.row_number}`"
                        >
                            <td>{{ line.row_number }}</td>
                            <td>{{ line.description }}</td>
                            <td>{{ formatDate(line.transaction_date) }}</td>
                            <td class="print-amount">
                                {{
                                    line.amount_afn
                                        ? formatAfn(line.amount_afn)
                                        : '—'
                                }}
                            </td>
                            <td>{{ line.document_ref || '—' }}</td>
                            <td class="print-amount">
                                {{
                                    line.amount_usd
                                        ? formatCurrency(line.amount_usd, 'USD')
                                        : '—'
                                }}
                            </td>
                            <td>{{ line.notes || '—' }}</td>
                        </tr>
                        <tr class="print-subtotal">
                            <td colspan="3">
                                {{ t('Subtotal') }} — {{ section.label }}
                            </td>
                            <td class="print-amount">
                                {{ formatAfn(section.subtotal_afn) }}
                            </td>
                            <td />
                            <td class="print-amount">
                                {{
                                    section.subtotal_usd
                                        ? formatCurrency(section.subtotal_usd, 'USD')
                                        : '—'
                                }}
                            </td>
                            <td />
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">{{ t('Total revenue') }}</td>
                        <td class="print-amount">
                            {{ formatAfn(ledger.totals.income_afn) }}
                        </td>
                        <td colspan="3" />
                    </tr>
                    <tr>
                        <td colspan="3">{{ t('Total expenses') }}</td>
                        <td class="print-amount">
                            {{ formatAfn(ledger.totals.expense_afn) }}
                        </td>
                        <td colspan="3" />
                    </tr>
                    <tr class="print-net">
                        <td colspan="3">{{ t('Net balance') }}</td>
                        <td class="print-amount">
                            {{ formatAfn(ledger.totals.net_afn) }}
                        </td>
                        <td colspan="3" />
                    </tr>
                </tfoot>
            </table>

            <footer
                class="mt-8 flex justify-between text-sm text-muted-foreground print:text-black"
            >
                <p>{{ t('Prepared By') }}: ______________________</p>
                <p>{{ t('Approved By') }}: ______________________</p>
            </footer>
        </article>
    </div>
</template>

<style scoped>
.ledger-print-table :is(th, td) {
    border: 1px solid var(--border);
    padding: 0.35rem 0.45rem;
    color: var(--foreground);
}

.ledger-print-table th {
    background: var(--muted);
    font-weight: 600;
    text-align: center;
}

.ledger-print-table td {
    background: var(--card);
}

.print-section {
    background: color-mix(in oklab, var(--accent) 45%, var(--card));
    font-weight: 700;
    text-align: center;
}

.print-subtotal td,
.print-net td {
    font-weight: 700;
}

.print-subtotal td {
    background: color-mix(in oklab, var(--muted) 55%, var(--card));
}

.print-net td {
    background: color-mix(in oklab, var(--destructive) 14%, var(--card));
}

.print-amount {
    text-align: start;
    direction: ltr;
    white-space: nowrap;
}

@media print {
    .no-print {
        display: none !important;
    }

    .ledger-print-table :is(th, td) {
        border-color: #111;
        color: #111;
        background: #fff;
    }

    .ledger-print-table th {
        background: #e5e7eb;
    }

    .print-section {
        background: #eef2ff;
    }

    .print-subtotal td {
        background: #f3f4f6;
    }

    .print-net td {
        background: #fef2f2;
    }
}
</style>
