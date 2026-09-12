<script setup lang="ts">
withDefaults(
    defineProps<{
        image?: string;
        alt?: string;
        compact?: boolean;
        priority?: boolean;
    }>(),
    {
        image: '/images/gs-hero-operations.png',
        alt: '',
        compact: false,
        priority: true,
    },
);
</script>

<template>
    <section class="hero detail-hero reveal" :class="{ compact }">
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
        <div class="hero-body detail-hero-body">
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
            <div v-if="$slots.side || $slots.actions" class="hero-side detail-hero-side">
                <div v-if="$slots.actions" class="detail-actions">
                    <slot name="actions" />
                </div>
                <slot name="side" />
            </div>
        </div>
        <slot name="stats" />
    </section>
</template>
