<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Archive, FileText } from '@lucide/vue';
import ArchiveDocumentFields from '@/components/archive/ArchiveDocumentFields.vue';
import Can from '@/components/Can.vue';
import MisPage from '@/components/MisPage.vue';
import RichTextContent from '@/components/RichTextContent.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useMisPage } from '@/composables/useMisPage';

interface DocumentCategory {
    id: number;
    name: string;
}

interface ArchivedDocument {
    id: number;
    reference_number?: string | null;
    title: string;
    description?: string | null;
    direction?: string | null;
    document_date?: string | null;
    received_at?: string | null;
    sent_at?: string | null;
    original_filename?: string | null;
    file_size?: number | null;
    version?: number | null;
    tags?: string[] | null;
    document_category_id?: number | null;
    organization_id?: number | null;
    project_id?: number | null;
    document_category?: DocumentCategory | null;
    organization?: { id: number; name: string } | null;
    project?: { id: number; code: string; name: string } | null;
    uploaded_by?: { id: number; name: string } | null;
}

interface Props {
    document: ArchivedDocument;
    categories: DocumentCategory[];
    organizations: Array<{ id: number; name: string }>;
    projects: Array<{ id: number; code: string; name: string }>;
}

const props = defineProps<Props>();

const { t, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Archive', href: '/archive' },
            { title: 'Document', href: '#' },
        ],
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

const formatSize = (bytes?: number | null): string => {
    if (!bytes) {
        return '—';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const moveToLongTermArchive = (): void => {
    if (
        !confirm(
            t(
                'Move this document to long-term archive? It will no longer appear in the active list.',
            ),
        )
    ) {
        return;
    }

    router.post(`/archive/${props.document.id}/archive`);
};
</script>

<template>
    <Head :title="document.title" />

    <MisPage>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ document.title }}
                </h1>
                <p
                    v-if="document.reference_number"
                    class="font-mono text-sm text-muted-foreground"
                >
                    {{ document.reference_number }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <Badge v-if="document.direction" variant="outline">
                        {{ document.direction }}
                    </Badge>
                    <Badge v-if="document.document_category" variant="secondary">
                        {{ document.document_category.name }}
                    </Badge>
                    <Badge v-if="document.version" variant="outline">
                        v{{ document.version }}
                    </Badge>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link href="/archive">{{ t('Back to list') }}</Link>
                </Button>
                <Button
                    v-if="can('archive.archive')"
                    variant="destructive"
                    @click="moveToLongTermArchive"
                >
                    {{ t('Move to long-term archive') }}
                </Button>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="size-5 text-primary" />
                        {{ t('Document details') }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="grid gap-5 text-sm">
                    <div class="grid gap-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            {{ t('Description') }}
                        </p>
                        <RichTextContent :content="document.description" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ t('Document date') }}
                            </p>
                            <p>{{ formatDate(document.document_date) }}</p>
                        </div>
                        <div class="grid gap-1">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ t('Received') }}
                            </p>
                            <p>{{ formatDate(document.received_at) }}</p>
                        </div>
                        <div class="grid gap-1">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ t('Sent') }}
                            </p>
                            <p>{{ formatDate(document.sent_at) }}</p>
                        </div>
                        <div class="grid gap-1">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ t('Uploaded by') }}
                            </p>
                            <p>{{ document.uploaded_by?.name ?? '—' }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ t('Organization') }}
                            </p>
                            <p>{{ document.organization?.name ?? '—' }}</p>
                        </div>
                        <div class="grid gap-1">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ t('Project') }}
                            </p>
                            <Link
                                v-if="document.project"
                                :href="`/projects/${document.project.id}`"
                                class="hover:underline"
                            >
                                {{ document.project.code }} — {{ document.project.name }}
                            </Link>
                            <p v-else>—</p>
                        </div>
                    </div>

                    <div class="grid gap-1">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            {{ t('File') }}
                        </p>
                        <p>
                            {{ document.original_filename ?? '—' }}
                            <span
                                v-if="document.file_size"
                                class="text-muted-foreground"
                            >
                                ({{ formatSize(document.file_size) }})
                            </span>
                        </p>
                    </div>

                    <div v-if="document.tags?.length" class="flex flex-wrap gap-2">
                        <Badge
                            v-for="tag in document.tags"
                            :key="tag"
                            variant="outline"
                        >
                            {{ tag }}
                        </Badge>
                    </div>
                </CardContent>
            </Card>

            <Can permission="archive.edit">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Archive class="size-5 text-primary" />
                            {{ t('Edit document') }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('Update metadata or replace the file') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            :action="`/archive/${document.id}`"
                            method="put"
                            class="space-y-6"
                            :options="{ preserveScroll: true, forceFormData: true }"
                            v-slot="{ errors, processing }"
                        >
                            <ArchiveDocumentFields
                                :errors="errors"
                                :document="document"
                                :categories="categories"
                                :organizations="organizations"
                                :projects="projects"
                                :file-label="t('Replace file')"
                            />

                            <Button type="submit" :disabled="processing" class="w-full sm:w-auto">
                                {{ t('Save changes') }}
                            </Button>
                        </Form>
                    </CardContent>
                </Card>
            </Can>
        </div>
    </MisPage>
</template>
