<script setup lang="ts">
import { FileQuestion } from '@lucide/vue';
import ErrorPage from '@/components/ErrorPage.vue';
</script>

<template>
    <ErrorPage
        :code="404"
        title="Page not found"
        description="Sorry, the page you are looking for does not exist or may have been moved."
        :icon="FileQuestion"
    />
</template>
