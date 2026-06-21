<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
    narrow?: boolean;
}>();
</script>

<template>
    <div
        class="flex flex-1 flex-col gap-4 px-2 py-3 sm:px-4 sm:py-4"
        :class="narrow ? 'mx-auto w-full max-w-3xl' : 'mx-auto w-full max-w-6xl'"
    >
        <slot />
    </div>
</template>
