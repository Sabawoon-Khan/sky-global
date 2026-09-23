<script setup lang="ts">
import { BarChart3, DollarSign, FileText } from '@lucide/vue';
import { computed } from 'vue';
import MisTabs from '@/components/MisTabs.vue';
import { useMisPage } from '@/composables/useMisPage';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps<{
    active: 'bidding' | 'finance' | 'reports';
}>();

const { t, can } = useMisPage();
const { canAny } = usePermissions();

const catalogPermissions = [
    'projects.view',
    'bidding.view',
    'finance.view',
    'hr.view',
    'training.view',
    'inventory.view',
    'archive.view',
];

const tabs = computed(() => {
    const items = [];

    if (can('bidding.view')) {
        items.push({
            id: 'bidding',
            label: t('Bidding'),
            href: '/analytics/bidding',
            icon: BarChart3,
        });
    }

    if (can('finance.view')) {
        items.push({
            id: 'finance',
            label: t('Finance'),
            href: '/analytics/finance',
            icon: DollarSign,
        });
    }

    if (canAny(catalogPermissions)) {
        items.push({
            id: 'reports',
            label: t('Reports'),
            href: '/analytics/reports',
            icon: FileText,
        });
    }

    return items;
});

const activeTab = computed({
    get: () => props.active,
    set: () => {},
});
</script>

<template>
    <div v-if="tabs.length > 1" class="mt-2">
        <MisTabs v-model="activeTab" nowrap :tabs="tabs" />
    </div>
</template>
