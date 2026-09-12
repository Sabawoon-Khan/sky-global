<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        value: number;
        max?: number;
        size?: number;
        color?: string;
        trackColor?: string;
        strokeWidth?: number;
    }>(),
    {
        max: 100,
        size: 52,
        color: '#ef4444',
        trackColor: 'rgba(255,255,255,0.1)',
        strokeWidth: 5,
    },
);

const radius = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const progress = computed(() => {
    const pct = Math.min(100, Math.max(0, (props.value / props.max) * 100));

    return circumference.value - (pct / 100) * circumference.value;
});
</script>

<template>
    <svg
        :width="size"
        :height="size"
        class="-rotate-90"
        aria-hidden="true"
    >
        <circle
            :cx="size / 2"
            :cy="size / 2"
            :r="radius"
            fill="none"
            :stroke="trackColor"
            :stroke-width="strokeWidth"
        />
        <circle
            :cx="size / 2"
            :cy="size / 2"
            :r="radius"
            fill="none"
            :stroke="color"
            :stroke-width="strokeWidth"
            stroke-linecap="round"
            :stroke-dasharray="circumference"
            :stroke-dashoffset="progress"
            class="transition-[stroke-dashoffset] duration-700 ease-out"
        />
    </svg>
</template>
