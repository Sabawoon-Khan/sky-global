<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { onMounted } from 'vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import { Button } from '@/components/ui/button';
import MisExportActions from '@/components/mis/MisExportActions.vue';
import { useMisPage } from '@/composables/useMisPage';
import { formatDate } from '@/lib/format';

const props = defineProps<{
    guard: {
        id: number;
        name: string;
        father_name: string;
        grandfather_name?: string | null;
        tazkira_number?: string | null;
        id_card_number?: string | null;
        start_date?: string | null;
        end_date?: string | null;
        batch_number: string;
        training_path?: string | null;
        certificate_number?: string | null;
        certificate_issued_at?: string | null;
    };
    company: {
        name: string;
        name_fa: string;
        address: string;
        phone: string;
        president: string;
        president_title: string;
    };
}>();

const { t } = useMisPage();

const pathLabel = (): string =>
    props.guard.training_path === 'ministry'
        ? t('Public Protection Deputy')
        : t('Company training');

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('autoprint') === '1') {
        window.setTimeout(() => window.print(), 400);
    }
});
</script>

<template>
    <Head :title="guard.certificate_number ?? t('Certificate of completion')" />

    <div class="sheet-page">
        <div class="sheet-toolbar no-print">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/training/guards/${guard.id}`">
                    <ArrowLeft class="size-4" />
                    {{ t('Back') }}
                </Link>
            </Button>
            <MisExportActions variant="outline" />
        </div>

        <article class="cert-document" data-export-root>
            <div class="cert-inner">
                <header class="cert-header">
                    <AppLogoImage class="cert-logo" />
                    <div>
                        <h1>{{ company.name }}</h1>
                        <p class="cert-fa">{{ company.name_fa }}</p>
                    </div>
                </header>

                <p class="cert-kicker">{{ t('Certificate of completion') }}</p>
                <h2 class="cert-title">تصدیق‌نامه تکمیل کورس</h2>
                <p class="cert-subtitle">Certificate of Completion</p>

                <p class="cert-body">
                    This is to certify that
                </p>
                <p class="cert-name">{{ guard.name }}</p>
                <p class="cert-meta">
                    {{ t("Father's name") }}: {{ guard.father_name }}
                    <span v-if="guard.grandfather_name">
                        · {{ t("Grandfather's name") }}:
                        {{ guard.grandfather_name }}
                    </span>
                </p>
                <p class="cert-text">
                    has successfully completed security guard training
                    ({{ pathLabel() }}) in batch
                    <strong>{{ guard.batch_number }}</strong>
                    from {{ formatDate(guard.start_date) }}
                    to {{ formatDate(guard.end_date) }}.
                </p>

                <dl class="cert-ids">
                    <div>
                        <dt>{{ t('Certificate number') }}</dt>
                        <dd>{{ guard.certificate_number }}</dd>
                    </div>
                    <div>
                        <dt>{{ t('Tazkira number') }}</dt>
                        <dd>{{ guard.tazkira_number || '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ t('ID card number') }}</dt>
                        <dd>{{ guard.id_card_number || '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ t('Issue date') }}</dt>
                        <dd>{{ formatDate(guard.certificate_issued_at) }}</dd>
                    </div>
                </dl>

                <footer class="cert-footer">
                    <div>
                        <span class="cert-line" />
                        <strong>{{ company.president }}</strong>
                        <small>{{ company.president_title }}</small>
                    </div>
                    <div>
                        <span class="cert-line" />
                        <strong>{{ t('Training') }}</strong>
                        <small>{{ t('Education and Training') }}</small>
                    </div>
                </footer>
            </div>
        </article>
    </div>
</template>

<style scoped>
.sheet-page {
    min-height: 100vh;
    background: #e8e4d8;
    padding: 1.5rem;
}

.sheet-toolbar {
    display: flex;
    justify-content: space-between;
    max-width: 280mm;
    margin: 0 auto 1rem;
}

.cert-document {
    max-width: 280mm;
    margin: 0 auto;
    background: #fff;
    border: 10px solid #1e3a5f;
    box-shadow: 0 16px 40px rgb(0 0 0 / 0.12);
}

.cert-inner {
    margin: 8px;
    border: 2px solid #c4a35a;
    padding: 28px 40px 32px;
    min-height: 180mm;
}

.cert-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
}

.cert-logo {
    width: 72px;
    height: 72px;
}

.cert-header h1 {
    margin: 0;
    font-size: 1.15rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.cert-fa {
    margin: 4px 0 0;
    font-size: 1rem;
    direction: rtl;
}

.cert-kicker {
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.28em;
    font-size: 0.75rem;
    color: #1e3a5f;
    margin: 8px 0 0;
}

.cert-title {
    text-align: center;
    font-size: 2rem;
    margin: 8px 0 0;
    color: #1e3a5f;
}

.cert-subtitle {
    text-align: center;
    font-size: 1.35rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin: 4px 0 24px;
    color: #c4a35a;
}

.cert-body,
.cert-text,
.cert-meta {
    text-align: center;
    margin: 0 auto;
    max-width: 720px;
}

.cert-name {
    text-align: center;
    font-size: 2.1rem;
    font-weight: 700;
    margin: 10px 0;
    color: #1e3a5f;
}

.cert-text {
    margin-top: 12px;
    line-height: 1.7;
}

.cert-ids {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin: 28px 0 36px;
}

.cert-ids div {
    border-top: 1px solid #d6d3c4;
    padding-top: 8px;
}

.cert-ids dt {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b7280;
}

.cert-ids dd {
    margin: 4px 0 0;
    font-weight: 600;
}

.cert-footer {
    display: flex;
    justify-content: space-between;
    gap: 40px;
    padding: 0 24px;
}

.cert-footer div {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 180px;
}

.cert-line {
    display: block;
    width: 180px;
    border-top: 1px solid #1e3a5f;
    margin-bottom: 8px;
}

.cert-footer small {
    color: #6b7280;
}

@media print {
    @page {
        size: A4 landscape;
        margin: 8mm;
    }

    .sheet-page {
        background: #fff;
        padding: 0;
    }

    .no-print {
        display: none !important;
    }

    .cert-document {
        max-width: none;
        box-shadow: none;
    }
}
</style>
