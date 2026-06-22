<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Paperclip, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';

export interface EntityAttachment {
    id: number;
    title: string | null;
    original_filename: string;
    file_size: number | null;
    download_url: string;
    created_at: string;
}

const props = defineProps<{
    attachments: EntityAttachment[];
}>();

const { t } = useTranslations();

const formatSize = (bytes: number | null): string => {
    if (!bytes) {
        return '';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );

const removeAttachment = (attachmentId: number) => {
    router.delete(`/attachments/${attachmentId}`, { preserveScroll: true });
};
</script>

<template>
    <Card>
        <CardHeader class="pb-3">
            <CardTitle class="flex items-center gap-2 text-base">
                <Paperclip class="size-4" />
                {{ t('Attachments') }}
            </CardTitle>
            <CardDescription>
                {{ t('Optional files linked to this record') }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div
                v-if="attachments.length === 0"
                class="py-4 text-center text-sm text-muted-foreground"
            >
                {{ t('No attachments yet.') }}
            </div>
            <ul v-else class="divide-y">
                <li
                    v-for="file in attachments"
                    :key="file.id"
                    class="flex items-center justify-between gap-3 py-2"
                >
                    <div class="min-w-0">
                        <a
                            :href="file.download_url"
                            class="truncate text-sm font-medium hover:underline"
                        >
                            {{ file.title || file.original_filename }}
                        </a>
                        <p class="text-xs text-muted-foreground">
                            {{ formatDate(file.created_at) }}
                            <template v-if="file.file_size">
                                · {{ formatSize(file.file_size) }}
                            </template>
                        </p>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="shrink-0"
                        @click="removeAttachment(file.id)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
