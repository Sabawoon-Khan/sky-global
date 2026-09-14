<script setup lang="ts">
import { Download, Paperclip } from '@lucide/vue';
import { computed } from 'vue';
import { fileDownloadUrl } from '@/lib/file-url';
import { useTranslations } from '@/composables/useTranslations';

const props = withDefaults(
    defineProps<{
        href: string;
        label: string;
        showIcon?: boolean;
        compact?: boolean;
    }>(),
    {
        showIcon: false,
        compact: false,
    },
);

const { t } = useTranslations();

const downloadHref = computed(() => fileDownloadUrl(props.href));
</script>

<template>
    <span
        class="inline-flex max-w-full items-center gap-1"
        @click.stop
    >
        <a
            :href="href"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex min-w-0 items-center gap-1 text-primary hover:underline"
            :title="t('View file')"
        >
            <Paperclip
                v-if="showIcon"
                class="size-3.5 shrink-0"
            />
            <span
                :class="
                    compact
                        ? 'max-w-[8rem] truncate text-xs'
                        : 'truncate'
                "
            >
                {{ label }}
            </span>
        </a>
        <a
            :href="downloadHref"
            class="inline-flex shrink-0 rounded-sm p-0.5 text-muted-foreground hover:bg-muted hover:text-foreground"
            :title="t('Download')"
            :download="label"
        >
            <Download class="size-3.5" />
            <span class="sr-only">{{ t('Download') }}</span>
        </a>
    </span>
</template>
