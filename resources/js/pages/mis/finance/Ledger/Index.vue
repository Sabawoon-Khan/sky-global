<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { V2Hero, V2ListPage } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn, formatCurrency, formatDate } from '@/lib/format';
import { BookOpen, Plus, Printer } from '@lucide/vue';

interface LedgerLine {
    row_number: number;
    source: string;
    source_id: number;
    section: string;
    description: string;
    category?: string | null;
    transaction_date?: string | null;
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
    period_start: string;
    period_end: string;
    period_label: string;
    sections: LedgerSection[];
    totals: {
        income_afn: number;
        income_usd: number;
        tax_afn: number;
        expense_afn: number;
        expense_usd: number;
        net_afn: number;
    };
}

interface LedgerFilters {
    period_start: string;
    period_end: string;
}

interface SystemDateContext {
    today: string;
    period_start: string;
    period_end: string;
}

const props = defineProps<{
    ledger: LedgerReport;
    filters: LedgerFilters;
    system: SystemDateContext;
}>();

const { t } = useMisPage();
const showLineForm = ref(false);
const newSection = ref<'income' | 'expense'>('expense');

const periodStart = ref(props.filters.period_start);
const periodEnd = ref(props.filters.period_end);

const applyPeriod = (): void => {
    router.get(
        '/finance/ledger',
        {
            period_start: periodStart.value,
            period_end: periodEnd.value,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const useSystemMonth = (): void => {
    periodStart.value = props.system.period_start;
    periodEnd.value = props.system.period_end;
    applyPeriod();
};

const printHref = computed(
    () =>
        `/finance/ledger/print?period_start=${encodeURIComponent(props.filters.period_start)}&period_end=${encodeURIComponent(props.filters.period_end)}`,
);

const netClass = computed(() =>
    props.ledger.totals.net_afn < 0
        ? 'text-destructive'
        : 'text-emerald-700 dark:text-emerald-400',
);

const openAddLine = (section: 'income' | 'expense'): void => {
    newSection.value = section;
    showLineForm.value = true;
};
</script>

<template>
    <Head :title="t('Monthly ledger')" />

    <V2ListPage>
        <V2Hero
            :title="t('Monthly ledger')"
            :description="
                t('Monthly list of accounts, revenue, expenses, and tax')
            "
            :icon="BookOpen"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="printHref" target="_blank">
                        <Printer class="size-4" />
                        {{ t('Print') }}
                    </Link>
                </Button>
                <Can permission="finance.create">
                    <Button @click="openAddLine('income')">
                        <Plus class="size-4" />
                        {{ t('Add income line') }}
                    </Button>
                    <Button variant="secondary" @click="openAddLine('expense')">
                        <Plus class="size-4" />
                        {{ t('Add expense line') }}
                    </Button>
                </Can>
            </template>
        </V2Hero>

        <div
            class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-border bg-card p-4 text-card-foreground"
        >
            <div class="grid gap-1.5">
                <Label for="period_start">{{ t('From') }}</Label>
                <Input
                    id="period_start"
                    v-model="periodStart"
                    type="date"
                    class="w-44"
                />
            </div>
            <div class="grid gap-1.5">
                <Label for="period_end">{{ t('To') }}</Label>
                <Input
                    id="period_end"
                    v-model="periodEnd"
                    type="date"
                    class="w-44"
                />
            </div>
            <Button type="button" @click="applyPeriod">
                {{ t('Apply') }}
            </Button>
            <Button
                type="button"
                variant="outline"
                @click="useSystemMonth"
            >
                {{ t('Current month') }}
            </Button>
        </div>

        <article
            class="rounded-xl border border-border bg-card p-5 text-card-foreground shadow-sm"
            dir="rtl"
        >
            <header
                class="mb-4 flex items-center gap-4 border-b-2 border-primary pb-3"
            >
                <AppLogoImage class="h-12 w-auto" />
                <div class="flex-1 text-center">
                    <h2 class="text-lg font-bold text-foreground">
                        {{ t('Monthly list of accounts and expenses') }}
                    </h2>
                </div>
            </header>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr>
                            <th
                                class="cell-head"
                                scope="col"
                            >
                                {{ t('No.') }}
                            </th>
                            <th
                                class="cell-head"
                                scope="col"
                            >
                                {{ t('Details') }}
                            </th>
                            <th
                                class="cell-head"
                                scope="col"
                            >
                                {{ t('Date') }}
                            </th>
                            <th
                                class="cell-head"
                                scope="col"
                            >
                                {{ t('Amount (AFN)') }}
                            </th>
                            <th
                                class="cell-head"
                                scope="col"
                            >
                                {{ t('Documents') }}
                            </th>
                            <th
                                class="cell-head"
                                scope="col"
                            >
                                {{ t('Amount (USD)') }}
                            </th>
                            <th
                                class="cell-head"
                                scope="col"
                            >
                                {{ t('Notes') }}
                            </th>
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
                                    class="cell-section"
                                >
                                    {{ section.label }}
                                </td>
                            </tr>
                            <tr
                                v-for="line in section.lines"
                                :key="`${section.key}-${line.source}-${line.source_id}`"
                            >
                                <td class="cell-body">{{ line.row_number }}</td>
                                <td class="cell-body min-w-48">
                                    {{ line.description }}
                                    <span
                                        v-if="line.category"
                                        class="text-xs text-muted-foreground"
                                    >
                                        · {{ line.category }}
                                    </span>
                                </td>
                                <td class="cell-body">
                                    {{ formatDate(line.transaction_date) }}
                                </td>
                                <td class="cell-amount">
                                    {{
                                        line.amount_afn
                                            ? formatAfn(line.amount_afn)
                                            : '—'
                                    }}
                                </td>
                                <td class="cell-body">
                                    {{ line.document_ref || '—' }}
                                </td>
                                <td class="cell-amount">
                                    {{
                                        line.amount_usd
                                            ? formatCurrency(line.amount_usd, 'USD')
                                            : '—'
                                    }}
                                </td>
                                <td class="cell-body">
                                    {{ line.notes || '—' }}
                                </td>
                            </tr>
                            <tr>
                                <td
                                    colspan="3"
                                    class="cell-subtotal"
                                >
                                    {{ t('Subtotal') }} — {{ section.label }}
                                </td>
                                <td class="cell-subtotal cell-amount">
                                    {{ formatAfn(section.subtotal_afn) }}
                                </td>
                                <td class="cell-subtotal" />
                                <td class="cell-subtotal cell-amount">
                                    {{
                                        section.subtotal_usd
                                            ? formatCurrency(section.subtotal_usd, 'USD')
                                            : '—'
                                    }}
                                </td>
                                <td class="cell-subtotal" />
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td
                                colspan="3"
                                class="cell-body font-semibold"
                            >
                                {{ t('Total revenue') }}
                            </td>
                            <td class="cell-body cell-amount font-semibold">
                                {{ formatAfn(ledger.totals.income_afn) }}
                            </td>
                            <td class="cell-body" />
                            <td class="cell-body cell-amount font-semibold">
                                {{
                                    ledger.totals.income_usd
                                        ? formatCurrency(ledger.totals.income_usd, 'USD')
                                        : '—'
                                }}
                            </td>
                            <td class="cell-body" />
                        </tr>
                        <tr>
                            <td
                                colspan="3"
                                class="cell-body font-semibold"
                            >
                                {{ t('Total tax') }}
                            </td>
                            <td class="cell-body cell-amount font-semibold">
                                {{ formatAfn(ledger.totals.tax_afn) }}
                            </td>
                            <td
                                colspan="3"
                                class="cell-body"
                            />
                        </tr>
                        <tr>
                            <td
                                colspan="3"
                                class="cell-body font-semibold"
                            >
                                {{ t('Total expenses') }}
                            </td>
                            <td class="cell-body cell-amount font-semibold">
                                {{ formatAfn(ledger.totals.expense_afn) }}
                            </td>
                            <td
                                colspan="3"
                                class="cell-body"
                            />
                        </tr>
                        <tr>
                            <td
                                colspan="3"
                                class="cell-net"
                            >
                                {{ t('Net balance') }}
                            </td>
                            <td
                                class="cell-net cell-amount"
                                :class="netClass"
                            >
                                {{ formatAfn(ledger.totals.net_afn) }}
                            </td>
                            <td
                                colspan="3"
                                class="cell-net"
                            />
                        </tr>
                    </tfoot>
                </table>
            </div>
        </article>

        <Can permission="finance.create">
            <Dialog v-model:open="showLineForm">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {{
                                newSection === 'income'
                                    ? t('Add income line')
                                    : t('Add expense line')
                            }}
                        </DialogTitle>
                    </DialogHeader>
                    <Form
                        method="post"
                        action="/finance/ledger/lines"
                        class="grid gap-4"
                        @success="showLineForm = false"
                    >
                        <input type="hidden" name="section" :value="newSection" />
                        <input
                            type="hidden"
                            name="period_start"
                            :value="filters.period_start"
                        />
                        <input
                            type="hidden"
                            name="period_end"
                            :value="filters.period_end"
                        />
                        <div class="grid gap-1.5">
                            <Label for="transaction_date">{{ t('Date') }}</Label>
                            <Input
                                id="transaction_date"
                                name="transaction_date"
                                type="date"
                                required
                                :default-value="system.today"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="description">{{ t('Details') }}</Label>
                            <Input id="description" name="description" required />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="amount">{{ t('Amount (AFN)') }}</Label>
                            <Input
                                id="amount"
                                name="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="amount_usd">{{ t('Amount (USD)') }}</Label>
                            <Input
                                id="amount_usd"
                                name="amount_usd"
                                type="number"
                                step="0.01"
                                min="0"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="reference_number">{{
                                t('Documents')
                            }}</Label>
                            <Input
                                id="reference_number"
                                name="reference_number"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="category">{{ t('Category') }}</Label>
                            <Input id="category" name="category" />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                @click="showLineForm = false"
                            >
                                {{ t('Cancel') }}
                            </Button>
                            <Button type="submit">{{ t('Save') }}</Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </Can>
    </V2ListPage>
</template>

<style scoped>
.cell-head,
.cell-body,
.cell-section,
.cell-subtotal,
.cell-net {
    border: 1px solid var(--border);
    padding: 0.45rem 0.5rem;
    vertical-align: top;
    color: var(--foreground);
}

.cell-head {
    background: var(--muted);
    padding-block: 0.5rem;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 600;
}

.cell-body {
    background: var(--card);
}

.cell-section {
    background: color-mix(in oklab, var(--accent) 45%, var(--card));
    text-align: center;
    font-size: 0.875rem;
    font-weight: 700;
}

.cell-subtotal {
    background: color-mix(in oklab, var(--muted) 55%, var(--card));
    font-weight: 600;
}

.cell-net {
    background: color-mix(in oklab, var(--destructive) 14%, var(--card));
    font-weight: 700;
}

.cell-amount {
    text-align: start;
    direction: ltr;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
</style>
