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
    total: number;
}

const props = defineProps<{
    quotation: {
        id: number;
        quote_number: string;
        quote_date: string | null;
        valid_until: string | null;
        description_of_work: string | null;
        notes: string | null;
        subtotal: number;
        tax: number;
        total: number;
        currency: string;
        organization: {
            id: number;
            name: string;
            address?: string | null;
            email?: string | null;
            phone?: string | null;
        } | null;
        line_items: LineItem[];
    };
    company: {
        name: string;
        short_name: string;
        address: string;
        phone: string;
        phone_alt: string;
        email: string;
        president: string;
        president_title: string;
    };
}>();

const { t } = useMisPage();

const money = (value: number): string =>
    `$ ${formatNumber(value, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const paddedItems = computed(() => {
    const items = [...props.quotation.line_items];

    while (items.length < 5) {
        items.push({
            description: '',
            quantity: 0,
            unit_price: 0,
            total: 0,
        });
    }

    return items;
});

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('autoprint') === '1') {
        window.setTimeout(() => window.print(), 400);
    }
});
</script>

<template>
    <Head :title="quotation.quote_number" />

    <div class="sheet-page">
        <div class="sheet-toolbar no-print">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/finance/quotations">
                    <ArrowLeft class="size-4" />
                    {{ t('Back') }}
                </Link>
            </Button>
            <MisExportActions variant="outline" />
        </div>

        <article class="sheet-document" data-export-root>
            <header class="q-header">
                <div class="q-brand">
                    <AppLogoImage class="q-logo" />
                    <div>
                        <h1>{{ company.short_name }}</h1>
                        <p>{{ company.address }}</p>
                        <p>Email: {{ company.email }}</p>
                        <p>
                            Contact #: {{ company.phone }},
                            {{ company.phone_alt }}
                        </p>
                    </div>
                </div>
                <div class="q-title-box">
                    <h2>QUOTATION</h2>
                    <table>
                        <tbody>
                        <tr>
                            <th>QUOTE #</th>
                            <td>{{ quotation.quote_number }}</td>
                        </tr>
                        <tr>
                            <th>DATE</th>
                            <td>{{ quotation.quote_date ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>CUSTOMER ID</th>
                            <td>{{ quotation.organization?.id ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>VALID UNTIL</th>
                            <td>{{ quotation.valid_until ?? '—' }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </header>

            <section class="q-customer">
                <h3>CUSTOMER INFO</h3>
                <p>
                    <strong>Company Name:</strong>
                    {{ quotation.organization?.name ?? '—' }}
                </p>
                <p>
                    <strong>Address:</strong>
                    {{ quotation.organization?.address ?? '—' }}
                </p>
                <p>
                    <strong>Email:</strong>
                    {{ quotation.organization?.email ?? '—' }}
                </p>
                <p>
                    <strong>Contact #:</strong>
                    {{ quotation.organization?.phone ?? '—' }}
                </p>
            </section>

            <section class="q-work">
                <h3>DESCRIPTION OF WORK</h3>
                <p>
                    {{
                        quotation.description_of_work ||
                        t('Security services as listed below.')
                    }}
                </p>
            </section>

            <table class="q-items">
                <thead>
                    <tr>
                        <th>SN</th>
                        <th>Service Description</th>
                        <th>QUANTITY</th>
                        <th>UNIT PRICE</th>
                        <th>TOTAL PRICE</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(item, index) in paddedItems"
                        :key="index"
                    >
                        <td>{{ item.description ? index + 1 : '' }}</td>
                        <td class="col-name">{{ item.description }}</td>
                        <td>{{ item.description ? formatNumber(item.quantity) : '' }}</td>
                        <td>{{ item.description ? money(item.unit_price) : '' }}</td>
                        <td>{{ item.description ? money(item.total) : '' }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="q-totals-wrap">
                <p class="q-thanks">Thank you for your business!</p>
                <table class="q-totals">
                    <tbody>
                    <tr>
                        <th>SUBTOTAL</th>
                        <td>{{ money(quotation.subtotal) }}</td>
                    </tr>
                    <tr>
                        <th>Withholding Tax</th>
                        <td>{{ money(quotation.tax) }}</td>
                    </tr>
                    <tr>
                        <th>TOTAL QUOTE</th>
                        <td>{{ money(quotation.total) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <p class="q-note">
                <strong>Note:</strong>
                {{ quotation.notes || '—' }}
            </p>

            <div class="q-sign">
                <p>Yours sincerely,</p>
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
    padding: 1.1rem 1.25rem 1.4rem;
    font-size: 0.82rem;
}

.q-header {
    display: grid;
    grid-template-columns: 1.3fr 0.9fr;
    gap: 1rem;
    margin-bottom: 0.85rem;
}

.q-brand {
    display: flex;
    gap: 0.75rem;
}

.q-logo {
    width: 4.25rem;
    height: 4.25rem;
}

.q-brand h1 {
    margin: 0 0 0.25rem;
    font-size: 1.05rem;
}

.q-brand p {
    margin: 0;
    line-height: 1.4;
}

.q-title-box h2 {
    margin: 0 0 0.4rem;
    text-align: center;
    letter-spacing: 0.12em;
    font-size: 1.15rem;
}

.q-title-box table {
    width: 100%;
    border-collapse: collapse;
}

.q-title-box th,
.q-title-box td {
    border: 1px solid #111;
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
}

.q-title-box th {
    width: 7.5rem;
    background: #f3f4f6;
    text-align: start;
}

.q-customer,
.q-work {
    border: 1px solid #111;
    padding: 0.45rem 0.55rem;
    margin-bottom: 0.55rem;
}

.q-customer h3,
.q-work h3 {
    margin: 0 0 0.3rem;
    font-size: 0.75rem;
}

.q-customer p,
.q-work p {
    margin: 0;
    line-height: 1.45;
}

.q-items {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0.6rem;
}

.q-items th,
.q-items td {
    border: 1px solid #111;
    padding: 0.32rem 0.4rem;
    text-align: center;
}

.q-items thead th {
    background: #f3f4f6;
    font-size: 0.72rem;
}

.col-name {
    text-align: start;
}

.q-totals-wrap {
    display: grid;
    grid-template-columns: 1fr 16rem;
    gap: 1rem;
    align-items: end;
    margin-bottom: 0.75rem;
}

.q-thanks {
    font-style: italic;
}

.q-totals {
    width: 100%;
    border-collapse: collapse;
}

.q-totals th,
.q-totals td {
    border: 1px solid #111;
    padding: 0.28rem 0.4rem;
}

.q-totals th {
    text-align: start;
    background: #f3f4f6;
}

.q-totals td {
    text-align: end;
    font-weight: 700;
}

.q-note {
    margin: 0 0 1.25rem;
}

.q-sign p {
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
    }
}
</style>
