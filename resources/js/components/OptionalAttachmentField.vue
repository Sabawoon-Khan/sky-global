<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

const props = withDefaults(
    defineProps<{
        name?: string;
        label?: string;
        error?: string;
    }>(),
    {
        name: 'attachment',
    },
);

const { t } = useTranslations();
</script>

<template>
    <div class="grid gap-2">
        <Label :for="props.name">
            {{ props.label ? t(props.label) : t('Attachment') }}
            <span class="font-normal text-muted-foreground">{{ t('(optional)') }}</span>
        </Label>
        <input
            :id="props.name"
            :name="props.name"
            type="file"
            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs file:border-0 file:bg-transparent file:text-sm file:font-medium"
        />
        <p class="text-xs text-muted-foreground">
            {{ t('PDF, images, or documents up to 10 MB') }}
        </p>
        <InputError :message="props.error" />
    </div>
</template>
