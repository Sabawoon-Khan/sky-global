<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import { useTranslations } from '@/composables/useTranslations';

const { title = '' } = defineProps<{
    title?: string;
}>();

const { t } = useTranslations();
</script>

<template>
    <AuthLayout :title="t(title)">
        <slot />
    </AuthLayout>
</template>
