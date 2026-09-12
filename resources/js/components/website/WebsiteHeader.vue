<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, Mail, Menu, Phone, X } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, login } from '@/routes';
import type { WebsiteContact } from '@/types/website';

const props = defineProps<{
    contact?: WebsiteContact | null;
}>();

const page = usePage();
const { t } = useTranslations();
const isAuthenticated = computed(() => !!page.props.auth.user);
const mobileOpen = ref(false);
const openDropdown = ref<string | null>(null);
const scrolled = ref(false);

const phone = computed(
    () => props.contact?.phone?.trim() || '+ (93) 799 111 911',
);
const email = computed(
    () => props.contact?.email?.trim() || 'm.office@sunskyglobalsecurity.com',
);
const phoneHref = computed(() => `tel:${phone.value.replace(/[^\d+]/g, '')}`);

type NavLink = { href: string; label: string };
type NavItem =
    | ({ type: 'link' } & NavLink)
    | { type: 'group'; key: string; label: string; children: NavLink[] };

const navItems = computed<NavItem[]>(() => [
    { type: 'link', href: '/', label: t('website.nav.home') },
    {
        type: 'group',
        key: 'about',
        label: t('website.nav.about'),
        children: [
            { href: '/about', label: t('website.nav.about') },
            { href: '/what-we-do', label: t('website.nav.whatWeDo') },
        ],
    },
    { type: 'link', href: '/services', label: t('website.nav.services') },
    {
        type: 'group',
        key: 'projects',
        label: t('website.nav.projects'),
        children: [
            { href: '/projects', label: t('website.nav.allProjects') },
            { href: '/projects/ongoing', label: t('website.nav.ongoingProjects') },
            {
                href: '/projects/completed',
                label: t('website.nav.completedProjects'),
            },
        ],
    },
    { type: 'link', href: '/trainings', label: t('website.nav.trainings') },
    { type: 'link', href: '/certificates', label: t('website.nav.certificates') },
    { type: 'link', href: '/contact', label: t('website.nav.contact') },
]);

function pathOnly(): string {
    return page.url.split('?')[0] || '/';
}

function isActive(href: string): boolean {
    const path = pathOnly();
    if (href === '/') {
        return path === '/';
    }
    return path === href || path.startsWith(`${href}/`);
}

function isGroupActive(children: NavLink[]): boolean {
    return children.some((child) => isActive(child.href));
}

function closeMobile() {
    mobileOpen.value = false;
    openDropdown.value = null;
}

function toggleDropdown(key: string) {
    openDropdown.value = openDropdown.value === key ? null : key;
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        if (mobileOpen.value) {
            closeMobile();
        } else {
            openDropdown.value = null;
        }
    }
}

function onScroll() {
    scrolled.value = window.scrollY > 24;
}

function onDocClick(event: MouseEvent) {
    const target = event.target as HTMLElement | null;
    if (!target?.closest('.ws-nav-item')) {
        openDropdown.value = null;
    }
}

