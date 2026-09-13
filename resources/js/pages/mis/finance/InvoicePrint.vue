<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import { Button } from '@/components/ui/button';
import MisExportActions from '@/components/mis/MisExportActions.vue';
import { useMisPage } from '@/composables/useMisPage';
import { formatNumber } from '@/lib/format';

interface LineItem {
    description: string;
    quantity: number;
    unit_price: number;
    days: number;
    total: number;
}

interface CompanyBank {
    name: string;
    bank: string;
    account: string;
    swift: string;
}

const props = defineProps<{
    invoice: {
        id: number;
        invoice_number: string;
        issue_date: string | null;
        due_date: string | null;
        period_start: string | null;
        period_end: string | null;
        services: string | null;
        notes: string | null;
        subtotal: number;
        tax: number;
        total: number;
        currency: string;
        organization: {
            name: string;
            address?: string | null;
            email?: string | null;
            phone?: string | null;
        } | null;
        project: { id: number; code: string; name: string } | null;
        line_items: LineItem[];
    };
    company: {
        name: string;
        name_fa: string;
        address: string;
        phone: string;
        president: string;
        president_title: string;
        bank: { usd: CompanyBank; afn: CompanyBank };
    };
    amount_in_words: string;
}>();

const { t } = useMisPage();

const money = (value: number): string => {
    const amount = formatNumber(value, { maximumFractionDigits: 0 });

    if (props.invoice.currency === 'AFN') {
        return `${amount} AFN`;
    }

    return `${amount} $`;
};

const paddedItems = computed(() => {
    const items = [...props.invoice.line_items];

    while (items.length < 10) {
        items.push({
            description: '',
            quantity: 0,
            unit_price: 0,
            days: 0,
            total: 0,
        });
    }

    return items;
});

const periodLabel = (): string => {
    if (props.invoice.period_start && props.invoice.period_end) {
        return `${props.invoice.period_start} - ${props.invoice.period_end}`;
    }

    return props.invoice.issue_date ?? '—';
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('autoprint') === '1') {
        window.setTimeout(() => window.print(), 400);
    }
});
</script>

<template>
    <Head :title="invoice.invoice_number" />

    <div class="sheet-page">
        <div class="sheet-toolbar no-print">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/finance/invoices">
                    <ArrowLeft class="size-4" />
                    {{ t('Back') }}
                </Link>
            </Button>
            <MisExportActions variant="outline" />
        </div>

        <article class="sheet-document" data-export-root>
            <header class="inv-header">
                <AppLogoImage class="inv-logo" />
                <div class="inv-header-copy">
                    <h1>{{ company.name.toUpperCase() }}</h1>
                    <p class="inv-fa">{{ company.name_fa }}</p>
                </div>
            </header>

            <div class="inv-to">
                <strong>To:</strong>
                {{ invoice.organization?.name ?? invoice.project?.name ?? '—' }}
            </div>

            <table class="inv-meta">
                <tbody>
                <tr>
                    <th>SERVICES</th>
                    <td>
                        {{
                            invoice.services ||
                            invoice.project?.name ||
                            '—'
                        }}
                    </td>
                </tr>
                <tr>
                    <th>INVOICE DATE</th>
                    <td>{{ invoice.issue_date ?? '—' }}</td>
                </tr>
                <tr>
                    <th>INVOICE NUMBER</th>
                    <td>{{ invoice.invoice_number }}</td>
                </tr>
                <tr>
                    <th>INVOICE PERIOD</th>
                    <td>{{ periodLabel() }}</td>
                </tr>
                </tbody>
            </table>

            <h2 class="inv-section">CONTRACTOR BANK DETAILS</h2>
            <div class="inv-banks">
                <div>
                    <p>
                        <strong>ACC/ N:</strong>
                        {{ company.bank.usd.name.toUpperCase() }}
                    </p>
                    <p><strong>USD ACCOUNT</strong></p>
                    <p><strong>BANK NAME :</strong> {{ company.bank.usd.bank }}</p>
                    <p>
                        <strong>ACCOUNT NUMBER :</strong>
                        {{ company.bank.usd.account }}
                    </p>
                    <p>
                        <strong>SWIFT CODE :</strong>
                        {{ company.bank.usd.swift }}
                    </p>
                </div>
                <div>
                    <p>
                        <strong>ACC/N:</strong>
                        {{ company.bank.afn.name.toUpperCase() }}
                    </p>
                    <p><strong>AFG ACCOUNT</strong></p>
                    <p><strong>BANK NAME :</strong> {{ company.bank.afn.bank }}</p>
                    <p>
                        <strong>ACCOUNT NUMBER :</strong>
                        {{ company.bank.afn.account }}
                    </p>
                    <p>
                        <strong>SWIFT CODE :</strong>
                        {{ company.bank.afn.swift }}
                    </p>
                </div>
            </div>

            <table class="inv-items">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>UNIT</th>
                        <th>QUANTITY</th>
                        <th>UNIT COST</th>
                        <th>DAY</th>
                        <th>TOTAL COST</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(item, index) in paddedItems"
                        :key="index"
                    >
                        <td>{{ index + 1 }}</td>
                        <td class="col-name">{{ item.description }}</td>
                        <td>{{ item.description ? formatNumber(item.quantity) : '' }}</td>
                        <td>{{ item.description ? money(item.unit_price) : '' }}</td>
                        <td>{{ item.description ? item.days : '' }}</td>
                        <td>{{ item.description ? money(item.total) : '' }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5"><strong>TOTAL COST</strong></td>
                        <td>
                            <strong>{{ money(invoice.total) }}</strong>
                        </td>
                    </tr>
                </tfoot>
            </table>

            <p class="inv-words">{{ amount_in_words }}</p>

            <p class="inv-office">
                <strong>HEAD OFFICE :</strong>
                {{ company.address }}
                <strong>Mob :</strong>
                {{ company.phone }}
            </p>

            <div class="inv-notes">
                <p><strong>NOTE:</strong></p>
                <p><strong>PAYMENT TERMS:</strong></p>
                <p>
                    PLEASE TRANSFER TOTAL AMOUNT OF
                    <strong>{{ money(invoice.total) }}</strong>
                    TO THE GIVEN BANK ACCOUNTS AS ABOVE.
                </p>
                <p v-if="invoice.notes">{{ invoice.notes }}</p>
            </div>

            <div class="inv-sign">
                <p>{{ company.president }}</p>
                <p>{{ company.name }}</p>
                <p>{{ company.president_title }}</p>
            </div>
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
    max-width: 900px;
    margin: 0 auto 0.75rem;
}

