<script setup lang="ts">
import { ShieldX } from '@lucide/vue';
import ErrorPage from '@/components/ErrorPage.vue';
</script>

<template>
    <ErrorPage
        :code="403"
        title="Access denied"
        description="You do not have permission to view this page. Contact your administrator if you believe this is a mistake."
        :icon="ShieldX"
    />
</template>
