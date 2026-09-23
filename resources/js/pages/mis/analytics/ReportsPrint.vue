<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import SystemReportContent, {
    type SystemReportSection,
} from '@/components/mis/SystemReportContent.vue';
import { Button } from '@/components/ui/button';
import MisExportActions from '@/components/mis/MisExportActions.vue';
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

const isSingleModule = computed(() => props.sections.length === 1);

const documentTitle = computed(() =>
    isSingleModule.value && props.sections[0]
        ? `${t(props.sections[0].module_label_key)} — ${t('reports.system.title')}`
        : t('reports.system.title'),
);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('autoprint') === '1') {
        window.setTimeout(() => window.print(), 400);
    }
});
</script>

<template>
    <Head :title="documentTitle" />

    <div class="sheet-page">
        <div class="sheet-toolbar no-print">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/analytics/reports">
                    <ArrowLeft class="size-4" />
                    {{ t('reports.system.title') }}
                </Link>
            </Button>
            <MisExportActions variant="outline" filename="system-report" />
        </div>

        <article class="sheet-document" data-export-root>
            <header class="sheet-header">
                <AppLogoImage class="sheet-logo sheet-logo--left" />
                <div class="sheet-header-center">
                    <h1 class="sheet-title">{{ documentTitle }}</h1>
                    <p class="sheet-subtitle">{{ company.name }}</p>
                </div>
                <AppLogoImage class="sheet-logo sheet-logo--right" />
            </header>

            <div class="sheet-meta">
                <p>
                    <strong>{{ t('Date') }}:</strong>
                    {{ generated_on }}
                </p>
                <p>
                    <strong>{{ t('Finance year') }}:</strong>
                    {{ year_label }}
                </p>
                <p>
                    <strong>{{ t('Modules') }}:</strong>
                    {{ sections.length }}
                </p>
            </div>

            <SystemReportContent
                v-if="sections.length"
                :sections="sections"
                :year="year"
                printable
                :show-print-actions="false"
            />

            <p v-else class="sheet-empty">
                {{ t('reports.empty.description') }}
            </p>
        </article>
    </div>
</template>

<style scoped>
.sheet-page {
    min-height: 100vh;
    background: #e8e8e8;
    padding: 1rem;
    color: #111;
}

.sheet-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    max-width: 210mm;
    margin: 0 auto 1rem;
}

.sheet-document {
    max-width: 210mm;
    margin: 0 auto;
    background: #fff;
    padding: 12mm;
    box-shadow: 0 2px 12px rgb(0 0 0 / 12%);
}

.sheet-header {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 2px solid #111;
    padding-bottom: 0.75rem;
    margin-bottom: 0.75rem;
}

.sheet-header-center {
    text-align: center;
}

.sheet-title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.sheet-subtitle {
    margin: 0.15rem 0 0;
    font-size: 0.75rem;
    color: #333;
}

.sheet-logo {
    height: 2.5rem;
    width: auto;
    object-fit: contain;
}

.sheet-logo--right {
    justify-self: end;
}

.sheet-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem 2rem;
    font-size: 0.8rem;
    margin-bottom: 1rem;
}

.sheet-meta p {
    margin: 0;
}

.sheet-empty {
    font-size: 0.85rem;
    color: #555;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    .sheet-page {
        background: #fff;
        padding: 0;
    }

    .no-print {
        display: none !important;
    }

    .sheet-document {
        padding: 0;
        max-width: none;
        box-shadow: none;
    }
}
</style>