.sheet-document {
    max-width: 900px;
    margin: 0 auto;
    background: #fff;
    color: #111;
    border: 2px solid #111;
    padding: 1rem 1.15rem 1.25rem;
    font-size: 0.8rem;
}

.inv-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.inv-logo {
    width: 4.5rem;
    height: 4.5rem;
}

.inv-header-copy {
    flex: 1;
    text-align: center;
}

.inv-header-copy h1 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: 0.02em;
}

.inv-fa {
    margin: 0.2rem 0 0;
    font-size: 0.95rem;
}

.inv-to {
    border: 1px solid #111;
    padding: 0.35rem 0.5rem;
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.inv-meta {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0.6rem;
}

.inv-meta th,
.inv-meta td {
    border: 1px solid #111;
    padding: 0.28rem 0.45rem;
    text-align: start;
}

.inv-meta th {
    width: 10rem;
    font-size: 0.72rem;
    background: #f3f4f6;
}

.inv-section {
    margin: 0 0 0.35rem;
    font-size: 0.78rem;
    font-weight: 800;
}

.inv-banks {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    border: 1px solid #111;
    padding: 0.5rem;
    margin-bottom: 0.6rem;
}

.inv-banks p {
    margin: 0;
    line-height: 1.45;
}

.inv-items {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0.6rem;
}

.inv-items th,
.inv-items td {
    border: 1px solid #111;
    padding: 0.3rem 0.4rem;
    text-align: center;
}

.inv-items thead th {
    background: #d9d9d9;
    font-size: 0.7rem;
}

.col-name {
    text-align: start;
}

.inv-words {
    border: 1px solid #111;
    padding: 0.4rem 0.5rem;
    font-weight: 700;
    text-transform: uppercase;
    margin: 0 0 0.5rem;
}

.inv-office,
.inv-notes p {
    margin: 0 0 0.35rem;
}

.inv-sign {
    margin-top: 1.5rem;
}

.inv-sign p {
    margin: 0;
    font-weight: 700;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    .sheet-page {
        background: #fff;
        padding: 0;
    }

    .no-print {
        display: none !important;
    }

    .sheet-document {
        max-width: none;
        border-width: 1.5px;
    }
}
</style>
