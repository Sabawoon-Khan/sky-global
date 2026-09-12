<script setup lang="ts">
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
    sections?: { title: string; body: string }[];
}>();

const { t } = useTranslations();
</script>

<template>
    <div>
        <SeoHead
            :title="seo?.title || title || t('website.nav.whatWeDo')"
            :description="seo?.description || lead"
            :image="seo?.image"
        />
        <PageHero
            :eyebrow="t('website.nav.whatWeDo')"
            :title="title || t('website.nav.whatWeDo')"
            :lead="lead"
            image="/images/website/ops-atmosphere.jpg"
        />

        <section class="ws-section">
            <div class="ws-container" style="max-width: 48rem">
                <article
                    v-if="body || paragraphs?.length"
                    class="ws-prose"
                    style="white-space: pre-line"
                >
                    <template v-if="paragraphs?.length">
                        <p v-for="(p, i) in paragraphs" :key="i">{{ p }}</p>
                    </template>
                    <template v-else>
                        {{ body }}
                    </template>
                </article>

                <div
                    v-if="sections?.length"
                    class="ws-aside-stack"
                    style="margin-top: 2.5rem"
                >
                    <div
                        v-for="(section, index) in sections"
                        :key="`${section.title}-${index}`"
                        class="ws-panel"
                    >
                        <h2 class="ws-title" style="font-size: 1.35rem">
                            {{ section.title }}
                        </h2>
                        <p class="ws-prose" style="margin-top: 0.75rem; white-space: pre-line">
                            {{ section.body }}
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
