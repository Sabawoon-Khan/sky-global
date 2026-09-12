<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        value: number;
        size?: number;
        color?: string;
        trackColor?: string;
    }>(),
    {
        size: 64,
        color: '#22c55e',
        trackColor: 'rgba(255,255,255,0.12)',
    },
);

const clamped = computed(() => Math.min(100, Math.max(0, props.value)));
const radius = 28;
const circumference = Math.PI * radius;
const offset = computed(
    () => circumference - (clamped.value / 100) * circumference,
);
</script>

<template>
    <svg
        :width="size"
        :height="size * 0.62"
        :viewBox="`0 0 64 40`"
        aria-hidden="true"
    >
        <path
            d="M 4 36 A 28 28 0 0 1 60 36"
            fill="none"
            :stroke="trackColor"
            stroke-width="6"
            stroke-linecap="round"
        />
        <path
            d="M 4 36 A 28 28 0 0 1 60 36"
            fill="none"
            :stroke="color"
            stroke-width="6"
            stroke-linecap="round"
            :stroke-dasharray="circumference"
            :stroke-dashoffset="offset"
            class="transition-[stroke-dashoffset] duration-700 ease-out"
        />
    </svg>
</template>
