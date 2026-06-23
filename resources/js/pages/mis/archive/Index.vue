<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Archive, Search } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useMisPage } from '@/composables/useMisPage';
import type { RowActionItem } from '@/lib/row-actions';

interface DocumentCategory {
    id: number;
    name: string;
}

interface ArchivedDocument {
    id: number;
    reference_number?: string | null;
    title: string;
    direction?: string | null;
    document_date?: string | null;
    document_category?: DocumentCategory | null;
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

const { t, viewAction, editAction } = useMisPage();

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
];
</script>

<template>
    <Head :title="t('Document Archive')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            :title="t('Document Archive')"
            :description="t('Central repository for incoming and outgoing documents')"
        />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Archive class="size-5" />
                    {{ t('Archived Documents') }}
                </CardTitle>
                <CardDescription>
                    {{
                        t(':count documents', {
                            count: String(
                                documents.meta?.total ?? documents.data.length,
                            ),
                        })
                    }}
                </CardDescription>
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
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
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
