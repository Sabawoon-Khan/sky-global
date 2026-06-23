<script setup lang="ts">
import {
    ArcElement,
    Chart as ChartJS,
    Legend,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps<{
    labels: string[];
    data: number[];
    colors?: string[];
    height?: number;
}>();

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            data: props.data,
            backgroundColor: props.colors ?? [
                'rgba(59, 130, 246, 0.8)',
                'rgba(34, 197, 94, 0.8)',
                'rgba(249, 115, 22, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(168, 85, 247, 0.8)',
                'rgba(107, 114, 128, 0.8)',
            ],
            borderWidth: 0,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' as const },
    },
};
</script>

<template>
    <div :style="{ height: `${height ?? 260}px` }">
        <Doughnut :data="chartData" :options="chartOptions" />
    </div>
</template>
