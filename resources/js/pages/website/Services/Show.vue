<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PageHero from '@/components/website/PageHero.vue';
import SeoHead from '@/components/website/SeoHead.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteSeo, WebsiteService } from '@/types/website';

defineProps<{
    seo?: WebsiteSeo | null;
    service: WebsiteService;
}>();

const { t } = useTranslations();
</script>

<template>
    <div>
        <SeoHead
            :title="seo?.title || service.title"
            :description="seo?.description || service.summary"
            :image="seo?.image || service.image"
        />
        <PageHero
            :eyebrow="t('website.nav.services')"
            :title="service.title"
            :lead="service.summary"
            :image="service.image"
        />

        <section class="ws-section">
            <div class="ws-container ws-detail">
                <article>
                    <div v-if="service.body" class="ws-prose" style="white-space: pre-line">
                        {{ service.body }}
                    </div>
                    <ul
                        v-if="service.bullets?.length"
                        class="ws-checklist ws-panel"
                        style="margin-top: 2rem"
                    >
                        <li
                            v-for="(bullet, index) in service.bullets"
                            :key="index"
                        >
                            {{ bullet }}
                        </li>
                    </ul>
                </article>
                <aside class="ws-aside-stack">
                    <div v-if="service.image" class="ws-detail-media">
                        <img :src="service.image" :alt="service.title" />
                    </div>
                    <div class="ws-panel-navy">
                        <h2 style="margin: 0; font-size: 1.25rem">{{ t('website.cta.contactUs') }}</h2>
                        <p style="margin: 0.65rem 0 0; font-size: 0.92rem; line-height: 1.65">
                            {{ t('website.services.ctaLead') }}
                        </p>
                        <Link href="/contact" class="ws-btn ws-btn-light" style="margin-top: 1.25rem">
                            {{ t('website.cta.getInTouch') }}
                        </Link>
                    </div>
                </aside>
            </div>
        </section>
    </div>
</template>
