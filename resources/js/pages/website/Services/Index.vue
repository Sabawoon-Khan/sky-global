<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    Phone,
    Shield,
    Target,
} from '@lucide/vue';
import PageHero from '@/components/website/PageHero.vue';
import SeoHead from '@/components/website/SeoHead.vue';
import { useTranslations } from '@/composables/useTranslations';
import type {
    WebsiteContact,
    WebsiteSeo,
    WebsiteService,
    WebsiteStat,
} from '@/types/website';

defineProps<{
    seo?: WebsiteSeo | null;
    title?: string | null;
    lead?: string | null;
    services: WebsiteService[];
    stats?: WebsiteStat[];
    trustFeatures?: Array<{ title: string; body: string }>;
    contact?: WebsiteContact | null;
}>();

const { t } = useTranslations();
</script>

<template>
    <div>
        <SeoHead
            :title="seo?.title || title || t('website.nav.services')"
            :description="seo?.description || lead"
            :image="seo?.image"
        />

        <PageHero
            :eyebrow="t('website.nav.services')"
            :title="title || t('website.home.servicesTitle')"
            :lead="lead"
            image="/images/website/services/manned-guarding.jpg"
        >
            <div class="ws-hero-actions">
                        <Link href="/contact" class="ws-btn ws-btn-primary">
                            {{ t('website.cta.contactUs') }}
                            <ArrowRight :size="15" />
                        </Link>
                        <a
                            v-if="contact?.phone"
                            :href="`tel:${contact.phone_raw || contact.phone}`"
                            class="ws-btn ws-btn-ghost-light"
                        >
                            <Phone :size="15" />
                            {{ contact.phone }}
                        </a>
            </div>
        </PageHero>

        <section v-if="stats?.length" class="ws-trust-strip">
            <div class="ws-container ws-trust-strip-inner">
                <div class="ws-trust-copy">
                    <p class="ws-trust-label">
                        {{ t('website.services.trustedBy') }}
                    </p>
                    <h2>{{ t('website.services.trustedTitle') }}</h2>
                </div>
                <div class="ws-trust-metrics">
                    <article v-for="(stat, index) in stats" :key="`${stat.label}-${index}`">
                        <strong>{{ stat.value }}</strong>
                        <span>{{ stat.label }}</span>
                    </article>
                </div>
            </div>
        </section>

        <section class="ws-section">
            <div class="ws-container">
                <div class="ws-heading ws-heading-center">
                    <span class="ws-kicker">{{ t('website.nav.services') }}</span>
                    <h2 class="ws-title">{{ t('website.services.gridTitle') }}</h2>
                    <p class="ws-lead">{{ t('website.services.gridLead') }}</p>
                </div>

                <div class="ws-service-tiles">
                    <Link
                        v-for="(service, index) in services"
                        :key="service.slug"
                        :href="`/services/${service.slug}`"
                        class="ws-service-tile"
                    >
                        <div class="ws-service-tile-media">
                            <img
                                v-if="service.image"
                                :src="service.image"
                                :alt="service.title"
                            />
                            <div class="ws-service-tile-veil" />
                            <span class="ws-service-tile-index">0{{ index + 1 }}</span>
                        </div>
                        <div class="ws-service-tile-body">
                            <h3>{{ service.title }}</h3>
                            <p>{{ service.summary }}</p>
                            <span class="ws-card-link">
                                {{ t('website.cta.learnMore') }}
                                <ArrowRight :size="14" />
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <section class="ws-section ws-section-soft">
            <div class="ws-container">
                <div class="ws-partner-intro">
                    <div>
                        <span class="ws-kicker">{{ t('website.services.partnerKicker') }}</span>
                        <h2 class="ws-title">{{ t('website.services.partnerTitle') }}</h2>
                    </div>
                    <p class="ws-lead">{{ t('website.services.partnerLead') }}</p>
                </div>

                <div class="ws-feature-grid">
                    <article
                        v-for="(feature, index) in trustFeatures || []"
                        :key="`${feature.title}-${index}`"
                        class="ws-feature-card"
                    >
                        <span class="ws-feature-icon" aria-hidden="true">
                            <Shield v-if="index % 3 === 0" :size="22" />
                            <BadgeCheck v-else-if="index % 3 === 1" :size="22" />
                            <Target v-else :size="22" />
                        </span>
                        <h3>{{ feature.title }}</h3>
                        <p>{{ feature.body }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ws-split-cta">
            <div class="ws-container ws-split-cta-inner">
                <div>
                    <span class="ws-kicker">{{ t('website.services.customKicker') }}</span>
                    <h2>{{ t('website.services.customTitle') }}</h2>
                    <p>{{ t('website.services.customLead') }}</p>
                </div>
                <div class="ws-split-cta-actions">
                    <Link href="/contact" class="ws-btn ws-btn-light">
                        {{ t('website.services.letsTalk') }}
                        <ArrowRight :size="15" />
                    </Link>
                    <Link href="/projects" class="ws-btn ws-btn-ghost-light">
                        {{ t('website.cta.viewAll') }}
                        {{ t('website.nav.projects') }}
                    </Link>
                </div>
            </div>
        </section>

        <section class="ws-section">
            <div class="ws-container ws-closing">
                <div>
                    <span class="ws-kicker">{{ t('website.services.closingKicker') }}</span>
                    <h2 class="ws-title">{{ t('website.services.closingTitle') }}</h2>
                    <div class="ws-prose" style="margin-top: 1.1rem">
                        <p>{{ t('website.services.closingBody1') }}</p>
                        <p>{{ t('website.services.closingBody2') }}</p>
                    </div>
                </div>
                <aside class="ws-closing-card">
                    <h3>{{ t('website.services.askTitle') }}</h3>
                    <p>{{ t('website.services.askLead') }}</p>
                    <Link href="/contact" class="ws-btn ws-btn-primary">
                        {{ t('website.cta.getInTouch') }}
                        <ArrowRight :size="15" />
                    </Link>
                    <a
                        v-if="contact?.email"
                        class="ws-text-link"
                        :href="`mailto:${contact.email}`"
                    >
                        {{ contact.email }}
                    </a>
                </aside>
            </div>
        </section>
    </div>
</template>
