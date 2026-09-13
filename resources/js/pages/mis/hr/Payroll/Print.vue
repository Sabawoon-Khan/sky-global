<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { Printer } from '@lucide/vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import PayrollPrintPersonnelPicker from '@/components/PayrollPrintPersonnelPicker.vue';
import { Button } from '@/components/ui/button';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn } from '@/lib/format';

interface PayrollRow {
    id: number;
    no: number;
    name: string;
    designation: string;
    days_present: number;
    days_absent: number;
    days_sick_leave: number;
    days_annual_leave: number;
    days_casual_leave: number;
    days_other: number;
    base_amount: number;
    bonus: number;
    deductions: number;
    tax: number;
    advance: number;
    net_amount: number;
    currency: string;
}

interface Totals {
    base: number;
    bonus: number;
    deductions: number;
    tax: number;
    advance: number;
    net: number;
}

interface Props {
    payrollRun: {
        id: number;
        title: string;
        payroll_type: string;
        date_from: string;
        date_to: string;
        status: string;
        processed_by?: string | null;
    };
    project?: {
        id: number;
        code: string;
        name: string;
        location?: string | null;
    } | null;
    employees: PayrollRow[];
    contractors: PayrollRow[];
    employee_totals: Totals;
    contractor_totals: Totals;
    totals: Totals;
    period_label: string;
}

const props = defineProps<Props>();

const { t } = useMisPage();

const allIds = (): number[] => [
    ...props.employees.map((row) => row.id),
    ...props.contractors.map((row) => row.id),
];

const requestedIds = (): number[] => {
    if (typeof window === 'undefined') {
        return [];
    }

    const items = new URLSearchParams(window.location.search).get('items');

    if (!items) {
        return [];
    }

    const allowed = new Set(allIds());

    return items
        .split(',')
        .map((value) => Number(value))
        .filter((id) => Number.isInteger(id) && allowed.has(id));
};

const initialSelectedIds = (): number[] => {
    const requested = requestedIds();

    return requested.length > 0 ? requested : allIds();
};

const selectedIds = ref<number[]>(initialSelectedIds());

const employeeOptions = computed(() =>
    props.employees.map((row) => ({ id: row.id, name: row.name })),
);
const contractorOptions = computed(() =>
    props.contractors.map((row) => ({ id: row.id, name: row.name })),
);

const selectedSet = computed(() => new Set(selectedIds.value));

const numberRows = (rows: PayrollRow[]): PayrollRow[] =>
    rows.map((row, index) => ({ ...row, no: index + 1 }));

const selectedEmployees = computed(() =>
    numberRows(props.employees.filter((row) => selectedSet.value.has(row.id))),
);
const selectedContractors = computed(() =>
    numberRows(
        props.contractors.filter((row) => selectedSet.value.has(row.id)),
    ),
);

const sumTotals = (rows: PayrollRow[]): Totals => ({
    base: rows.reduce((sum, row) => sum + row.base_amount, 0),
    bonus: rows.reduce((sum, row) => sum + row.bonus, 0),
    deductions: rows.reduce((sum, row) => sum + row.deductions, 0),
    tax: rows.reduce((sum, row) => sum + row.tax, 0),
    advance: rows.reduce((sum, row) => sum + row.advance, 0),
    net: rows.reduce((sum, row) => sum + row.net_amount, 0),
});

const selectedEmployeeTotals = computed(() =>
    sumTotals(selectedEmployees.value),
);
const selectedContractorTotals = computed(() =>
    sumTotals(selectedContractors.value),
);
const selectedTotals = computed(() =>
    sumTotals([...selectedEmployees.value, ...selectedContractors.value]),
);

const hasItems = computed(
    () =>
        selectedEmployees.value.length > 0 ||
        selectedContractors.value.length > 0,
);

const amount = (value: number): string => formatAfn(value);

const printPage = (): void => {
    if (!hasItems.value) {
        return;
    }

    window.print();
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('autoprint') === '1' && hasItems.value) {
        window.setTimeout(() => window.print(), 500);
    }
});
</script>

