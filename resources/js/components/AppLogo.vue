<script setup lang="ts">
import AppLogoImage from '@/components/AppLogoImage.vue';
</script>

<template>
    <div class="flex min-w-0 flex-1 items-center gap-3 group-data-[collapsible=icon]:flex-none group-data-[collapsible=icon]:justify-center">
        <div
            class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white/10 ring-1 ring-white/20"
        >
            <AppLogoImage class="size-8" />
        </div>
        <div
            class="grid flex-1 text-start leading-tight group-data-[collapsible=icon]:hidden"
        >
            <span class="truncate text-base font-semibold text-white"
                >SunSky Global</span
            >
        </div>
    </div>
</template>
