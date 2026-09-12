<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        data: number[];
        color?: string;
        width?: number;
        height?: number;
        fill?: boolean;
    }>(),
    {
        color: '#22c55e',
        width: 88,
        height: 36,
        fill: true,
    },
);

const points = computed(() => {
    const values = props.data.length ? props.data : [0, 0];
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;
    const padY = 3;
    const w = props.width;
    const h = props.height;

    return values.map((value, index) => {
        const x =
            values.length === 1
                ? w / 2
                : (index / (values.length - 1)) * w;
        const y = h - padY - ((value - min) / range) * (h - padY * 2);

        return `${x},${y}`;
    });
});

const linePath = computed(() => {
    if (points.value.length === 0) return '';

    return `M ${points.value.join(' L ')}`;
});

const areaPath = computed(() => {
    if (points.value.length === 0) return '';

    const first = points.value[0].split(',')[0];
    const last = points.value[points.value.length - 1].split(',')[0];

    return `${linePath.value} L ${last},${props.height} L ${first},${props.height} Z`;
});
</script>

<template>
    <svg
        :width="width"
        :height="height"
        :viewBox="`0 0 ${width} ${height}`"
        class="overflow-visible"
        aria-hidden="true"
    >
        <defs>
            <linearGradient
                :id="`spark-fill-${color.replace('#', '')}`"
                x1="0"
                y1="0"
                x2="0"
                y2="1"
            >
                <stop :offset="'0%'" :stop-color="color" stop-opacity="0.35" />
                <stop :offset="'100%'" :stop-color="color" stop-opacity="0" />
            </linearGradient>
        </defs>
        <path
            v-if="fill"
            :d="areaPath"
            :fill="`url(#spark-fill-${color.replace('#', '')})`"
        />
        <path
            :d="linePath"
            fill="none"
            :stroke="color"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>
