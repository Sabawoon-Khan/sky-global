<script setup lang="ts">
import { MessageCircle } from '@lucide/vue';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

const props = withDefaults(
    defineProps<{
        href?: string | null;
        phone?: string | null;
    }>(),
    {
        href: null,
        phone: '93799111911',
    },
);

const { t } = useTranslations();

const link = computed(() => {
    if (props.href?.trim()) {
        return props.href.trim();
    }
    const digits = (props.phone || '93799111911').replace(/\D/g, '');
    return `https://wa.me/${digits}`;
});
</script>

<template>
    <a
        :href="link"
        target="_blank"
        rel="noopener noreferrer"
        class="ws-whatsapp"
        :aria-label="t('website.cta.whatsapp')"
    >
        <MessageCircle :size="22" />
    </a>
</template>
