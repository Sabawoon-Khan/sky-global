<script setup lang="ts">
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import { Button } from '@/components/ui/button';
import { V2FilterBar } from '@/components/v2';
import { useTranslations } from '@/composables/useTranslations';

const search = defineModel<string>('search', { default: '' });
const dateFrom = defineModel<string>('dateFrom', { default: '' });
const dateTo = defineModel<string>('dateTo', { default: '' });

withDefaults(
    defineProps<{
        searchPlaceholder?: string;
        showSearch?: boolean;
        showDates?: boolean;
        hasActive?: boolean;
    }>(),
    {
        searchPlaceholder: undefined,
        showSearch: true,
        showDates: true,
        hasActive: false,
    },
);

const emit = defineEmits<{
    apply: [];
    clear: [];
}>();

const { t } = useTranslations();
</script>

<template>
    <V2FilterBar>
        <div v-if="showSearch" class="filter-search">
            <MisSearchInput
                v-model="search"
                :placeholder="searchPlaceholder ?? t('Search')"
                @submit="emit('apply')"
                @clear="emit('apply')"
            />
        </div>
        <template v-if="showDates">
            <label class="filter-select">
                <span>{{ t('From') }}</span>
                <input v-model="dateFrom" type="date" @change="emit('apply')" />
            </label>
            <label class="filter-select">
                <span>{{ t('To') }}</span>
                <input v-model="dateTo" type="date" @change="emit('apply')" />
            </label>
        </template>
        <slot />
        <template v-if="hasActive || $slots.actions" #actions>
            <slot name="actions" />
            <Button
                v-if="hasActive"
                type="button"
                variant="ghost"
                class="h-9"
                @click="emit('clear')"
            >
                {{ t('Clear') }}
            </Button>
        </template>
        <template v-if="$slots.columns" #columns>
            <slot name="columns" />
        </template>
    </V2FilterBar>
</template>
