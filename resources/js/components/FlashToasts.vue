<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { showFlashToast } from '@/lib/flashToast';

const page = usePage();

onMounted(() => {
    showFlashToast(page.flash as Record<string, unknown>);

    router.on('flash', (event) => {
        showFlashToast((event as CustomEvent).detail?.flash);
    });
});
</script>

<template />
