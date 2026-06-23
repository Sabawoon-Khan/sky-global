<script setup lang="ts">
import {
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

const props = defineProps<{
    labels: string[];
    datasets: Array<{
        label: string;
        data: number[];
        borderColor?: string;
        backgroundColor?: string;
    }>;
    height?: number;
}>();

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.datasets.map((dataset, index) => ({
        ...dataset,
        borderColor:
            dataset.borderColor ??
            ['rgba(59, 130, 246, 1)', 'rgba(34, 197, 94, 1)', 'rgba(249, 115, 22, 1)'][index % 3],
        backgroundColor:
            dataset.backgroundColor ??
            ['rgba(59, 130, 246, 0.1)', 'rgba(34, 197, 94, 0.1)', 'rgba(249, 115, 22, 0.1)'][index % 3],
        tension: 0.3,
        fill: true,
    })),
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' as const },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { precision: 0 },
        },
    },
};
</script>

<template>
    <div :style="{ height: `${height ?? 280}px` }">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
