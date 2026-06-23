<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

defineProps<{
    tabs: Array<{ id: string; label: string }>;
    modelValue: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <div class="flex flex-wrap gap-1 border-b pb-1">
        <Button
            v-for="tab in tabs"
            :key="tab.id"
            variant="ghost"
            size="sm"
            :class="
                cn(
                    'rounded-b-none',
                    modelValue === tab.id &&
                        'border-b-2 border-primary bg-muted/50',
                )
            "
            @click="emit('update:modelValue', tab.id)"
        >
            {{ tab.label }}
        </Button>
    </div>
</template>
