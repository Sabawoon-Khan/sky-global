<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { V2FormPage, V2FormSection } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn, formatDate } from '@/lib/format';

interface AvailableGuard {
    id: number;
    name: string;
    father_name: string;
    grandfather_name?: string | null;
    tazkira_number?: string | null;
    id_card_number?: string | null;
    batch_number: string;
    start_date?: string | null;
    end_date?: string | null;
}

interface BatchOption {
    batch_number: string;
    guard_count: number;
    training_start?: string | null;
    training_end?: string | null;
}

const props = defineProps<{
    availableGuards: AvailableGuard[];
    selectedIds?: number[];
    batches?: BatchOption[];
    preselectedBatch?: string | null;
    next_reference_number?: string;
}>();

const { t } = useMisPage();

const batchNumber = ref(props.preselectedBatch ?? '');

const selected = reactive<Record<number, { fee_amount: string }>>(
    Object.fromEntries(
        (props.selectedIds ?? []).map((id) => [id, { fee_amount: '' }]),
    ),
);

const activeBatch = computed(() =>
    (props.batches ?? []).find((row) => row.batch_number === batchNumber.value),
);

const guardsInBatch = computed(() =>
    props.availableGuards.filter(
        (guard) => guard.batch_number === batchNumber.value,
    ),
);

const selectedGuards = computed(() =>
    guardsInBatch.value.filter((guard) => selected[guard.id]),
);

const total = computed(() =>
    selectedGuards.value.reduce(
        (sum, guard) => sum + Number(selected[guard.id]?.fee_amount || 0),
        0,
    ),
);

const toggleGuard = (id: number): void => {
    if (selected[id]) {
        delete selected[id];
        return;
    }

    selected[id] = { fee_amount: '' };
};

const selectAllInBatch = (): void => {
    for (const guard of guardsInBatch.value) {
        if (!selected[guard.id]) {
            selected[guard.id] = { fee_amount: '' };
        }
    }
};

const clearSelection = (): void => {
    for (const guard of guardsInBatch.value) {
        delete selected[guard.id];
    }
};

