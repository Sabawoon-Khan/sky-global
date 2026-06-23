<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

export interface AttachmentTypeOption {
    id: number;
    name: string;
    requires_expiry?: boolean;
}

interface FormRow {
    key: number;
    attachmentTypeId: string;
}

const props = defineProps<{
    attachmentTypes: AttachmentTypeOption[];
    errors?: Record<string, string>;
}>();

const { t } = useTranslations();

let nextKey = 0;
const rows = ref<FormRow[]>([]);

const hasTypes = computed(() => props.attachmentTypes.length > 0);

const addRow = (): void => {
    rows.value.push({
        key: nextKey++,
        attachmentTypeId: '',
    });
};

const removeRow = (index: number): void => {
    rows.value.splice(index, 1);
};

const typeRequiresExpiry = (typeId: string): boolean =>
    props.attachmentTypes.find((type) => String(type.id) === typeId)
        ?.requires_expiry ?? false;

const fieldError = (index: number, field: string): string | undefined =>
    props.errors?.[`personnel_forms.${index}.${field}`];
</script>

<template>
    <div class="space-y-4">
        <p v-if="!hasTypes" class="text-sm text-muted-foreground">
            {{
                t(
                    'No form types configured. Add types in Settings → Form Types first.',
                )
            }}
        </p>

        <template v-else>
            <div
                v-if="rows.length === 0"
                class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                {{ t('No forms added yet. Click below to attach employee forms.') }}
            </div>

            <div
                v-for="(row, index) in rows"
                :key="row.key"
                class="space-y-4 rounded-lg border p-4"
            >
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-medium">
                        {{ t('Form :number', { number: String(index + 1) }) }}
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

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2 md:col-span-2">
                        <Label :for="`personnel_form_type_${row.key}`">
                            {{ t('Form type') }} *
                        </Label>
                        <select
                            :id="`personnel_form_type_${row.key}`"
                            v-model="row.attachmentTypeId"
                            :name="`personnel_forms[${index}][attachment_type_id]`"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option value="">{{ t('Select form type') }}</option>
                            <option
                                v-for="type in attachmentTypes"
                                :key="type.id"
                                :value="type.id"
                            >
                                {{ type.name }}
                            </option>
                        </select>
                        <InputError :message="fieldError(index, 'attachment_type_id')" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label :for="`personnel_form_file_${row.key}`">
                            {{ t('File') }} *
                        </Label>
                        <input
                            :id="`personnel_form_file_${row.key}`"
                            :name="`personnel_forms[${index}][file]`"
                            type="file"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs file:border-0 file:bg-transparent file:text-sm file:font-medium"
                        />
                        <InputError :message="fieldError(index, 'file')" />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`personnel_form_issued_${row.key}`">
                            {{ t('Issued date') }}
                        </Label>
                        <Input
                            :id="`personnel_form_issued_${row.key}`"
                            :name="`personnel_forms[${index}][issued_at]`"
                            type="date"
                        />
                        <InputError :message="fieldError(index, 'issued_at')" />
                    </div>

                    <div
                        v-if="typeRequiresExpiry(row.attachmentTypeId)"
                        class="grid gap-2"
                    >
                        <Label :for="`personnel_form_expires_${row.key}`">
                            {{ t('Expiry date') }}
                        </Label>
                        <Input
                            :id="`personnel_form_expires_${row.key}`"
                            :name="`personnel_forms[${index}][expires_at]`"
                            type="date"
                        />
                        <InputError :message="fieldError(index, 'expires_at')" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label :for="`personnel_form_notes_${row.key}`">
                            {{ t('Notes') }}
                        </Label>
                        <textarea
                            :id="`personnel_form_notes_${row.key}`"
                            :name="`personnel_forms[${index}][notes]`"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                        <InputError :message="fieldError(index, 'notes')" />
                    </div>
                </div>
            </div>

            <Button type="button" variant="outline" @click="addRow">
                <Plus class="size-4" />
                {{ t('Add form') }}
            </Button>
        </template>
    </div>
</template>
