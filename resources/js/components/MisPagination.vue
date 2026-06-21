<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Paginated } from '@/lib/format';

defineProps<{
    pagination: Pick<Paginated<unknown>, 'links' | 'meta'>;
}>();
</script>

<template>
    <div
        v-if="pagination.meta && pagination.meta.last_page > 1"
        class="flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-sm text-muted-foreground">
            Showing {{ pagination.meta.from ?? 0 }}–{{ pagination.meta.to ?? 0 }}
            of {{ pagination.meta.total }}
        </p>
        <div class="flex flex-wrap gap-1">
            <template v-for="(link, index) in pagination.links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded-md px-3 py-1 text-sm transition-colors"
                    :class="
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-muted'
                    "
                    preserve-scroll
                    v-html="link.label"
                />
                <span
                    v-else
                    class="px-3 py-1 text-sm text-muted-foreground"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
