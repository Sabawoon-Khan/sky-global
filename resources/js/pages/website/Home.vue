<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { onMounted, onUnmounted } from 'vue';
import SeoHead from '@/components/website/SeoHead.vue';
import { useTranslations } from '@/composables/useTranslations';
import type {
    WebsiteClientLogo,
    WebsiteContact,
    WebsiteProject,
    WebsiteSeo,
    WebsiteService,
    WebsiteStat,
    WebsiteTestimonial,
} from '@/types/website';

defineProps<{
    seo?: WebsiteSeo | null;
    hero?: {
        brand?: string | null;
        headline?: string | null;
        support?: string | null;
        image?: string | null;
    } | null;
    aboutTeaser?: {
        title?: string | null;
        body?: string | null;
        image?: string | null;
    } | null;
    services?: WebsiteService[];
    stats?: WebsiteStat[];
    statsTitle?: string | null;
    statsLead?: string | null;
    projects?: WebsiteProject[];
    testimonials?: WebsiteTestimonial[];
    clients?: {
        title?: string | null;
        blurb?: string | null;
        logos?: WebsiteClientLogo[];
    } | null;
    contact?: WebsiteContact | null;
}>();

const { t } = useTranslations();

let revealObserver: IntersectionObserver | null = null;

function observeReveals() {
    document
        .querySelectorAll('.ws-reveal:not(.is-visible)')
        .forEach((el) => revealObserver?.observe(el));
}

onMounted(() => {
    const shell = document.querySelector('.website-shell');
    shell?.classList.add('has-reveal-js');

    const reveal = (el: Element) => {
        el.classList.add('is-visible');
        revealObserver?.unobserve(el);
    };

    revealObserver = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    reveal(entry.target);
                }
            }
        },
        { threshold: 0.08, rootMargin: '0px 0px -8% 0px' },
    );

    observeReveals();

    // Show anything already in/near the viewport immediately
    document.querySelectorAll('.ws-reveal:not(.is-visible)').forEach((el) => {
        const top = el.getBoundingClientRect().top;
        if (top < window.innerHeight * 0.95) {
            reveal(el);
        }
    });

    // Hard fallback — never leave homepage sections invisible
    window.setTimeout(() => {
        document
            .querySelectorAll('.ws-reveal:not(.is-visible)')
            .forEach((el) => reveal(el));
    }, 1200);
});

onUnmounted(() => {
    document.querySelector('.website-shell')?.classList.remove('has-reveal-js');
    revealObserver?.disconnect();
    revealObserver = null;
});
</script>

