<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { V2FormPage, V2FormSection } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';

interface TrainingGuard {
    id: number;
    name: string;
    father_name: string;
    grandfather_name?: string | null;
    tazkira_number?: string | null;
    id_card_number?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    batch_number: string;
    notes?: string | null;
}

const props = defineProps<{
    guard: TrainingGuard;
    batches?: string[];
}>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Training', href: '/training/guards' },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Edit :name', { name: guard.name })" />

    <V2FormPage
        :title="t('Edit :name', { name: guard.name })"
        :eyebrow="t('Training')"
        :back-href="`/training/guards/${guard.id}`"
    >
        <Form
            :action="`/training/guards/${guard.id}`"
            method="put"
            class="space-y-5"
            v-slot="{ errors, processing }"
        >
            <V2FormSection :title="t('Personal details')">
                <div class="mis-form-grid">
                    <div class="v2-field">
                        <Label for="name">{{ t('Name') }} *</Label>
                        <Input id="name" name="name" :default-value="guard.name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="v2-field">
                        <Label for="father_name">{{ t("Father's name") }} *</Label>
                        <Input
                            id="father_name"
                            name="father_name"
                            :default-value="guard.father_name"
                            required
                        />
                        <InputError :message="errors.father_name" />
                    </div>
                    <div class="v2-field">
                        <Label for="grandfather_name">{{ t("Grandfather's name") }}</Label>
                        <Input
                            id="grandfather_name"
                            name="grandfather_name"
                            :default-value="guard.grandfather_name ?? ''"
                        />
                        <InputError :message="errors.grandfather_name" />
                    </div>
                    <div class="v2-field">
                        <Label for="tazkira_number">{{ t('Tazkira number') }}</Label>
                        <Input
                            id="tazkira_number"
                            name="tazkira_number"
                            :default-value="guard.tazkira_number ?? ''"
                        />
                        <InputError :message="errors.tazkira_number" />
                    </div>
                    <div class="v2-field">
                        <Label for="id_card_number">{{ t('ID card number') }}</Label>
                        <Input
                            id="id_card_number"
                            name="id_card_number"
                            :default-value="guard.id_card_number ?? ''"
                        />
                        <InputError :message="errors.id_card_number" />
                    </div>
                </div>
            </V2FormSection>

            <V2FormSection :title="t('Training period')">
                <div class="mis-form-grid">
                    <div class="v2-field">
                        <Label for="batch_number">{{ t('Batch number') }} *</Label>
                        <Input
                            id="batch_number"
                            name="batch_number"
                            list="training-batches"
                            :default-value="guard.batch_number"
                            required
                        />
                        <datalist id="training-batches">
                            <option
                                v-for="batch in batches ?? []"
                                :key="batch"
                                :value="batch"
                            />
                        </datalist>
                        <InputError :message="errors.batch_number" />
                    </div>
                    <div class="v2-field">
                        <Label for="start_date">{{ t('Start date') }} *</Label>
                        <Input
                            id="start_date"
                            name="start_date"
                            type="date"
                            :default-value="guard.start_date ?? ''"
                            required
                        />
                        <InputError :message="errors.start_date" />
                    </div>
                    <div class="v2-field">
                        <Label for="end_date">{{ t('End date') }} *</Label>
                        <Input
                            id="end_date"
                            name="end_date"
                            type="date"
                            :default-value="guard.end_date ?? ''"
                            required
                        />
                        <InputError :message="errors.end_date" />
                    </div>
                    <div class="v2-field mis-form-span">
                        <Label for="notes">{{ t('Notes') }}</Label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="mis-form-textarea"
                        >{{ guard.notes ?? '' }}</textarea>
                        <InputError :message="errors.notes" />
                    </div>
                </div>
            </V2FormSection>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Button variant="outline" as-child class="sm:min-w-28">
                    <Link :href="`/training/guards/${guard.id}`">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing" class="sm:min-w-40">
                    {{ t('Save') }}
                </Button>
            </div>
        </Form>
    </V2FormPage>
</template>
