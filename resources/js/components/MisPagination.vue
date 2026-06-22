<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated } from '@/lib/format';

defineProps<{
    pagination: Pick<Paginated<unknown>, 'links' | 'meta'>;
}>();

const { t } = useTranslations();
</script>

<template>
    <div
        v-if="pagination.meta && pagination.meta.last_page > 1"
        class="flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-sm text-muted-foreground">
            {{
                t('Showing :from–:to of :total', {
                    from: String(pagination.meta.from ?? 0),
                    to: String(pagination.meta.to ?? 0),
                    total: String(pagination.meta.total),
                })
            }}
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
