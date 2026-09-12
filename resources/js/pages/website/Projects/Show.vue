<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PageHero from '@/components/website/PageHero.vue';
import SeoHead from '@/components/website/SeoHead.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteProject, WebsiteSeo } from '@/types/website';

defineProps<{
    seo?: WebsiteSeo | null;
    project: WebsiteProject;
}>();

const { t } = useTranslations();
</script>

<template>
    <div>
        <SeoHead
            :title="seo?.title || project.title"
            :description="seo?.description || project.summary"
            :image="seo?.image || project.image"
        />
        <PageHero
            :eyebrow="t('website.nav.projects')"
            :title="project.title"
            :lead="project.summary"
            :image="project.image"
        >
            <div class="ws-filter-row" style="margin-bottom: 0">
                <span v-if="project.status" class="ws-chip is-active" style="text-transform: capitalize">
                    {{ project.status }}
                </span>
                <span v-if="project.date" class="ws-chip">
                    {{ project.date }}
                </span>
            </div>
        </PageHero>

        <section class="ws-section">
            <div class="ws-container ws-detail">
                <article class="ws-prose" style="white-space: pre-line">
                    {{ project.body || project.summary }}
                </article>
                <aside class="ws-aside-stack">
                    <div v-if="project.image" class="ws-detail-media">
                        <img :src="project.image" :alt="project.title" />
                    </div>
                    <Link href="/projects" class="ws-btn ws-btn-outline">
                        {{ t('website.cta.backToProjects') }}
                    </Link>
                </aside>
            </div>
        </section>
    </div>
</template>
