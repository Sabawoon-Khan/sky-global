<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Archive, Paperclip, Search } from '@lucide/vue';
import MisCreateButton from '@/components/MisCreateButton.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useMisPage } from '@/composables/useMisPage';
import type { RowActionItem } from '@/lib/row-actions';

interface ArchivedDocument {
    id: number;
    reference_number?: string | null;
    title: string;
    direction?: string | null;
    document_date?: string | null;
    original_filename?: string | null;
    download_url?: string | null;
    document_category?: { id: number; name: string } | null;
    organization?: { id: number; name: string } | null;
    project?: { id: number; code: string; name: string } | null;
}

interface PaginatedDocuments {
    data: ArchivedDocument[];
    meta?: { total: number };
}

interface Props {
    documents: PaginatedDocuments;
    filters?: { search?: string; direction?: string };
}

defineProps<Props>();

const { t, viewAction, editAction, deleteAction } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Archive', href: '/archive' }],
    },
});

const formatDate = (value?: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );
};

const documentActions = (doc: ArchivedDocument): RowActionItem[] => [
    viewAction(`/archive/${doc.id}`),
    editAction(`/archive/${doc.id}`, 'archive.edit'),
    deleteAction(
        {
            href: `/archive/${doc.id}`,
            title: t('Delete document'),
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                { name: doc.title },
            ),
        },
        'archive.delete',
    ),
];
</script>

<template>
    <Head :title="t('Document Archive')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Archive class="size-5" />
                    {{ t('Archived Documents') }}
                </CardTitle>
                <CardAction>
                    <MisCreateButton href="/archive/create" permission="archive.create">
                        {{ t('Register new document') }}
                    </MisCreateButton>
                </CardAction>
            </CardHeader>
            <CardContent class="space-y-4">
                <form method="get" action="/archive" class="relative max-w-sm">
                    <Search
                        class="absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        name="search"
                        :default-value="filters?.search"
                        :placeholder="t('Search archive...')"
                        class="ps-9"
                    />
                </form>

                <div
                    v-if="documents.data.length === 0"
                    class="ui-empty-state"
                >
                    {{ t('No documents in archive.') }}
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-start text-muted-foreground">
                                <th class="pb-3 pe-4 font-medium">{{ t('Reference') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{ t('Title') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{ t('Category') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{ t('Linked To') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{ t('Date') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{ t('Attachment') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{ t('Direction') }}</th>
                                <th class="pb-3 text-end font-medium">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="doc in documents.data"
                                :key="doc.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pe-4 font-mono text-xs">
                                    {{ doc.reference_number ?? '—' }}
                                </td>
                                <td class="py-3 pe-4 font-medium">
                                    {{ doc.title }}
                                </td>
                                <td class="py-3 pe-4 text-muted-foreground">
                                    {{ doc.document_category?.name ?? '—' }}
                                </td>
                                <td class="py-3 pe-4 text-muted-foreground">
                                    <Link
                                        v-if="doc.project"
                                        :href="`/projects/${doc.project.id}`"
                                        class="hover:underline"
                                    >
                                        {{ doc.project.code }}
                                    </Link>
                                    <span v-else-if="doc.organization">
                                        {{ doc.organization.name }}
                                    </span>
                                    <span v-else>—</span>
                                </td>
                                <td class="py-3 pe-4 text-muted-foreground">
                                    {{ formatDate(doc.document_date) }}
                                </td>
                                <td class="py-3 pe-4">
                                    <a
                                        v-if="doc.download_url"
                                        :href="doc.download_url"
                                        class="inline-flex items-center gap-1 text-primary hover:underline"
                                        :title="doc.original_filename ?? undefined"
                                    >
                                        <Paperclip class="size-3.5 shrink-0" />
                                        <span class="max-w-[8rem] truncate text-xs">
                                            {{ doc.original_filename ?? t('Download') }}
                                        </span>
                                    </a>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="py-3 pe-4">
                                    <Badge
                                        v-if="doc.direction"
                                        variant="outline"
                                    >
                                        {{ doc.direction }}
                                    </Badge>
                                    <span v-else>—</span>
                                </td>
                                <td class="py-3 text-end">
                                    <RowActionsMenu
                                        :actions="documentActions(doc)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
