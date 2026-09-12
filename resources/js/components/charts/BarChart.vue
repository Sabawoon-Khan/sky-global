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

const props = withDefaults(
    defineProps<{
        labels: string[];
        datasets: Array<{
            label: string;
            data: number[];
            backgroundColor?: string | string[];
        }>;
        height?: number;
        horizontal?: boolean;
        showLegend?: boolean;
    }>(),
    {
        showLegend: true,
    },
);

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.datasets.map((dataset, index) => ({
        ...dataset,
        backgroundColor:
            dataset.backgroundColor ??
            ['#0c1a2e', '#1f4e5f', '#b8956c', '#3d5a80'][index % 4],
        borderRadius: 6,
        borderSkipped: false,
        maxBarThickness: props.horizontal ? 28 : 42,
    })),
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: (props.horizontal ? 'y' : 'x') as 'x' | 'y',
    animation: {
        duration: 900,
        easing: 'easeOutQuart' as const,
    },
    plugins: {
        legend: {
            display: props.showLegend,
            position: 'bottom' as const,
            labels: {
                usePointStyle: true,
                pointStyle: 'rectRounded' as const,
                boxWidth: 10,
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
                label: (context: {
                    dataset: { label?: string };
                    parsed: { x: number | null; y: number | null };
                }) => {
                    const value = props.horizontal
                        ? (context.parsed.x ?? 0)
                        : (context.parsed.y ?? 0);

                    return `${context.dataset.label}: ${formatNumber(value)}`;
                },
            },
        },
    },
    scales: {
        x: {
            beginAtZero: true,
            grid: {
                display: props.horizontal,
                color: 'rgba(0, 0, 0, 0.06)',
                drawBorder: false,
            },
            border: { display: false },
            ticks: {
                precision: 0,
                font: { size: 11 },
                color: '#737373',
                callback: props.horizontal
                    ? (value: string | number) => formatNumber(Number(value))
                    : undefined,
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                display: !props.horizontal,
                color: 'rgba(0, 0, 0, 0.06)',
                drawBorder: false,
            },
            border: { display: false },
            ticks: {
                precision: 0,
                font: { size: 11 },
                color: '#737373',
                callback: !props.horizontal
                    ? (value: string | number) => formatNumber(Number(value))
                    : undefined,
            },
        },
    },
}));
</script>

<template>
    <div :style="{ height: `${height ?? 280}px` }">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
