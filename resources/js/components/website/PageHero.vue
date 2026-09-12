<script setup lang="ts">
withDefaults(
    defineProps<{
        eyebrow?: string | null;
        title: string;
        lead?: string | null;
        image?: string | null;
        compact?: boolean;
    }>(),
    {
        compact: false,
    },
);
</script>

<template>
    <section class="ws-page-hero" :class="{ 'is-compact': compact }">
        <div class="ws-page-hero-media" aria-hidden="true">
            <img :src="image || '/images/website/hero.jpg'" alt="" />
            <div class="ws-page-hero-veil" />
        </div>

        <div class="ws-container">
            <div class="ws-page-hero-copy">
                <p v-if="eyebrow" class="ws-kicker ws-rise">
                    {{ eyebrow }}
                </p>
                <h1 class="ws-rise ws-rise-delay-1">
                    {{ title }}
                </h1>
                <p v-if="lead" class="ws-lead ws-rise ws-rise-delay-2">
                    {{ lead }}
                </p>
                <div
                    v-if="$slots.default"
                    class="ws-rise ws-rise-delay-3"
                    style="margin-top: 1.15rem"
                >
                    <slot />
                </div>
            </div>
        </div>
    </section>
</template>
