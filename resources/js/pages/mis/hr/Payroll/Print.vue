<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { useMisPage } from '@/composables/useMisPage';
import { formatCurrency } from '@/lib/format';

interface Personnel {
    first_name?: string;
    last_name?: string;
}

interface PayrollItem {
    id: number;
    personnel_type: string;
    personnel_id: number;
    personnel?: Personnel | null;
    project?: { code: string } | null;
    base_amount: number;
    bonus: number;
    deductions: number;
    advance: number;
    net_amount: number;
    currency?: string | null;
    notes?: string | null;
}

interface PayrollRun {
    id: number;
    status: string;
    processed_by?: { name: string } | null;
    items?: PayrollItem[];
}

interface Props {
    payrollRun: PayrollRun;
    periodLabel: string;
    autoprint?: boolean;
}

const props = defineProps<Props>();

const { t } = useMisPage();

const personnelLabel = (item: PayrollItem): string => {
    if (item.personnel?.first_name || item.personnel?.last_name) {
        return [item.personnel.first_name, item.personnel.last_name]
            .filter(Boolean)
            .join(' ');
    }

    return `#${item.personnel_id}`;
};

const personnelTypeLabel = (type: string): string => {
    const parts = type.split('\\');

    return parts[parts.length - 1] ?? type;
};

const items = computed(() => props.payrollRun.items ?? []);

const totalNet = computed(() =>
    items.value.reduce((sum, item) => sum + Number(item.net_amount), 0),
);

onMounted(() => {
    if (props.autoprint) {
        window.print();
    }
});
</script>

<template>
    <Head :title="`${periodLabel} ${t('Payroll')}`" />

    <div class="mx-auto max-w-5xl p-8 text-sm text-black print:p-0">
        <header class="mb-8 border-b border-black/20 pb-4">
            <h1 class="text-2xl font-bold">{{ periodLabel }} {{ t('Payroll') }}</h1>
            <p class="mt-1 text-muted-foreground">
                {{ t('Status') }}: {{ payrollRun.status }}
                <span v-if="payrollRun.processed_by?.name">
                    · {{ t('Processed by') }} {{ payrollRun.processed_by.name }}
                </span>
            </p>
        </header>

        <table class="w-full border-collapse">
            <thead>
                <tr class="border-b border-black/30 text-left">
                    <th class="pb-2 pe-3 font-semibold">{{ t('Personnel') }}</th>
                    <th class="pb-2 pe-3 font-semibold">{{ t('Type') }}</th>
                    <th class="pb-2 pe-3 font-semibold">{{ t('Project') }}</th>
                    <th class="pb-2 pe-3 text-end font-semibold">{{ t('Base') }}</th>
                    <th class="pb-2 pe-3 text-end font-semibold">{{ t('Bonus') }}</th>
                    <th class="pb-2 pe-3 text-end font-semibold">{{ t('Deductions') }}</th>
                    <th class="pb-2 pe-3 text-end font-semibold">{{ t('Advance') }}</th>
                    <th class="pb-2 text-end font-semibold">{{ t('Net') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="item in items"
                    :key="item.id"
                    class="border-b border-black/10"
                >
                    <td class="py-2 pe-3">
                        <div>{{ personnelLabel(item) }}</div>
                        <div v-if="item.notes" class="text-xs text-black/60">
                            {{ item.notes }}
                        </div>
                    </td>
                    <td class="py-2 pe-3">{{ personnelTypeLabel(item.personnel_type) }}</td>
                    <td class="py-2 pe-3">{{ item.project?.code ?? '—' }}</td>
                    <td class="py-2 pe-3 text-end">
                        {{ formatCurrency(item.base_amount, item.currency ?? 'USD') }}
                    </td>
                    <td class="py-2 pe-3 text-end">
                        {{ formatCurrency(item.bonus ?? 0, item.currency ?? 'USD') }}
                    </td>
                    <td class="py-2 pe-3 text-end">
                        {{ formatCurrency(item.deductions, item.currency ?? 'USD') }}
                    </td>
                    <td class="py-2 pe-3 text-end">
                        {{ formatCurrency(item.advance ?? 0, item.currency ?? 'USD') }}
                    </td>
                    <td class="py-2 text-end font-medium">
                        {{ formatCurrency(item.net_amount, item.currency ?? 'USD') }}
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="border-t border-black/30 font-semibold">
                    <td colspan="7" class="pt-3 text-end">{{ t('Total net pay') }}</td>
                    <td class="pt-3 text-end">{{ formatCurrency(totalNet) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="mt-8 print:hidden">
            <button
                type="button"
                class="rounded border px-4 py-2"
                @click="window.print()"
            >
                {{ t('Print') }}
            </button>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        margin: 1.5cm;
    }
}
</style>
