<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    title?: string | null;
    description?: string | null;
    image?: string | null;
}>();

const resolvedTitle = computed(() => props.title?.trim() || undefined);
const resolvedDescription = computed(() => props.description?.trim() || undefined);
const resolvedImage = computed(() => props.image?.trim() || '/images/website/hero.jpg');
</script>

<template>
    <Head :title="resolvedTitle">
        <meta
            v-if="resolvedDescription"
            head-key="description"
            name="description"
            :content="resolvedDescription"
        />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta
            v-if="resolvedTitle"
            head-key="og:title"
            property="og:title"
            :content="resolvedTitle"
        />
        <meta
            v-if="resolvedDescription"
            head-key="og:description"
            property="og:description"
            :content="resolvedDescription"
        />
        <meta head-key="og:image" property="og:image" :content="resolvedImage" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta
            v-if="resolvedTitle"
            head-key="twitter:title"
            name="twitter:title"
            :content="resolvedTitle"
        />
        <meta
            v-if="resolvedDescription"
            head-key="twitter:description"
            name="twitter:description"
            :content="resolvedDescription"
        />
        <meta head-key="twitter:image" name="twitter:image" :content="resolvedImage" />
    </Head>
</template>
