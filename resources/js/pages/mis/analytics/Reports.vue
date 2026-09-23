<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Printer } from '@lucide/vue';
import AnalyticsSubnav from '@/components/mis/AnalyticsSubnav.vue';
import MisEmptyState from '@/components/mis/MisEmptyState.vue';
import SystemReportContent, {
    type SystemReportSection,
} from '@/components/mis/SystemReportContent.vue';
import { Button } from '@/components/ui/button';
import { V2Hero, V2ListPage } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';

const props = defineProps<{
    year: number;
    year_label: string;
    years: number[];
    sections: SystemReportSection[];
    generated_on: string;
    company: { name: string };
}>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Analytics', href: '/analytics/bidding' },
            { title: 'Reports', href: '/analytics/reports' },
        ],
    },
});

const hasSections = computed(() => props.sections.length > 0);

const printAllHref = computed(
    () => `/analytics/reports/print?year=${props.year}&autoprint=1`,
);

function onYearChange(event: Event): void {
    const year = (event.target as HTMLSelectElement).value;

    router.get(
        '/analytics/reports',
        { year },
        { preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head :title="t('reports.system.title')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png" priority>
            <template #eyebrow>{{ t('Analytics') }}</template>
            <template #title>{{ t('reports.system.title') }}</template>
            <template #description>
                {{ t('reports.system.lead') }}
                · {{ company.name }}
                · {{ generated_on }}
            </template>
            <template #side>
                <div class="detail-actions">
                    <label class="year-filter">
                        <span>{{ t('Finance year') }}</span>
                        <select
                            class="year-select"
                            :value="String(year)"
                            @change="onYearChange"
                        >
                            <option
                                v-for="y in years"
                                :key="y"
                                :value="String(y)"
                            >
                                {{ y }}
                            </option>
                        </select>
                    </label>
                    <Button
                        v-if="hasSections"
                        variant="outline"
                        as-child
                    >
                        <a :href="printAllHref" target="_blank" rel="noopener">
                            <Printer class="size-4" />
                            {{ t('reports.print_all') }}
                        </a>
                    </Button>
                </div>
            </template>
        </V2Hero>

        <AnalyticsSubnav active="reports" />

        <MisEmptyState
            v-if="!hasSections"
            class="mt-6"
            :title="t('reports.empty.title')"
            :description="t('reports.empty.description')"
        />

        <div v-else class="mt-6">
            <SystemReportContent
                :sections="sections"
                :year="year"
            />
        </div>
    </V2ListPage>
</template>

<style scoped>
.detail-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
}

.year-filter {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.year-select {
    border-radius: 0.375rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--background));
    padding: 0.35rem 0.5rem;
    font-size: 0.875rem;
}
</style>
