<script setup lang="ts">
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import { formatNumber } from '@/lib/format';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
);

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
    datasets: props.datasets.map((dataset, index) => {
        const palette = [
            { border: '#1f4e5f', fill: 'rgba(31, 78, 95, 0.12)' },
            { border: '#b8956c', fill: 'rgba(184, 149, 108, 0.14)' },
            { border: '#0c1a2e', fill: 'rgba(12, 26, 46, 0.1)' },
            { border: '#3d5a80', fill: 'rgba(61, 90, 128, 0.12)' },
        ];
        const colors = palette[index % palette.length];

        return {
            ...dataset,
            borderColor: dataset.borderColor ?? colors.border,
            backgroundColor: dataset.backgroundColor ?? colors.fill,
            borderWidth: 2.5,
            tension: 0.35,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: '#fff',
            pointBorderWidth: 2,
            pointBorderColor: dataset.borderColor ?? colors.border,
            pointHoverBorderWidth: 2,
        };
    }),
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index' as const,
        intersect: false,
    },
    animation: {
        duration: 900,
        easing: 'easeOutQuart' as const,
    },
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: {
                usePointStyle: true,
                pointStyle: 'circle' as const,
                boxWidth: 8,
                padding: 16,
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
                label: (context: { dataset: { label?: string }; parsed: { y: number | null } }) =>
                    `${context.dataset.label}: ${formatNumber(context.parsed.y ?? 0)}`,
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { font: { size: 11 }, color: '#737373' },
            border: { display: false },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(0, 0, 0, 0.06)',
                drawBorder: false,
            },
            border: { display: false },
            ticks: {
                precision: 0,
                font: { size: 11 },
                color: '#737373',
                callback: (value: string | number) => formatNumber(Number(value)),
            },
        },
    },
};
</script>

<template>
    <div :style="{ height: `${height ?? 280}px` }">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
