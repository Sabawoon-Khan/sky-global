<script setup lang="ts">
import { ref } from 'vue';
import PageHero from '@/components/website/PageHero.vue';
import SeoHead from '@/components/website/SeoHead.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteCertificate, WebsiteSeo } from '@/types/website';

defineProps<{
    seo?: WebsiteSeo | null;
    title?: string | null;
    lead?: string | null;
    certificates: WebsiteCertificate[];
}>();

const { t } = useTranslations();
const active = ref<WebsiteCertificate | null>(null);
</script>

<template>
    <div>
        <SeoHead
            :title="seo?.title || title || t('website.nav.certificates')"
            :description="seo?.description || lead"
            :image="seo?.image || certificates[0]?.src"
        />
        <PageHero
            :eyebrow="t('website.nav.certificates')"
            :title="title || t('website.certificates.title')"
            :lead="lead"
            compact
        />

        <section class="ws-section">
            <div class="ws-container ws-card-grid-3">
                <button
                    v-for="(cert, index) in certificates"
                    :key="`${cert.src}-${index}`"
                    type="button"
                    class="ws-card"
                    style="cursor: pointer; text-align: start; width: 100%; font: inherit"
                    @click="active = cert"
                >
                    <div
                        class="ws-portfolio-media"
                        style="height: auto; aspect-ratio: 4 / 3; padding: 0.85rem; background: var(--ws-fog)"
                    >
                        <img
                            :src="cert.src"
                            :alt="cert.alt || cert.title || `Certificate ${index + 1}`"
                            style="object-fit: contain"
                        />
                    </div>
                    <div v-if="cert.title" class="ws-card-body" style="padding-block: 0.95rem">
                        <h2 style="font-size: 0.95rem">{{ cert.title }}</h2>
                    </div>
                </button>
            </div>
        </section>

        <div
            v-if="active"
            class="ws-lightbox"
            @click.self="active = null"
        >
            <div class="ws-lightbox-panel">
                <button
                    type="button"
                    class="ws-lightbox-close"
                    @click="active = null"
                >
                    {{ t('website.cta.close') }}
                </button>
                <img
                    :src="active.src"
                    :alt="active.alt || active.title || 'Certificate'"
                    style="display: block; margin-inline: auto; max-height: 80vh; width: auto; object-fit: contain"
                />
            </div>
        </div>
    </div>
</template>
