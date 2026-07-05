<script setup lang="ts">
import { ShieldX } from '@lucide/vue';
import ErrorPage from '@/components/ErrorPage.vue';
</script>

<template>
    <ErrorPage
        :code="403"
        title="Access denied"
        :icon="ShieldX"
    />
</template>
