<script setup lang="ts">
import type { AppVariant } from '@/types';
import LocaleSync from '@/components/LocaleSync.vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';

type Props = {
    variant?: AppVariant;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <LocaleSync>
        <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
            <slot />
        </div>
        <SidebarProvider v-else :default-open="isOpen">
            <slot />
        </SidebarProvider>
    </LocaleSync>
</template>
