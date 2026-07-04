<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LanguageTabs from '@/components/LanguageTabs.vue';
import { useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/language';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Language settings',
                href: edit(),
            },
        ],
    },
});

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('Language settings')" />

    <h1 class="sr-only">{{ t('Language settings') }}</h1>

    <div class="space-y-6">
<LanguageTabs />
    </div>
</template>
