<script setup lang="ts">
import type { WebsiteStat } from '@/types/website';

defineProps<{
    stats: WebsiteStat[];
    title?: string | null;
    lead?: string | null;
}>();
</script>

<template>
    <section class="ws-stats-band">
        <div class="ws-stats-band-media" aria-hidden="true">
            <img src="/images/website/ops-atmosphere.jpg" alt="" />
            <div class="ws-stats-band-veil" />
        </div>
        <div class="ws-container">
            <div v-if="title || lead" style="max-width: 36rem">
                <span v-if="title" class="ws-kicker">{{ title }}</span>
                <h2 v-if="title">{{ title }}</h2>
                <p v-if="lead" class="ws-lead">{{ lead }}</p>
            </div>
            <div class="ws-stats-row" :style="!title && !lead ? 'margin-top: 0' : undefined">
                <article
                    v-for="(stat, index) in stats"
                    :key="`${stat.label}-${index}`"
                >
                    <strong>{{ stat.value }}</strong>
                    <span>{{ stat.label }}</span>
                </article>
            </div>
        </div>
    </section>
</template>
