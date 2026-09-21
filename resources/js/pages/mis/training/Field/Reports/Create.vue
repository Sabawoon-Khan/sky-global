<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import InputError from '@/components/InputError.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import TrainingFieldSubnav from '@/components/mis/TrainingFieldSubnav.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { V2FormPage, V2FormSection } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';

interface RosterGuard {
    id: number;
    name: string;
    father_name: string;
    tazkira_number?: string | null;
    id_card_number?: string | null;
    site?: string | null;
}

const props = defineProps<{
    roster: RosterGuard[];
    selectedIds?: number[];
    next_reference_number?: string;
}>();

const { t } = useMisPage();

const selected = reactive<Record<number, boolean>>(
    Object.fromEntries((props.selectedIds ?? []).map((id) => [id, true])),
);

const selectedCount = computed(
    () => Object.keys(selected).filter((id) => selected[Number(id)]).length,
);

const toggle = (id: number): void => {
    if (selected[id]) {
        delete selected[id];
        return;
    }
    selected[id] = true;
};

const selectAll = (): void => {
    for (const guard of props.roster) {
        selected[guard.id] = true;
    }
};

const clearAll = (): void => {
    for (const key of Object.keys(selected)) {
        delete selected[Number(key)];
    }
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Training', href: '/training/guards' },
            { title: 'On-site training', href: '/training/field/reports' },
            { title: 'New visit report', href: '/training/field/reports/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('New visit report')" />

    <TrainingFieldSubnav />

    <V2FormPage
        :title="t('New visit report')"
        :eyebrow="t('On-site training')"
        :description="
            t(
                'Record what the trainer did on site: date, description, and optional attachment.',
            )
        "
        back-href="/training/field/reports"
    >
        <Form
            action="/training/field/reports"
            method="post"
            class="space-y-5"
            :options="{ forceFormData: true }"
            v-slot="{ errors, processing }"
        >
            <V2FormSection :title="t('Report details')">
                <div class="mis-form-grid">
                    <div class="v2-field">
                        <Label for="reference_number">{{ t('Reference #') }}</Label>
                        <Input
                            id="reference_number"
                            name="reference_number"
                            :default-value="next_reference_number"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ t('Auto-generated. You can change this before saving.') }}
                        </p>
                        <InputError :message="errors.reference_number" />
                    </div>
                    <div class="v2-field">
                        <Label for="report_date">{{ t('Report date') }} *</Label>
                        <Input
                            id="report_date"
                            name="report_date"
                            type="date"
                            required
                        />
                        <InputError :message="errors.report_date" />
                    </div>
                    <div class="v2-field">
                        <Label for="trainer_name">{{ t('Trainer') }}</Label>
                        <Input id="trainer_name" name="trainer_name" />
                        <InputError :message="errors.trainer_name" />
                    </div>
                    <div class="v2-field mis-form-span">
                        <Label for="description">{{ t('Description') }} *</Label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="mis-form-textarea"
                            required
                            :placeholder="
                                t(
                                    'Topics covered, duration, location, observations…',
                                )
                            "
                        />
                        <InputError :message="errors.description" />
                    </div>
                    <div class="v2-field mis-form-span">
                        <OptionalAttachmentField
                            name="attachment"
                            :label="t('Attachment')"
                            :error="errors.attachment"
                        />
                    </div>
                </div>
            </V2FormSection>

            <V2FormSection :title="t('Guards trained')">
                <div
                    v-if="roster.length"
                    class="mb-4 flex flex-wrap gap-2"
                >
                    <Button type="button" variant="outline" size="sm" @click="selectAll">
                        {{ t('Select all') }}
                    </Button>
                    <Button type="button" variant="ghost" size="sm" @click="clearAll">
                        {{ t('Clear selection') }}
                    </Button>
                </div>

                <p v-if="!roster.length" class="text-sm text-muted-foreground">
                    {{ t('Add guards to the existing guards roster first.') }}
                    <Link
                        href="/training/field/roster"
                        class="font-medium text-primary"
                    >
                        {{ t('Existing guards') }}
                    </Link>
                </p>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="w-10 py-2" />
                                <th class="py-2">{{ t('Name') }}</th>
                                <th class="py-2">{{ t("Father's name") }}</th>
                                <th class="py-2">{{ t('Site') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="guard in roster"
                                :key="guard.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-2">
                                    <input
                                        type="checkbox"
                                        class="size-4 rounded border border-input"
                                        :checked="Boolean(selected[guard.id])"
                                        @change="toggle(guard.id)"
                                    />
                                    <input
                                        v-if="selected[guard.id]"
                                        type="hidden"
                                        name="guard_ids[]"
                                        :value="guard.id"
                                    />
                                </td>
                                <td class="py-2 font-medium">{{ guard.name }}</td>
                                <td class="py-2 text-muted-foreground">
                                    {{ guard.father_name }}
                                </td>
                                <td class="py-2 text-muted-foreground">
                                    {{ guard.site ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <InputError :message="errors.guard_ids" />
                <p v-if="roster.length" class="mt-2 text-sm text-muted-foreground">
                    {{ selectedCount }} {{ t('Guards') }}
                </p>
            </V2FormSection>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Button variant="outline" as-child class="sm:min-w-28">
                    <Link href="/training/field/reports">{{ t('Cancel') }}</Link>
                </Button>
                <Button
                    type="submit"
                    :disabled="
                        processing || !roster.length || selectedCount === 0
                    "
                    class="sm:min-w-40"
                >
                    {{ t('Save report') }}
                </Button>
            </div>
        </Form>
    </V2FormPage>
</template>
