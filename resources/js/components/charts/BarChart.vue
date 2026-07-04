<script setup lang="ts">
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Title,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { formatNumber } from '@/lib/format';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps<{
    labels: string[];
    datasets: Array<{
        label: string;
        data: number[];
        backgroundColor?: string;
    }>;
    height?: number;
}>();

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.datasets.map((dataset, index) => ({
        ...dataset,
        backgroundColor:
            dataset.backgroundColor ??
            [
                'rgba(59, 130, 246, 0.7)',
                'rgba(34, 197, 94, 0.7)',
                'rgba(249, 115, 22, 0.7)',
                'rgba(168, 85, 247, 0.7)',
            ][index % 4],
        borderRadius: 4,
    })),
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 1100,
        easing: 'easeOutQuart' as const,
    },
    plugins: {
        legend: { position: 'bottom' as const },
        tooltip: {
            callbacks: {
                label: (context) =>
                    `${context.dataset.label}: ${formatNumber(context.parsed.y ?? 0)}`,
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                precision: 0,
                callback: (value) => formatNumber(Number(value)),
            },
        },
    },
};
</script>

<template>
    <div :style="{ height: `${height ?? 280}px` }">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
