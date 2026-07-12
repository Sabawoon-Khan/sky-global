<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

export interface ProjectOption {
    id: number;
    code: string;
    name: string;
}

export interface RateRecord {
    id?: number;
    project_id?: number | null;
    daily_rate?: number | null;
    monthly_rate?: number | null;
    currency?: string | null;
    effective_from?: string | null;
    effective_to?: string | null;
}

interface RateRow {
    key: number;
    id?: number;
    projectId: string;
    dailyRate: string;
    monthlyRate: string;
    currency: string;
    effectiveFrom: string;
    effectiveTo: string;
}

const props = defineProps<{
    projects: ProjectOption[];
    currencies?: string[];
    initialRates?: RateRecord[];
    errors?: Record<string, string>;
}>();

const { t } = useTranslations();

let nextKey = 0;
const rows = ref<RateRow[]>([]);

const formatDate = (value?: string | null): string => {
    if (!value) return '';
    return value.slice(0, 10);
};

const addRow = (): void => {
    rows.value.push({
        key: nextKey++,
        projectId: '',
        dailyRate: '',
        monthlyRate: '',
        currency: 'USD',
        effectiveFrom: '',
        effectiveTo: '',
    });
};

const removeRow = (index: number): void => {
    rows.value.splice(index, 1);
};

const fieldError = (index: number, field: string): string | undefined =>
    props.errors?.[`rates.${index}.${field}`];

onMounted(() => {
    if (props.initialRates?.length) {
        rows.value = props.initialRates.map((rate) => ({
            key: nextKey++,
            id: rate.id,
            projectId: rate.project_id ? String(rate.project_id) : '',
            dailyRate: rate.daily_rate != null ? String(rate.daily_rate) : '',
            monthlyRate: rate.monthly_rate != null ? String(rate.monthly_rate) : '',
            currency: rate.currency ?? 'USD',
            effectiveFrom: formatDate(rate.effective_from),
            effectiveTo: formatDate(rate.effective_to),
        }));
    }
});
</script>

<template>
    <div class="space-y-4">
        <div v-if="rows.length === 0" class="ui-empty-state">
            {{ t('No rates configured yet. Click below to add one.') }}
        </div>

        <div
            v-for="(row, index) in rows"
            :key="row.key"
            class="space-y-4 ui-inset-panel"
        >
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium">
                    {{ t('Rate :number', { number: String(index + 1) }) }}
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
                :name="`rates[${index}][id]`"
                :value="row.id"
            />

            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2 md:col-span-2">
                    <Label :for="`rate_project_${row.key}`">
                        {{ t('Project') }}
                    </Label>
                    <select
                        :id="`rate_project_${row.key}`"
                        :name="`rates[${index}][project_id]`"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option value="" :selected="!row.projectId">
                            {{ t('General (all projects)') }}
                        </option>
                        <option
                            v-for="project in projects"
                            :key="project.id"
                            :value="project.id"
                            :selected="row.projectId === String(project.id)"
                        >
                            {{ project.code }} — {{ project.name }}
                        </option>
                    </select>
                    <InputError :message="fieldError(index, 'project_id')" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`rate_daily_${row.key}`">
                        {{ t('Daily rate') }}
                    </Label>
                    <Input
                        :id="`rate_daily_${row.key}`"
                        :name="`rates[${index}][daily_rate]`"
                        type="number"
                        min="0"
                        step="0.01"
                        :default-value="row.dailyRate"
                    />
                    <InputError :message="fieldError(index, 'daily_rate')" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`rate_monthly_${row.key}`">
                        {{ t('Monthly rate') }}
                    </Label>
                    <Input
                        :id="`rate_monthly_${row.key}`"
                        :name="`rates[${index}][monthly_rate]`"
                        type="number"
                        min="0"
                        step="0.01"
                        :default-value="row.monthlyRate"
                    />
                    <InputError :message="fieldError(index, 'monthly_rate')" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`rate_currency_${row.key}`">
                        {{ t('Currency') }}
                    </Label>
                    <select
                        :id="`rate_currency_${row.key}`"
                        :name="`rates[${index}][currency]`"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option
                            v-for="code in currencies ?? ['USD']"
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
                    <Label :for="`rate_effective_from_${row.key}`">
                        {{ t('Effective from') }}
                    </Label>
                    <Input
                        :id="`rate_effective_from_${row.key}`"
                        :name="`rates[${index}][effective_from]`"
                        type="date"
                        :default-value="row.effectiveFrom"
                    />
                    <InputError :message="fieldError(index, 'effective_from')" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`rate_effective_to_${row.key}`">
                        {{ t('Effective to') }}
                    </Label>
                    <Input
                        :id="`rate_effective_to_${row.key}`"
                        :name="`rates[${index}][effective_to]`"
                        type="date"
                        :default-value="row.effectiveTo"
                    />
                    <InputError :message="fieldError(index, 'effective_to')" />
                </div>
            </div>
        </div>

        <Button type="button" variant="outline" @click="addRow">
            <Plus class="size-4" />
            {{ t('Add rate') }}
        </Button>
    </div>
</template>