<template>
    <Head :title="payrollRun.title" />

    <div class="sheet-page">
        <div class="sheet-toolbar no-print">
            <div class="toolbar-row">
                <a :href="`/hr/payroll/${payrollRun.id}`" class="back-link">
                    ← {{ t('Back to payroll') }}
                </a>
                <div class="flex items-center gap-2">
                    <span class="period-hint">{{ period_label }}</span>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="!hasItems"
                        @click="printPage"
                    >
                        <Printer class="size-4" />
                        {{ t('Print') }}
                    </Button>
                </div>
            </div>

            <div
                v-if="employees.length > 0 || contractors.length > 0"
                class="picker-panel"
            >
                <p class="picker-title">
                    {{ t('Select employees or contractors to print') }}
                </p>
                <PayrollPrintPersonnelPicker
                    v-model="selectedIds"
                    :employees="employeeOptions"
                    :contractors="contractorOptions"
                />
            </div>
        </div>

        <article class="sheet-document">
            <header class="sheet-header">
                <AppLogoImage class="sheet-logo sheet-logo--left" />
                <div class="sheet-header-center">
                    <h1 class="sheet-title">{{ payrollRun.title }}</h1>
                    <p class="sheet-subtitle">{{ t('Payroll Sheet') }}</p>
                </div>
                <AppLogoImage class="sheet-logo sheet-logo--right" />
            </header>

            <div class="sheet-meta">
                <p><strong>{{ t('Period') }}:</strong> {{ period_label }}</p>
                <p>
                    <strong>{{ t('Project') }}:</strong>
                    {{ project ? `${project.name} (${project.code})` : t('General / Permanent staff') }}
                </p>
                <p><strong>{{ t('Location') }}:</strong> {{ project?.location ?? '—' }}</p>
                <p><strong>{{ t('Prepared by') }}:</strong> {{ payrollRun.processed_by ?? '—' }}</p>
            </div>

            <p v-if="!hasItems" class="sheet-empty">
                {{
                    employees.length > 0 || contractors.length > 0
                        ? t(
                              'Select at least one employee or contractor to print.',
                          )
                        : t('No payroll line items.')
                }}
            </p>

            <template v-else>
                <section
                    v-if="selectedEmployees.length > 0"
                    class="sheet-section"
                >
                    <h2 class="section-title">{{ t('Employees') }}</h2>
                    <div class="sheet-table-wrap">
                        <table class="sheet-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ t('Name') }}</th>
                                    <th>{{ t('Present') }}</th>
                                    <th>{{ t('Absent') }}</th>
                                    <th>{{ t('Sick') }}</th>
                                    <th>{{ t('Annual') }}</th>
                                    <th>{{ t('Casual') }}</th>
                                    <th>{{ t('Other') }}</th>
                                    <th>{{ t('Base') }}</th>
                                    <th>{{ t('Bonus') }}</th>
                                    <th>{{ t('Deductions') }}</th>
                                    <th>{{ t('Tax') }}</th>
                                    <th>{{ t('Advance') }}</th>
                                    <th class="col-net">{{ t('Net Pay') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in selectedEmployees"
                                    :key="`emp-${row.id}`"
                                >
                                    <td>{{ row.no }}</td>
                                    <td class="col-name">{{ row.name }}</td>
                                    <td>{{ row.days_present }}</td>
                                    <td>{{ row.days_absent }}</td>
                                    <td>{{ row.days_sick_leave }}</td>
                                    <td>{{ row.days_annual_leave }}</td>
                                    <td>{{ row.days_casual_leave }}</td>
                                    <td>{{ row.days_other }}</td>
                                    <td class="col-amount">{{ amount(row.base_amount) }}</td>
                                    <td class="col-amount">{{ amount(row.bonus) }}</td>
                                    <td class="col-amount">{{ amount(row.deductions) }}</td>
                                    <td class="col-amount">{{ amount(row.tax) }}</td>
                                    <td class="col-amount">{{ amount(row.advance) }}</td>
                                    <td class="col-amount col-net">{{ amount(row.net_amount) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="totals-row">
                                    <td colspan="8" class="text-end font-bold">{{ t('Employee totals') }}</td>
                                    <td class="col-amount font-bold">{{ amount(selectedEmployeeTotals.base) }}</td>
                                    <td class="col-amount font-bold">{{ amount(selectedEmployeeTotals.bonus) }}</td>
                                    <td class="col-amount font-bold">{{ amount(selectedEmployeeTotals.deductions) }}</td>
                                    <td class="col-amount font-bold">{{ amount(selectedEmployeeTotals.tax) }}</td>
                                    <td class="col-amount font-bold">{{ amount(selectedEmployeeTotals.advance) }}</td>
                                    <td class="col-amount col-net font-bold">{{ amount(selectedEmployeeTotals.net) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <section
                    v-if="selectedContractors.length > 0"
                    class="sheet-section"
                >
                    <h2 class="section-title">{{ t('Contractors') }}</h2>
                    <div class="sheet-table-wrap">
                        <table class="sheet-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ t('Name') }}</th>
                                    <th>{{ t('Present') }}</th>
                                    <th>{{ t('Absent') }}</th>
                                    <th>{{ t('Sick') }}</th>
                                    <th>{{ t('Annual') }}</th>
                                    <th>{{ t('Casual') }}</th>
                                    <th>{{ t('Other') }}</th>
                                    <th>{{ t('Base') }}</th>
                                    <th>{{ t('Bonus') }}</th>
                                    <th>{{ t('Deductions') }}</th>
                                    <th>{{ t('Tax') }}</th>
                                    <th>{{ t('Advance') }}</th>
                                    <th class="col-net">{{ t('Net Pay') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in selectedContractors"
                                    :key="`con-${row.id}`"
                                >
                                    <td>{{ row.no }}</td>
                                    <td class="col-name">{{ row.name }}</td>
                                    <td>{{ row.days_present }}</td>
                                    <td>{{ row.days_absent }}</td>
                                    <td>{{ row.days_sick_leave }}</td>
                                    <td>{{ row.days_annual_leave }}</td>
                                    <td>{{ row.days_casual_leave }}</td>
                                    <td>{{ row.days_other }}</td>
                                    <td class="col-amount">{{ amount(row.base_amount) }}</td>
                                    <td class="col-amount">{{ amount(row.bonus) }}</td>
                                    <td class="col-amount">{{ amount(row.deductions) }}</td>
                                    <td class="col-amount">{{ amount(row.tax) }}</td>
                                    <td class="col-amount">{{ amount(row.advance) }}</td>
                                    <td class="col-amount col-net">{{ amount(row.net_amount) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="totals-row">
                                    <td colspan="8" class="text-end font-bold">{{ t('Contractor totals') }}</td>
                                    <td class="col-amount font-bold">{{ amount(selectedContractorTotals.base) }}</td>
                                    <td class="col-amount font-bold">{{ amount(selectedContractorTotals.bonus) }}</td>
                                    <td class="col-amount font-bold">{{ amount(selectedContractorTotals.deductions) }}</td>
                                    <td class="col-amount font-bold">{{ amount(selectedContractorTotals.tax) }}</td>
                                    <td class="col-amount font-bold">{{ amount(selectedContractorTotals.advance) }}</td>
                                    <td class="col-amount col-net font-bold">{{ amount(selectedContractorTotals.net) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <section class="sheet-section">
                    <table class="sheet-table grand-total-table">
                        <tbody>
                            <tr class="totals-row">
                                <td class="font-bold">{{ t('Grand total') }}</td>
                                <td class="col-amount font-bold">{{ amount(selectedTotals.base) }}</td>
                                <td class="col-amount font-bold">{{ amount(selectedTotals.bonus) }}</td>
                                <td class="col-amount font-bold">{{ amount(selectedTotals.deductions) }}</td>
                                <td class="col-amount font-bold">{{ amount(selectedTotals.tax) }}</td>
                                <td class="col-amount font-bold">{{ amount(selectedTotals.advance) }}</td>
                                <td class="col-amount col-net font-bold">{{ amount(selectedTotals.net) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </template>

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
    flex-direction: column;
    gap: 0.75rem;
    max-width: 100%;
    margin: 0 auto 0.75rem;
}

.toolbar-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: center;
}

.picker-panel {
    background: #fff;
    border-radius: 0.75rem;
    padding: 0.85rem 1rem;
}

.picker-title {
    margin: 0 0 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #111;
}

.back-link {
    font-size: 0.875rem;
    color: #374151;
    text-decoration: none;
}

.back-link:hover {
    text-decoration: underline;
}

.period-hint {
    font-size: 0.875rem;
    color: #6b7280;
}

.sheet-document {
    max-width: 100%;
    margin: 0 auto;
    background: #fff;
    padding: 1rem 1.25rem 1.5rem;
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

.sheet-section {
    margin-bottom: 1rem;
}

.section-title {
    margin: 0 0 0.35rem;
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #111;
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

.grand-total-table {
    max-width: 40rem;
    margin-left: auto;
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
        size: A4 landscape;
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
        padding: 0;
    }

    .sheet-table {
        font-size: 9pt;
    }

    .col-amount {
        font-size: 9pt;
    }
}
</style>
