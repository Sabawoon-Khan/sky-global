<script setup lang="ts">
import { FileSpreadsheet, Printer } from '@lucide/vue';
import { usePageExport } from '@/composables/usePageExport';
import { useTranslations } from '@/composables/useTranslations';

withDefaults(
    defineProps<{
        filename?: string;
        variant?: 'ghost' | 'outline';
        class?: string;
    }>(),
    {
        filename: undefined,
        variant: 'ghost',
        class: undefined,
    },
);

const { t } = useTranslations();
const { printPage, exportExcel } = usePageExport();
</script>

<template>
    <div
        class="mis-export-actions no-print"
        :class="$props.class"
        role="group"
        :aria-label="t('Print')"
    >
        <button
            type="button"
            class="create-btn"
            :class="variant === 'outline' ? 'create-btn-outline' : 'create-btn-ghost'"
            @click="printPage"
        >
            <Printer />
            {{ t('Print') }}
        </button>
        <button
            type="button"
            class="create-btn"
            :class="variant === 'outline' ? 'create-btn-outline' : 'create-btn-ghost'"
            @click="exportExcel(filename)"
        >
            <FileSpreadsheet />
            {{ t('Export Excel') }}
        </button>
    </div>
</template>
