<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

export interface AgreementRecord {
    id?: number;
    agreement_number?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    notes?: string | null;
}

interface AgreementRow {
    key: number;
    id?: number;
    agreementNumber: string;
    startDate: string;
    endDate: string;
    notes: string;
}

const props = defineProps<{
    initialAgreements?: AgreementRecord[];
    errors?: Record<string, string>;
}>();

const { t } = useTranslations();

let nextKey = 0;
const rows = ref<AgreementRow[]>([]);

const formatDate = (value?: string | null): string => {
    if (!value) return '';
    return value.slice(0, 10);
};

const addRow = (): void => {
    rows.value.push({
        key: nextKey++,
        agreementNumber: '',
        startDate: '',
        endDate: '',
        notes: '',
    });
};

const removeRow = (index: number): void => {
    rows.value.splice(index, 1);
};

const fieldError = (index: number, field: string): string | undefined =>
    props.errors?.[`agreements.${index}.${field}`];

onMounted(() => {
    if (props.initialAgreements?.length) {
        rows.value = props.initialAgreements.map((agreement) => ({
            key: nextKey++,
            id: agreement.id,
            agreementNumber: agreement.agreement_number ?? '',
            startDate: formatDate(agreement.start_date),
            endDate: formatDate(agreement.end_date),
            notes: agreement.notes ?? '',
        }));
    }
});
</script>

<template>
    <div class="space-y-4">
        <div v-if="rows.length === 0" class="ui-empty-state">
            {{ t('No agreements added yet. Click below to add one.') }}
        </div>

        <div
            v-for="(row, index) in rows"
            :key="row.key"
            class="space-y-4 ui-inset-panel"
        >
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium">
                    {{ t('Agreement :number', { number: String(index + 1) }) }}
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
                :name="`agreements[${index}][id]`"
                :value="row.id"
            />

            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label :for="`agreement_number_${row.key}`">
                        {{ t('Agreement number') }}
                    </Label>
                    <Input
                        :id="`agreement_number_${row.key}`"
                        :name="`agreements[${index}][agreement_number]`"
                        :default-value="row.agreementNumber"
                    />
                    <InputError :message="fieldError(index, 'agreement_number')" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`agreement_start_${row.key}`">
                        {{ t('Start date') }} *
                    </Label>
                    <Input
                        :id="`agreement_start_${row.key}`"
                        :name="`agreements[${index}][start_date]`"
                        type="date"
                        required
                        :default-value="row.startDate"
                    />
                    <InputError :message="fieldError(index, 'start_date')" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`agreement_end_${row.key}`">
                        {{ t('End date') }}
                    </Label>
                    <Input
                        :id="`agreement_end_${row.key}`"
                        :name="`agreements[${index}][end_date]`"
                        type="date"
                        :default-value="row.endDate"
                    />
                    <InputError :message="fieldError(index, 'end_date')" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`agreement_file_${row.key}`">
                        {{ t('Agreement file') }}
                    </Label>
                    <input
                        :id="`agreement_file_${row.key}`"
                        :name="`agreements[${index}][file]`"
                        type="file"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs file:border-0 file:bg-transparent file:text-sm file:font-medium"
                    />
                    <InputError :message="fieldError(index, 'file')" />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <Label :for="`agreement_notes_${row.key}`">
                        {{ t('Notes') }}
                    </Label>
                    <textarea
                        :id="`agreement_notes_${row.key}`"
                        :name="`agreements[${index}][notes]`"
                        rows="2"
                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                    >{{ row.notes }}</textarea>
                    <InputError :message="fieldError(index, 'notes')" />
                </div>
            </div>
        </div>

        <Button type="button" variant="outline" @click="addRow">
            <Plus class="size-4" />
            {{ t('Add agreement') }}
        </Button>
    </div>
</template>
