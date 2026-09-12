<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PageHero from '@/components/website/PageHero.vue';
import SeoHead from '@/components/website/SeoHead.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteSeo } from '@/types/website';

defineProps<{
    seo?: WebsiteSeo | null;
    title?: string | null;
    lead?: string | null;
    body?: string | null;
    paragraphs?: string[];
    image?: string | null;
}>();

const { t } = useTranslations();
</script>

<template>
    <div>
        <SeoHead
            :title="seo?.title || title || t('website.nav.about')"
            :description="seo?.description || lead"
            :image="seo?.image || image"
        />
        <PageHero
            :eyebrow="t('website.nav.about')"
            :title="title || t('website.nav.about')"
            :lead="lead"
            :image="image || '/images/website/about.jpg'"
        />

        <section class="ws-section">
            <div class="ws-container ws-detail">
                <article class="ws-prose" style="white-space: pre-line">
                    <template v-if="paragraphs?.length">
                        <p v-for="(p, i) in paragraphs" :key="i">{{ p }}</p>
                    </template>
                    <template v-else>
                        {{ body }}
                    </template>
                </article>
                <aside class="ws-aside-stack">
                    <div class="ws-detail-media">
                        <img
                            :src="image || '/images/website/about.jpg'"
                            :alt="title || 'About'"
                            style="aspect-ratio: 4 / 5"
                        />
                    </div>
                    <div class="ws-panel-solid">
                        <h2 class="ws-title" style="font-size: 1.35rem">
                            {{ t('website.cta.contactUs') }}
                        </h2>
                        <p class="ws-prose" style="margin-top: 0.65rem; font-size: 0.92rem">
                            {{ t('website.about.contactBlurb') }}
                        </p>
                        <Link href="/contact" class="ws-btn ws-btn-primary" style="margin-top: 1.25rem">
                            {{ t('website.cta.getInTouch') }}
                        </Link>
                    </div>
                </aside>
            </div>
        </section>
    </div>
</template>
