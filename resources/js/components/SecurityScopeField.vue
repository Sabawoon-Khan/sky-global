<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { useMisPage } from '@/composables/useMisPage';

const props = withDefaults(
    defineProps<{
        selected?: string[] | null;
        error?: string;
        includeMarker?: boolean;
    }>(),
    {
        selected: () => [],
        includeMarker: false,
    },
);

const { t } = useMisPage();

const options = [
    { value: 'static', label: 'Static guards' },
    { value: 'mobile', label: 'Mobile patrol' },
    { value: 'vip', label: 'VIP' },
    { value: 'event', label: 'Event' },
] as const;

const isSelected = (value: string): boolean =>
    (props.selected ?? []).includes(value);
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ t('Scope type') }}</Label>
        <input
            v-if="includeMarker"
            type="hidden"
            name="has_security_scope"
            value="1"
        />
        <div
            class="grid gap-2 rounded-md border border-input p-3 sm:grid-cols-2"
        >
            <label
                v-for="opt in options"
                :key="opt.value"
                class="flex cursor-pointer items-center gap-2 text-sm"
            >
                <input
                    type="checkbox"
                    name="security_scope[]"
                    :value="opt.value"
                    :checked="isSelected(opt.value)"
                    class="size-4 rounded border border-input accent-primary"
                />
                {{ t(opt.label) }}
            </label>
        </div>
        <InputError :message="error" />
    </div>
</template>