watch(mobileOpen, (open) => {
    if (typeof document === 'undefined') {
        return;
    }
    document.body.style.overflow = open ? 'hidden' : '';
});

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('scroll', onScroll, { passive: true });
    document.addEventListener('click', onDocClick);
    onScroll();
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('scroll', onScroll);
    document.removeEventListener('click', onDocClick);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <header class="ws-nav" :class="{ 'is-scrolled': scrolled, 'is-open': mobileOpen }">
        <div class="ws-topbar">
            <div class="ws-topbar-inner">
                <a class="ws-topbar-link" :href="`mailto:${email}`">
                    <Mail :size="13" aria-hidden="true" />
                    <span>{{ email }}</span>
                </a>
                <a class="ws-topbar-link" :href="phoneHref">
                    <Phone :size="13" aria-hidden="true" />
                    <span>{{ phone }}</span>
                </a>
            </div>
        </div>

        <div class="ws-island">
            <Link href="/" class="ws-brand" @click="closeMobile">
                <span class="ws-brand-mark">
                    <AppLogoImage class="size-8" />
                </span>
                <span class="ws-brand-stack">
                    <strong>Sun Sky</strong>
                    <em>Global Security</em>
                </span>
            </Link>

            <nav class="ws-links" :aria-label="t('website.nav.primary')">
                <template v-for="item in navItems" :key="item.type === 'link' ? item.href : item.key">
                    <Link
                        v-if="item.type === 'link'"
                        :href="item.href"
                        class="ws-nav-link"
                        :class="{ 'is-active': isActive(item.href) }"
                    >
                        {{ item.label }}
                    </Link>

                    <div
                        v-else
                        class="ws-nav-item"
                        :class="{
                            'is-open': openDropdown === item.key,
                            'is-active': isGroupActive(item.children),
                        }"
                    >
                        <button
                            type="button"
                            class="ws-nav-link ws-nav-trigger"
                            :aria-expanded="openDropdown === item.key"
                            @click.stop="toggleDropdown(item.key)"
                        >
                            {{ item.label }}
                            <ChevronDown :size="14" aria-hidden="true" />
                        </button>
                        <div class="ws-dropdown" role="menu">
                            <Link
                                v-for="child in item.children"
                                :key="child.href"
                                :href="child.href"
                                role="menuitem"
                                :class="{ 'is-active': isActive(child.href) }"
                                @click="openDropdown = null"
                            >
                                {{ child.label }}
                            </Link>
                        </div>
                    </div>
                </template>
            </nav>

            <div class="ws-nav-actions">
                <div class="ws-lang">
                    <LanguageSwitcher />
                </div>
                <Link
                    v-if="isAuthenticated"
                    :href="dashboard()"
                    class="ws-staff"
                >
                    {{ t('Dashboard') }}
                </Link>
                <Link v-else :href="login()" class="ws-staff">
                    {{ t('website.nav.staffLogin') }}
                </Link>
                <Link href="/contact" class="ws-cta-btn">
                    {{ t('website.cta.contactUs') }}
                </Link>
                <button
                    type="button"
                    class="ws-menu"
                    :aria-expanded="mobileOpen"
                    :aria-label="
                        mobileOpen
                            ? t('website.nav.closeMenu')
                            : t('website.nav.openMenu')
                    "
                    @click="mobileOpen = !mobileOpen"
                >
                    <X v-if="mobileOpen" :size="18" />
                    <Menu v-else :size="18" />
                </button>
            </div>
        </div>

        <nav
            v-if="mobileOpen"
            class="ws-mobile"
            :aria-label="t('website.nav.mobile')"
        >
            <template v-for="item in navItems" :key="item.type === 'link' ? item.href : item.key">
                <Link
                    v-if="item.type === 'link'"
                    :href="item.href"
                    :class="{ 'is-active': isActive(item.href) }"
                    @click="closeMobile"
                >
                    {{ item.label }}
                </Link>
                <div v-else class="ws-mobile-group">
                    <p>{{ item.label }}</p>
                    <Link
                        v-for="child in item.children"
                        :key="child.href"
                        :href="child.href"
                        :class="{ 'is-active': isActive(child.href) }"
                        @click="closeMobile"
                    >
                        {{ child.label }}
                    </Link>
                </div>
            </template>
            <div class="ws-mobile-footer">
                <Link href="/contact" class="ws-cta-btn" @click="closeMobile">
                    {{ t('website.cta.contactUs') }}
                </Link>
                <Link
                    v-if="isAuthenticated"
                    :href="dashboard()"
                    class="ws-staff"
                    @click="closeMobile"
                >
                    {{ t('Dashboard') }}
                </Link>
                <Link
                    v-else
                    :href="login()"
                    class="ws-staff"
                    @click="closeMobile"
                >
                    {{ t('website.nav.staffLogin') }}
                </Link>
            </div>
        </nav>
    </header>
</template>
