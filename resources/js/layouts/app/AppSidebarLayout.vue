<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import FlashToasts from '@/components/FlashToasts.vue';
import { Toaster } from '@/components/ui/sonner';
import { useLocale } from '@/composables/useLocale';
import type { BreadcrumbItem } from '@/types';
import { computed } from 'vue';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { dir, sidebarSide } = useLocale();

const toasterPosition = computed(() =>
    dir.value === 'rtl' ? 'top-left' : 'top-right',
);

const isSidebarRight = computed(() => sidebarSide.value === 'right');
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar :class="isSidebarRight ? 'order-2' : 'order-1'" />
        <AppContent
            variant="sidebar"
            :class="[
                'min-w-0 flex-1 overflow-x-hidden',
                isSidebarRight ? 'order-1' : 'order-2',
            ]"
        >
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="flex flex-1 flex-col space-y-6 px-6 pt-6 pb-12 md:px-8">
                <slot />
            </div>
        </AppContent>
        <Toaster :position="toasterPosition" :duration="5000" rich-colors />
        <FlashToasts />
    </AppShell>
</template>