<template>
    <div>
        <SeoHead
            :title="seo?.title || 'Sun Sky Global Security'"
            :description="seo?.description"
            :image="seo?.image || hero?.image || '/images/website/hero.jpg'"
        />

        <section id="top" class="ws-hero">
            <div class="ws-hero-backdrop" aria-hidden="true">
                <img
                    class="ws-hero-media"
                    :src="hero?.image || '/images/website/hero.jpg'"
                    alt="Sun Sky Global Security operations"
                />
                <div class="ws-hero-veil" />
                <div class="ws-hero-edge" />
            </div>

            <div class="ws-hero-stage">
                <div class="ws-hero-copy">
                    <span class="ws-hero-accent" aria-hidden="true" />
                    <p class="ws-hero-brand ws-rise">
                        {{ hero?.brand || 'Sun Sky Global Security' }}
                    </p>
                    <h1 class="ws-rise ws-rise-delay-1">
                        {{
                            hero?.headline ||
                            'Professional security for complex environments'
                        }}
                    </h1>
                    <p class="ws-hero-lead ws-rise ws-rise-delay-2">
                        {{
                            hero?.support ||
                            'Integrated guarding, mobile escort, risk advisory, and training across Afghanistan.'
                        }}
                    </p>
                    <div class="ws-hero-actions ws-rise ws-rise-delay-3">
                        <Link href="/services" class="ws-btn ws-btn-primary">
                            {{ t('website.cta.ourServices') }}
                            <ArrowRight :size="15" />
                        </Link>
                        <Link href="/contact" class="ws-btn ws-btn-ghost">
                            {{ t('website.cta.contactUs') }}
                        </Link>
                    </div>
                </div>
            </div>

            <div class="ws-hero-base" aria-hidden="true">
                <div class="ws-hero-base-line" />
                <div class="ws-hero-scroll">
                    <span>Scroll</span>
                </div>
            </div>
        </section>

        <section
            v-if="stats?.length"
            class="ws-stats ws-reveal"
            :aria-label="statsTitle || 'Key results'"
        >
            <div class="ws-container ws-stats-row">
                <article
                    v-for="(stat, index) in stats"
                    :key="`${stat.label}-${index}`"
                >
                    <strong>{{ stat.value }}</strong>
                    <span>{{ stat.label }}</span>
                </article>
            </div>
        </section>

        <section v-if="aboutTeaser" class="ws-section ws-reveal">
            <div class="ws-container ws-about">
                <div class="ws-about-media">
                    <img
                        :src="aboutTeaser.image || '/images/website/about.jpg'"
                        :alt="aboutTeaser.title || 'About Sun Sky'"
                    />
                    <div class="ws-about-frame" aria-hidden="true" />
                </div>
                <div>
                    <span class="ws-kicker">{{ t('website.nav.about') }}</span>
                    <h2 class="ws-title">{{ aboutTeaser.title || t('website.nav.about') }}</h2>
                    <div class="ws-prose" style="margin-top: 1.15rem; white-space: pre-line">
                        {{ aboutTeaser.body }}
                    </div>
                    <Link href="/about" class="ws-text-link">
                        {{ t('website.cta.learnMore') }}
                        <ArrowRight :size="15" />
                    </Link>
                </div>
            </div>
        </section>

        <section v-if="services?.length" class="ws-section ws-section-soft ws-reveal">
            <div class="ws-container">
                <div class="ws-heading-split">
                    <div>
                        <span class="ws-kicker">{{ t('website.nav.services') }}</span>
                        <h2 class="ws-title">{{ t('website.home.servicesTitle') }}</h2>
                    </div>
                    <Link href="/services" class="ws-btn ws-btn-outline">
                        {{ t('website.cta.viewAll') }}
                        <ArrowRight :size="15" />
                    </Link>
                </div>
                <div class="ws-card-grid-4">
                    <Link
                        v-for="(service, index) in services"
                        :key="service.slug"
                        :href="`/services/${service.slug}`"
                        class="ws-card"
                    >
                        <div class="ws-service-media">
                            <img
                                v-if="service.image"
                                :src="service.image"
                                :alt="service.title"
                            />
                            <span class="ws-service-index">0{{ index + 1 }}</span>
                        </div>
                        <div class="ws-card-body">
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

        <section v-if="projects?.length" class="ws-section ws-reveal">
            <div class="ws-container">
                <div class="ws-heading-split">
                    <div>
                        <span class="ws-kicker">{{ t('website.nav.projects') }}</span>
                        <h2 class="ws-title">{{ t('website.home.projectsTitle') }}</h2>
                    </div>
                    <Link href="/projects" class="ws-btn ws-btn-outline">
                        {{ t('website.cta.viewAll') }}
                        <ArrowRight :size="15" />
                    </Link>
                </div>
                <div class="ws-card-grid-4">
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
                            <em v-if="project.status" class="ws-meta">{{ project.status }}</em>
                            <h3 class="line-clamp-2" :style="project.status ? 'margin-top: 0.4rem' : undefined">
                                {{ project.title }}
                            </h3>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <section v-if="testimonials?.length" class="ws-section ws-section-soft ws-reveal">
            <div class="ws-container">
                <div class="ws-heading">
                    <span class="ws-kicker">{{ t('website.home.testimonials') }}</span>
                    <h2 class="ws-title">{{ t('website.home.testimonialsTitle') }}</h2>
                </div>
                <div class="ws-card-grid-2">
                    <blockquote
                        v-for="(item, index) in testimonials"
                        :key="`${item.name}-${index}`"
                        class="ws-quote"
                    >
                        <p>“{{ item.quote }}”</p>
                        <footer>
                            <strong>{{ item.name }}</strong>
                            <span v-if="item.role"> — {{ item.role }}</span>
                        </footer>
                    </blockquote>
                </div>
            </div>
        </section>

        <section class="ws-cta ws-reveal">
            <img
                class="ws-cta-media"
                src="/images/website/clients/atmosphere.jpg"
                alt=""
            />
            <div class="ws-cta-veil" />
            <div class="ws-container ws-cta-inner">
                <span class="ws-kicker">
                    {{ clients?.title || t('website.home.clientsTitle') }}
                </span>
                <h2>{{ t('website.home.ctaTitle') }}</h2>
                <p class="ws-lead">
                    {{ clients?.blurb || t('website.home.ctaLead') }}
                </p>
                <div v-if="clients?.logos?.length" class="ws-logo-row">
                    <img
                        v-for="(logo, index) in clients.logos"
                        :key="`${logo.src}-${index}`"
                        :src="logo.src"
                        :alt="logo.alt || 'Client'"
                    />
                </div>
                <Link href="/contact" class="ws-btn ws-btn-light">
                    {{ t('website.cta.contactUs') }}
                    <ArrowRight :size="15" />
                </Link>
            </div>
        </section>
    </div>
</template>
