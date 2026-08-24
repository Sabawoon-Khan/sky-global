<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

export interface SalaryRecord {
    id?: number;
    amount?: number | null;
    currency?: string | null;
    effective_from?: string | null;
    effective_to?: string | null;
    notes?: string | null;
}

interface SalaryRow {
    key: number;
    id?: number;
    amount: string;
    currency: string;
    effectiveFrom: string;
    effectiveTo: string;
    notes: string;
}

const props = defineProps<{
    currencies?: string[];
    initialSalaries?: SalaryRecord[];
    errors?: Record<string, string>;
}>();

const { t } = useTranslations();

let nextKey = 0;
const rows = ref<SalaryRow[]>([]);

const formatDate = (value?: string | null): string => {
    if (!value) return '';
    return value.slice(0, 10);
};

const addRow = (): void => {
    rows.value.push({
        key: nextKey++,
        amount: '',
        currency: 'AFN',
        effectiveFrom: '',
        effectiveTo: '',
        notes: '',
    });
};

const removeRow = (index: number): void => {
    rows.value.splice(index, 1);
};

const fieldError = (index: number, field: string): string | undefined =>
    props.errors?.[`salaries.${index}.${field}`];

onMounted(() => {
    if (props.initialSalaries?.length) {
        rows.value = props.initialSalaries.map((salary) => ({
            key: nextKey++,
            id: salary.id,
            amount: salary.amount != null ? String(salary.amount) : '',
            currency: salary.currency ?? 'AFN',
            effectiveFrom: formatDate(salary.effective_from),
            effectiveTo: formatDate(salary.effective_to),
            notes: salary.notes ?? '',
        }));
    }
});
</script>

<template>
    <div class="space-y-4">
        <div v-if="rows.length === 0" class="ui-empty-state">
            {{ t('No salary records yet. Click below to add one.') }}
        </div>

        <div
            v-for="(row, index) in rows"
            :key="row.key"
            class="space-y-4 ui-inset-panel"
        >
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium">
                    {{ t('Salary :number', { number: String(index + 1) }) }}
                </p>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    @click="removeRow(index)"
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>

            <input
                v-if="row.id"
                type="hidden"
                :name="`salaries[${index}][id]`"
                :value="row.id"
            />

            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label :for="`salary_amount_${row.key}`">
                        {{ t('Monthly amount') }} *
                    </Label>
                    <Input
                        :id="`salary_amount_${row.key}`"
                        :name="`salaries[${index}][amount]`"
                        type="number"
                        min="0"
                        step="0.01"
                        :default-value="row.amount"
                    />
                    <InputError :message="fieldError(index, 'amount')" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`salary_currency_${row.key}`">
                        {{ t('Currency') }}
                    </Label>
                    <select
                        :id="`salary_currency_${row.key}`"
                        :name="`salaries[${index}][currency]`"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option
                            v-for="code in currencies ?? ['AFN']"
                            :key="code"
                            :value="code"
                            :selected="row.currency === code"
                        >
                            {{ code }}
                        </option>
                    </select>
                    <InputError :message="fieldError(index, 'currency')" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`salary_effective_from_${row.key}`">
                        {{ t('Effective from') }} *
                    </Label>
                    <Input
                        :id="`salary_effective_from_${row.key}`"
                        :name="`salaries[${index}][effective_from]`"
                        type="date"
                        :default-value="row.effectiveFrom"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ t('The date from which this monthly salary applies to payroll.') }}
                    </p>
                    <InputError :message="fieldError(index, 'effective_from')" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`salary_effective_to_${row.key}`">
                        {{ t('Effective to') }}
                    </Label>
                    <Input
                        :id="`salary_effective_to_${row.key}`"
                        :name="`salaries[${index}][effective_to]`"
                        type="date"
                        :default-value="row.effectiveTo"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ t('Optional. Last date this salary applies. Leave blank if it is still current.') }}
                    </p>
                    <InputError :message="fieldError(index, 'effective_to')" />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <Label :for="`salary_notes_${row.key}`">
                        {{ t('Notes') }}
                    </Label>
                    <Input
                        :id="`salary_notes_${row.key}`"
                        :name="`salaries[${index}][notes]`"
                        :default-value="row.notes"
                    />
                    <InputError :message="fieldError(index, 'notes')" />
                </div>
            </div>
        </div>

        <Button type="button" variant="outline" @click="addRow">
            <Plus class="size-4" />
            {{ t('Add salary') }}
        </Button>
    </div>
</template>
