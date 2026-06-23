<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { FileText, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AttachmentTypeOption } from '@/components/PersonnelFormsField.vue';
import { useTranslations } from '@/composables/useTranslations';

export interface PersonnelFormRecord {
    id: number;
    download_url: string;
    issued_at?: string | null;
    expires_at?: string | null;
    notes?: string | null;
    created_at: string;
    attachment_type?: {
        id: number;
        name: string;
        requires_expiry?: boolean;
    } | null;
}

const props = defineProps<{
    personnelType: string;
    personnelId: number;
    forms: PersonnelFormRecord[];
    attachmentTypes: AttachmentTypeOption[];
    canManage?: boolean;
}>();

const { t } = useTranslations();

const showAddForm = ref(false);
const selectedTypeId = ref('');

const formatDate = (value?: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );
};

const removeForm = (formId: number): void => {
    router.delete(`/forms/personnel-attachments/${formId}`, {
        preserveScroll: true,
    });
};

const selectedType = () =>
    props.attachmentTypes.find(
        (type) => String(type.id) === selectedTypeId.value,
    );
</script>

<template>
    <Card>
        <CardHeader class="pb-3">
            <CardTitle class="flex items-center gap-2 text-base">
                <FileText class="size-4" />
                {{ t('Employee forms') }}
            </CardTitle>
            <CardDescription>
                {{ t('Guarantee letters, certificates, and other HR documents') }}
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
            <div
                v-if="forms.length === 0"
                class="py-4 text-center text-sm text-muted-foreground"
            >
                {{ t('No forms uploaded yet.') }}
            </div>

            <ul v-else class="divide-y">
                <li
                    v-for="form in forms"
                    :key="form.id"
                    class="flex items-start justify-between gap-3 py-3"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-medium">
                            {{ form.attachment_type?.name ?? t('Form') }}
                        </p>
                        <a
                            :href="form.download_url"
                            class="text-sm text-primary hover:underline"
                        >
                            {{ t('Download file') }}
                        </a>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('Uploaded') }}: {{ formatDate(form.created_at) }}
                            <template v-if="form.issued_at">
                                · {{ t('Issued') }}: {{ formatDate(form.issued_at) }}
                            </template>
                            <template v-if="form.expires_at">
                                · {{ t('Expires') }}: {{ formatDate(form.expires_at) }}
                            </template>
                        </p>
                        <p
                            v-if="form.notes"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            {{ form.notes }}
                        </p>
                    </div>
                    <Button
                        v-if="canManage"
                        variant="ghost"
                        size="icon"
                        class="shrink-0"
                        @click="removeForm(form.id)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </li>
            </ul>

            <div v-if="canManage && attachmentTypes.length > 0">
                <Button
                    v-if="!showAddForm"
                    type="button"
                    variant="outline"
                    @click="showAddForm = true"
                >
                    <Plus class="size-4" />
                    {{ t('Add form') }}
                </Button>

                <Form
                    v-else
                    action="/forms/personnel-attachments"
                    method="post"
                    class="space-y-4 rounded-lg border p-4"
                    :options="{ forceFormData: true }"
                    @success="showAddForm = false"
                    v-slot="{ errors, processing }"
                >
                    <input type="hidden" name="personnel_type" :value="personnelType" />
                    <input type="hidden" name="personnel_id" :value="personnelId" />

                    <div class="grid gap-2">
                        <Label for="add_form_type">{{ t('Form type') }} *</Label>
                        <select
                            id="add_form_type"
                            v-model="selectedTypeId"
                            name="attachment_type_id"
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
                        <InputError :message="errors.attachment_type_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="add_form_file">{{ t('File') }} *</Label>
                        <input
                            id="add_form_file"
                            name="file"
                            type="file"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs file:border-0 file:bg-transparent file:text-sm file:font-medium"
                        />
                        <InputError :message="errors.file" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="add_form_issued">{{ t('Issued date') }}</Label>
                            <Input id="add_form_issued" name="issued_at" type="date" />
                        </div>
                        <div
                            v-if="selectedType()?.requires_expiry"
                            class="grid gap-2"
                        >
                            <Label for="add_form_expires">{{ t('Expiry date') }}</Label>
                            <Input id="add_form_expires" name="expires_at" type="date" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="add_form_notes">{{ t('Notes') }}</Label>
                        <textarea
                            id="add_form_notes"
                            name="notes"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="flex gap-2">
                        <Button type="submit" :disabled="processing">
                            {{ t('Upload form') }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="showAddForm = false"
                        >
                            {{ t('Cancel') }}
                        </Button>
                    </div>
                </Form>
            </div>
        </CardContent>
    </Card>
</template>
