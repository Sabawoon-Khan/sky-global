<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
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
        <Heading
            variant="small"
            :title="t('Language settings')"
            :description="t('Update the language used across the application.')"
        />
        <LanguageTabs />
    </div>
</template>
