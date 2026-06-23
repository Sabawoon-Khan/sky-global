<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
    narrow?: boolean;
}>();
</script>

<template>
    <div
        class="flex w-full flex-1 flex-col gap-4 px-3 py-3 sm:px-6 sm:py-4"
        :class="narrow ? 'max-w-5xl' : ''"
    >
        <slot />
    </div>
</template>
