<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
import { computed } from 'vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteContact } from '@/types/website';

const props = defineProps<{
    contact?: WebsiteContact | null;
    blurb?: string | null;
}>();

const { t } = useTranslations();
const year = new Date().getFullYear();

const phone = computed(
    () => props.contact?.phone?.trim() || '+ (93) 799 111 911',
);
const email = computed(
    () => props.contact?.email?.trim() || 'm.office@sunskyglobalsecurity.com',
);
const address = computed(
    () =>
        props.contact?.address?.trim() ||
        'House 16# 4th Street of old Taimani, PD4, Kabul/ Afghanistan',
);

const explore = [
    { href: '/about', labelKey: 'website.nav.about' },
    { href: '/services', labelKey: 'website.nav.services' },
    { href: '/projects', labelKey: 'website.nav.projects' },
    { href: '/trainings', labelKey: 'website.nav.trainings' },
    { href: '/certificates', labelKey: 'website.nav.certificates' },
    { href: '/contact', labelKey: 'website.nav.contact' },
];
</script>

<template>
    <footer class="ws-footer">
        <div class="ws-container ws-footer-grid">
            <div class="ws-footer-brand">
                <Link href="/" class="ws-brand">
                    <span class="ws-brand-mark">
                        <AppLogoImage class="size-5" />
                    </span>
                    <span class="ws-brand-stack">
                        <strong>Sun Sky</strong>
                        <em>Global Security</em>
                    </span>
                </Link>
                <p>
                    {{
                        blurb ||
                        t('website.footer.blurb')
                    }}
                </p>
            </div>

            <div>
                <h3>{{ t('website.footer.explore') }}</h3>
                <Link
                    v-for="item in explore"
                    :key="item.href"
                    :href="item.href"
                >
                    {{ t(item.labelKey) }}
                </Link>
            </div>

            <div>
                <h3>{{ t('website.footer.contact') }}</h3>
                <p class="ws-footer-line">
                    <MapPin :size="14" />
                    <span>{{ address }}</span>
                </p>
                <a class="ws-footer-line" :href="`mailto:${email}`">
                    <Mail :size="14" />
                    <span>{{ email }}</span>
                </a>
                <a
                    class="ws-footer-line"
                    :href="`tel:${phone.replace(/\s+/g, '')}`"
                >
                    <Phone :size="14" />
                    <span>{{ phone }}</span>
                </a>
            </div>
        </div>
        <div class="ws-container ws-footer-copy">
            <span>© {{ year }} Sun Sky Global Security</span>
            <span>{{ t('website.footer.rights') }}</span>
        </div>
    </footer>
</template>
