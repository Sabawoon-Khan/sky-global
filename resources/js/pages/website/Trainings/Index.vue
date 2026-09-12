<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import PageHero from '@/components/website/PageHero.vue';
import SeoHead from '@/components/website/SeoHead.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteSeo, WebsiteTraining } from '@/types/website';

defineProps<{
    seo?: WebsiteSeo | null;
    title?: string | null;
    lead?: string | null;
    trainings: WebsiteTraining[];
}>();

const { t } = useTranslations();
</script>

<template>
    <div>
        <SeoHead
            :title="seo?.title || title || t('website.nav.trainings')"
            :description="seo?.description || lead"
            :image="seo?.image"
        />
        <PageHero
            :eyebrow="t('website.nav.trainings')"
            :title="title || t('website.trainings.title')"
            :lead="lead"
        />

        <section class="ws-section">
            <div class="ws-container ws-card-grid-3">
                <Link
                    v-for="training in trainings"
                    :key="training.slug"
                    :href="`/trainings/${training.slug}`"
                    class="ws-card"
                >
                    <div class="ws-portfolio-media">
                        <img
                            v-if="training.image"
                            :src="training.image"
                            :alt="training.title"
                        />
                    </div>
                    <div class="ws-card-body">
                        <h2>{{ training.title }}</h2>
                        <p class="line-clamp-3">{{ training.summary }}</p>
                        <span class="ws-card-link">
                            {{ t('website.cta.learnMore') }}
                            <ArrowRight :size="14" />
                        </span>
                    </div>
                </Link>
            </div>
        </section>
    </div>
</template>