watch(batchNumber, (next, prev) => {
    if (prev && next !== prev) {
        clearSelection();
    }
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Training', href: '/training/guards' },
            { title: 'Public Protection Deputy', href: '/training/payments' },
            { title: 'Record payment', href: '/training/payments/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Record ministry payment')" />

    <V2FormPage
        :title="t('Record ministry payment')"
        :eyebrow="t('Public Protection Deputy')"
        :description="
            t(
                'Choose the training batch you are paying for, then enter fees for each guard in that batch.',
            )
        "
        back-href="/training/payments"
    >
        <Form
            action="/training/payments"
            method="post"
            class="space-y-5"
            :options="{ forceFormData: true }"
            v-slot="{ errors, processing }"
        >
            <V2FormSection :title="t('Training batch')">
                <p class="mb-4 text-sm text-muted-foreground">
                    {{ t('Paying for') }}
                </p>
                <div class="mis-form-grid">
                    <div class="v2-field mis-form-span">
                        <Label for="batch_number">{{ t('Batch number') }} *</Label>
                        <select
                            id="batch_number"
                            v-model="batchNumber"
                            name="batch_number"
                            class="mis-form-select"
                            required
                        >
                            <option value="">{{ t('Select batch') }}</option>
                            <option
                                v-for="batch in batches ?? []"
                                :key="batch.batch_number"
                                :value="batch.batch_number"
                            >
                                {{ batch.batch_number }}
                                ({{ batch.guard_count }} {{ t('Guards') }})
                            </option>
                        </select>
                        <InputError :message="errors.batch_number" />
                    </div>
                </div>

                <div
                    v-if="activeBatch"
                    class="mt-4 grid gap-3 rounded-2xl border border-primary/20 bg-primary/5 p-4 sm:grid-cols-3"
                >
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            {{ t('Batch number') }}
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ activeBatch.batch_number }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            {{ t('Training period') }}
                        </p>
                        <p class="mt-1 text-sm font-medium">
                            {{ formatDate(activeBatch.training_start) }}
                            –
                            {{ formatDate(activeBatch.training_end) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            {{ t('Registered guards') }}
                        </p>
                        <p class="mt-1 text-sm font-medium">
                            {{ activeBatch.guard_count }}
                        </p>
                    </div>
                </div>

                <p
                    v-else-if="!(batches?.length)"
                    class="mt-2 text-sm text-muted-foreground"
                >
                    {{ t('No registered guards are waiting for assignment.') }}
                </p>
            </V2FormSection>

            <V2FormSection :title="t('Payment details')">
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
                        <Label for="payment_date">{{ t('Payment date') }} *</Label>
                        <Input id="payment_date" name="payment_date" type="date" required />
                        <InputError :message="errors.payment_date" />
                    </div>
                    <div class="v2-field">
                        <Label for="period_start">{{ t('Period start') }} *</Label>
                        <Input id="period_start" name="period_start" type="date" required />
                        <InputError :message="errors.period_start" />
                    </div>
                    <div class="v2-field">
                        <Label for="period_end">{{ t('Period end') }} *</Label>
                        <Input id="period_end" name="period_end" type="date" required />
                        <InputError :message="errors.period_end" />
                    </div>
                    <div class="v2-field mis-form-span">
                        <Label for="notes">{{ t('Notes') }}</Label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="2"
                            class="mis-form-textarea"
                        />
                    </div>
                    <div class="v2-field mis-form-span">
                        <OptionalAttachmentField
                            name="receipt"
                            :label="t('Receipt')"
                            :error="errors.receipt"
                        />
                    </div>
                </div>
            </V2FormSection>

            <V2FormSection :title="t('Select guards')">
                <div
                    v-if="batchNumber && guardsInBatch.length"
                    class="mb-4 flex flex-wrap gap-2"
                >
                    <Button type="button" variant="outline" size="sm" @click="selectAllInBatch">
                        {{ t('Select all in batch') }}
                    </Button>
                    <Button type="button" variant="ghost" size="sm" @click="clearSelection">
                        {{ t('Clear selection') }}
                    </Button>
                </div>

                <p v-if="!batchNumber" class="text-sm text-muted-foreground">
                    {{ t('Select batch') }}
                </p>
                <p
                    v-else-if="!guardsInBatch.length"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No registered guards are waiting for assignment.') }}
                </p>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="w-10 py-2" />
                                <th class="py-2">{{ t('Name') }}</th>
                                <th class="py-2">{{ t("Father's name") }}</th>
                                <th class="py-2">{{ t('Tazkira number') }}</th>
                                <th class="py-2">{{ t('Fee amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="guard in guardsInBatch"
                                :key="guard.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-2">
                                    <input
                                        type="checkbox"
                                        class="size-4 rounded border border-input"
                                        :checked="Boolean(selected[guard.id])"
                                        @change="toggleGuard(guard.id)"
                                    />
                                </td>
                                <td class="py-2 font-medium">{{ guard.name }}</td>
                                <td class="py-2 text-muted-foreground">
                                    {{ guard.father_name }}
                                </td>
                                <td class="py-2 text-muted-foreground">
                                    {{ guard.tazkira_number ?? '—' }}
                                </td>
                                <td class="py-2">
                                    <template v-if="selected[guard.id]">
                                        <input
                                            type="hidden"
                                            :name="`guards[${guard.id}][id]`"
                                            :value="guard.id"
                                        />
                                        <Input
                                            :name="`guards[${guard.id}][fee_amount]`"
                                            v-model="selected[guard.id].fee_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            class="max-w-[10rem]"
                                        />
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <InputError :message="errors.guards" />
                <p v-if="batchNumber" class="mt-4 text-sm font-medium">
                    {{ t('Batch number') }}: {{ batchNumber }}
                    ·
                    {{ t('Total payment') }}:
                    {{ formatAfn(total) }}
                    ·
                    {{ selectedGuards.length }}
                    /
                    {{ guardsInBatch.length }}
                    {{ t('Guards') }}
                </p>
            </V2FormSection>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Button variant="outline" as-child class="sm:min-w-28">
                    <Link href="/training/payments">{{ t('Cancel') }}</Link>
                </Button>
                <Button
                    type="submit"
                    :disabled="
                        processing ||
                        !batchNumber ||
                        selectedGuards.length === 0
                    "
                    class="sm:min-w-40"
                >
                    {{ t('Record ministry payment') }}
                </Button>
            </div>
        </Form>
    </V2FormPage>
</template>
