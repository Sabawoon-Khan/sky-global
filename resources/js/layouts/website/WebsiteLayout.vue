<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FlashToasts from '@/components/FlashToasts.vue';
import WebsiteFooter from '@/components/website/WebsiteFooter.vue';
import WebsiteHeader from '@/components/website/WebsiteHeader.vue';
import WhatsAppFab from '@/components/website/WhatsAppFab.vue';
import { Toaster } from '@/components/ui/sonner';
import type { WebsiteContact } from '@/types/website';
import '@/../css/website.css';

const page = usePage();

const contact = computed(() => {
    const props = page.props as Record<string, unknown>;
    return (props.contact as WebsiteContact | undefined) ?? null;
});

const footerBlurb = computed(() => {
    const props = page.props as Record<string, unknown>;
    return (
        (props.footerBlurb as string | undefined) ??
        (props.clientsBlurb as string | undefined) ??
        null
    );
});

const whatsappHref = computed(() => {
    const value = contact.value?.whatsapp?.trim();
    if (value?.startsWith('http')) {
        return value;
    }
    const digits =
        contact.value?.phone_raw?.replace(/\D/g, '') ||
        value?.replace(/\D/g, '') ||
        '93799111911';
    return `https://wa.me/${digits}`;
});
</script>

<template>
    <div class="website-shell">
        <WebsiteHeader :contact="contact" />
        <main>
            <slot />
        </main>
        <WebsiteFooter :contact="contact" :blurb="footerBlurb" />
        <WhatsAppFab :href="whatsappHref" />
        <FlashToasts />
        <Toaster />
    </div>
</template>
