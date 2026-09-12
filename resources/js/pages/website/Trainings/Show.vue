<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PageHero from '@/components/website/PageHero.vue';
import SeoHead from '@/components/website/SeoHead.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteSeo, WebsiteTraining } from '@/types/website';

defineProps<{
    seo?: WebsiteSeo | null;
    training: WebsiteTraining;
}>();

const { t } = useTranslations();
</script>

<template>
    <div>
        <SeoHead
            :title="seo?.title || training.title"
            :description="seo?.description || training.summary"
            :image="seo?.image || training.image"
        />
        <PageHero
            :eyebrow="t('website.nav.trainings')"
            :title="training.title"
            :lead="training.summary"
            :image="training.image"
        />

        <section class="ws-section">
            <div class="ws-container ws-detail">
                <article class="ws-prose" style="white-space: pre-line">
                    {{ training.body || training.summary }}
                </article>
                <aside class="ws-aside-stack">
                    <div v-if="training.image" class="ws-detail-media">
                        <img :src="training.image" :alt="training.title" />
                    </div>
                    <div class="ws-panel-solid">
                        <h2 class="ws-title" style="font-size: 1.25rem">
                            {{ t('website.cta.contactUs') }}
                        </h2>
                        <p class="ws-prose" style="margin-top: 0.65rem; font-size: 0.92rem">
                            {{ t('website.trainings.ctaLead') }}
                        </p>
                        <Link href="/contact" class="ws-btn ws-btn-primary" style="margin-top: 1.25rem">
                            {{ t('website.cta.getInTouch') }}
                        </Link>
                        <Link
                            href="/trainings"
                            class="ws-btn ws-btn-outline"
                            style="margin-top: 0.65rem; width: 100%"
                        >
                            {{ t('website.cta.backToTrainings') }}
                        </Link>
                    </div>
                </aside>
            </div>
        </section>
    </div>
</template>
