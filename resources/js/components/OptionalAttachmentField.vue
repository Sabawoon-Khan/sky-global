<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

withDefaults(
    defineProps<{
        name?: string;
        label?: string;
        error?: string;
    }>(),
    {
        name: 'attachment',
        label: 'Attachment',
    },
);
</script>

<template>
    <div class="grid gap-2">
        <Label :for="name">
            {{ label }}
            <span class="font-normal text-muted-foreground">(optional)</span>
        </Label>
        <input
            :id="name"
            :name="name"
            type="file"
            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs file:border-0 file:bg-transparent file:text-sm file:font-medium"
        />
        <p class="text-xs text-muted-foreground">PDF, images, or documents up to 10 MB</p>
        <InputError :message="error" />
    </div>
</template>
