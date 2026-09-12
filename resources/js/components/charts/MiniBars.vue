<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        data: number[];
        color?: string;
        width?: number;
        height?: number;
    }>(),
    {
        color: '#f97316',
        width: 72,
        height: 36,
    },
);

const bars = computed(() => {
    const values = props.data.length ? props.data : [0];
    const max = Math.max(...values, 1);

    return values.map((value) => Math.max(12, (value / max) * 100));
});
</script>

<template>
    <div
        class="flex items-end justify-end gap-[3px]"
        :style="{ width: `${width}px`, height: `${height}px` }"
        aria-hidden="true"
    >
        <span
            v-for="(bar, index) in bars"
            :key="index"
            class="w-[5px] rounded-full transition-all duration-500"
            :style="{
                height: `${bar}%`,
                background: color,
                opacity: 0.45 + (index / Math.max(bars.length - 1, 1)) * 0.55,
            }"
        />
    </div>
</template>
