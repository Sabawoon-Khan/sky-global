<script setup lang="ts">
import DOMPurify from 'dompurify';
import { computed } from 'vue';

const props = defineProps<{
    content?: string | null;
    emptyLabel?: string;
}>();

const safeHtml = computed(() => {
    const value = props.content?.trim();

    if (!value || value === '<p></p>') {
        return '';
    }

    return DOMPurify.sanitize(value, {
        USE_PROFILES: { html: true },
    });
});
</script>

<template>
    <div
        v-if="safeHtml"
        class="rich-text-content text-sm leading-relaxed text-foreground"
        v-html="safeHtml"
    />
    <p v-else class="text-sm text-muted-foreground">
        {{ emptyLabel ?? '—' }}
    </p>
</template>
