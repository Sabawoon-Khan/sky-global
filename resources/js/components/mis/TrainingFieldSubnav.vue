<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();
const page = usePage();

const path = computed(() => page.url.split('?')[0] ?? '');

const tabs = computed(() => [
    {
        id: 'reports',
        label: t('Visit reports'),
        href: '/training/field/reports',
        active:
            path.value.startsWith('/training/field/reports') ||
            path.value === '/training/field',
    },
    {
        id: 'roster',
        label: t('Existing guards'),
        href: '/training/field/roster',
        active: path.value.startsWith('/training/field/roster'),
    },
]);
</script>

<template>
    <nav
        class="mb-6 flex flex-wrap gap-2 rounded-2xl border border-border/70 bg-muted/20 p-1.5"
        :aria-label="t('On-site training')"
    >
        <Link
            v-for="tab in tabs"
            :key="tab.id"
            :href="tab.href"
            class="rounded-xl px-4 py-2 text-sm font-medium transition-colors"
            :class="
                tab.active
                    ? 'bg-background text-foreground shadow-sm'
                    : 'text-muted-foreground hover:text-foreground'
            "
        >
            {{ tab.label }}
        </Link>
    </nav>
</template>
