<script setup lang="ts">
import MisExportActions from '@/components/mis/MisExportActions.vue';

withDefaults(
    defineProps<{
        image: string;
        alt?: string;
        priority?: boolean;
    }>(),
    {
        alt: '',
        priority: false,
    },
);
</script>

<template>
    <section class="hero reveal">
        <img
            class="hero-image"
            :src="image"
            :alt="alt"
            width="1280"
            height="720"
            decoding="async"
            :loading="priority ? 'eager' : 'lazy'"
            :fetchpriority="priority ? 'high' : 'auto'"
        />
        <div class="hero-fade" />
        <div class="hero-body">
            <div class="hero-copy">
                <slot name="copy">
                    <p v-if="$slots.eyebrow" class="eyebrow">
                        <slot name="eyebrow" />
                    </p>
                    <h1 v-if="$slots.title"><slot name="title" /></h1>
                    <p v-if="$slots.description">
                        <slot name="description" />
                    </p>
                </slot>
            </div>
            <div class="hero-side">
                <div class="hero-toolbar no-print">
                    <MisExportActions />
                </div>
                <slot name="side" />
            </div>
        </div>
        <slot name="stats" />
    </section>
</template>
