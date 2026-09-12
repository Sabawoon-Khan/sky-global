<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PageHero from '@/components/website/PageHero.vue';
import SeoHead from '@/components/website/SeoHead.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteProject, WebsiteSeo } from '@/types/website';

const props = defineProps<{
    seo?: WebsiteSeo | null;
    title?: string | null;
    lead?: string | null;
    filter?: 'all' | 'ongoing' | 'completed' | string | null;
    projects: WebsiteProject[];
}>();

const { t } = useTranslations();

const filters = [
    { href: '/projects', key: 'all', labelKey: 'website.projects.filterAll' },
    { href: '/projects/ongoing', key: 'ongoing', labelKey: 'website.projects.filterOngoing' },
    {
        href: '/projects/completed',
        key: 'completed',
        labelKey: 'website.projects.filterCompleted',
    },
] as const;

function isActive(key: string): boolean {
    const current = props.filter || 'all';
    return current === key;
}

function projectDate(project: WebsiteProject): string | null {
    return project.posted_at || project.start_date || project.date || null;
}
</script>

<template>
    <div>
        <SeoHead
            :title="seo?.title || title || t('website.nav.projects')"
            :description="seo?.description || lead"
            :image="seo?.image"
        />
        <PageHero
            :eyebrow="t('website.nav.projects')"
            :title="title || t('website.home.projectsTitle')"
            :lead="lead"
        />

        <section class="ws-section">
            <div class="ws-container">
                <div class="ws-filter-row">
                    <Link
                        v-for="item in filters"
                        :key="item.key"
                        :href="item.href"
                        class="ws-chip"
                        :class="{ 'is-active': isActive(item.key) }"
                    >
                        {{ t(item.labelKey) }}
                    </Link>
                </div>

                <div class="ws-card-grid-3">
                    <Link
                        v-for="project in projects"
                        :key="project.slug"
                        :href="`/projects/${project.slug}`"
                        class="ws-card"
                    >
                        <div class="ws-portfolio-media">
                            <img
                                v-if="project.image"
                                :src="project.image"
                                :alt="project.title"
                            />
                        </div>
                        <div class="ws-card-body">
                            <div
                                style="
                                    display: flex;
                                    align-items: center;
                                    justify-content: space-between;
                                    gap: 0.75rem;
                                "
                            >
                                <em v-if="project.status" class="ws-meta">{{ project.status }}</em>
                                <em v-if="projectDate(project)" class="ws-meta">{{
                                    projectDate(project)
                                }}</em>
                            </div>
                            <h2 style="margin-top: 0.45rem">{{ project.title }}</h2>
                            <p class="line-clamp-3">{{ project.summary }}</p>
                        </div>
                    </Link>
                </div>

                <p v-if="!projects.length" class="ws-empty" style="margin-top: 2.5rem">
                    {{ t('website.projects.empty') }}
                </p>
            </div>
        </section>
    </div>
</template>
