<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import { useTranslations } from '@/composables/useTranslations';

const { locale, locales, updateLocale } = useLocale();
const { t } = useTranslations();
</script>

<template>
    <div
        class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800"
    >
        <button
            v-for="option in locales"
            :key="option.code"
            type="button"
            @click="updateLocale(option.code)"
            :class="[
                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                locale === option.code
                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
            ]"
        >
            <span class="text-sm">{{ option.native }}</span>
            <span
                v-if="option.native !== option.name"
                class="ms-1.5 text-xs text-muted-foreground"
            >
                {{ option.name }}
            </span>
        </button>
    </div>
</template>
