<script setup lang="ts">
import {
    ArcElement,
    Chart as ChartJS,
    Legend,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { formatNumber } from '@/lib/format';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps<{
    labels: string[];
    data: number[];
    colors?: string[];
    height?: number;
    centerLabel?: string;
    centerValue?: string;
}>();

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            data: props.data,
            backgroundColor: props.colors ?? [
                '#0c1a2e',
                '#1f4e5f',
                '#b8956c',
                '#3d5a80',
                '#8b9bb4',
                '#8f2d3a',
            ],
            borderWidth: 3,
            borderColor: 'transparent',
            hoverOffset: 6,
            hoverBorderWidth: 0,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '68%',
    animation: {
        animateRotate: true,
        animateScale: true,
        duration: 1000,
        easing: 'easeOutQuart' as const,
    },
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: {
                usePointStyle: true,
                pointStyle: 'circle' as const,
                boxWidth: 8,
                padding: 14,
                font: { size: 12 },
            },
        },
        tooltip: {
            backgroundColor: 'rgba(10, 10, 10, 0.9)',
            titleFont: { size: 12, weight: 'bold' as const },
            bodyFont: { size: 12 },
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                label: (context: { label?: string; parsed: number | null }) =>
                    `${context.label}: ${formatNumber(context.parsed ?? 0)}`,
            },
        },
    },
};

const total = computed(() =>
    props.data.reduce((sum, value) => sum + (Number(value) || 0), 0),
);
</script>

<template>
    <div class="relative" :style="{ height: `${height ?? 260}px` }">
        <Doughnut :data="chartData" :options="chartOptions" />
        <div
            v-if="centerLabel || centerValue"
            class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center pb-8"
        >
            <p
                v-if="centerValue"
                class="text-xl font-bold tabular-nums tracking-tight"
            >
                {{ centerValue }}
            </p>
            <p
                v-else
                class="text-xl font-bold tabular-nums tracking-tight"
            >
                {{ formatNumber(total) }}
            </p>
            <p
                v-if="centerLabel"
                class="mt-0.5 text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
            >
                {{ centerLabel }}
            </p>
        </div>
    </div>
</template>
