<script setup lang="ts">
import { FileQuestion } from '@lucide/vue';
import ErrorPage from '@/components/ErrorPage.vue';
</script>

<template>
    <ErrorPage
        :code="404"
        title="Page not found"
        :icon="FileQuestion"
    />
</template>
