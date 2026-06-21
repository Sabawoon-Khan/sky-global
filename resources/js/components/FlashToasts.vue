<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import { showFlashToast } from '@/lib/flashToast';

const page = usePage();

watch(
    () => page.flash,
    (flash) => {
        if (!flash || Object.keys(flash).length === 0) {
            return;
        }

        showFlashToast(flash as Record<string, unknown>);
    },
    { deep: true },
);

onMounted(() => {
    router.on('flash', (event) => {
        showFlashToast((event as CustomEvent).detail?.flash);
    });
});
</script>

<template />
