<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

const props = withDefaults(
    defineProps<{
        name?: string;
        label?: string;
        error?: string;
        required?: boolean;
    }>(),
    {
        name: 'attachment',
        required: false,
    },
);

const { t } = useTranslations();
</script>

<template>
    <div class="grid gap-2">
        <Label :for="props.name">
            {{ props.label ? t(props.label) : t('Attachment') }}
            <span v-if="!required" class="font-normal text-muted-foreground">{{ t('(optional)') }}</span>
            <span v-else class="text-destructive"> *</span>
        </Label>
        <input
            :id="props.name"
            :name="props.name"
            type="file"
            :required="required"
            class="flex min-h-10 w-full cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs file:me-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90"
        />
        <InputError :message="props.error" />
    </div>
</template>
