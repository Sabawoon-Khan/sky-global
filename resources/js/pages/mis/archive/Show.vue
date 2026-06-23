<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Archive, FileText } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Heading
                    :title="document.title"
                    :description="document.reference_number ?? t('Archived document')"
                />
                <div class="mt-3 flex flex-wrap gap-2">
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

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="size-5" />
                        {{ t('Document details') }}
                    </CardTitle>
                    <CardDescription>{{ t('Metadata and linked records') }}</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 text-sm">
                    <div class="grid gap-1">
                        <p class="text-muted-foreground">{{ t('Description') }}</p>
                        <p>{{ document.description ?? '—' }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1">
                            <p class="text-muted-foreground">{{ t('Document date') }}</p>
                            <p>{{ formatDate(document.document_date) }}</p>
                        </div>
                        <div class="grid gap-1">
                            <p class="text-muted-foreground">{{ t('Received') }}</p>
                            <p>{{ formatDate(document.received_at) }}</p>
                        </div>
                        <div class="grid gap-1">
                            <p class="text-muted-foreground">{{ t('Sent') }}</p>
                            <p>{{ formatDate(document.sent_at) }}</p>
                        </div>
                        <div class="grid gap-1">
                            <p class="text-muted-foreground">{{ t('Uploaded by') }}</p>
                            <p>{{ document.uploaded_by?.name ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1">
                            <p class="text-muted-foreground">{{ t('Organization') }}</p>
                            <p>{{ document.organization?.name ?? '—' }}</p>
                        </div>
                        <div class="grid gap-1">
                            <p class="text-muted-foreground">{{ t('Project') }}</p>
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
                        <p class="text-muted-foreground">{{ t('File') }}</p>
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
                        <Archive class="size-5" />
                        {{ t('Edit document') }}
                    </CardTitle>
                    <CardDescription>{{
                        t('Update metadata or replace the file')
                    }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        :action="`/archive/${document.id}`"
                        method="put"
                        class="grid gap-4"
                        :options="{ preserveScroll: true, forceFormData: true }"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="title">{{ t('Title') }} *</Label>
                            <Input
                                id="title"
                                name="title"
                                required
                                :default-value="document.title"
                            />
                            <InputError :message="errors.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">{{ t('Description') }}</Label>
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                            >{{ document.description ?? '' }}</textarea>
                            <InputError :message="errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="direction">{{ t('Direction') }}</Label>
                            <select
                                id="direction"
                                name="direction"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option
                                    value="incoming"
                                    :selected="document.direction === 'incoming'"
                                >
                                    {{ t('Incoming') }}
                                </option>
                                <option
                                    value="outgoing"
                                    :selected="document.direction === 'outgoing'"
                                >
                                    {{ t('Outgoing') }}
                                </option>
                                <option
                                    value="internal"
                                    :selected="document.direction === 'internal'"
                                >
                                    {{ t('Internal') }}
                                </option>
                            </select>
                            <InputError :message="errors.direction" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="document_category_id">{{ t('Category') }}</Label>
                            <select
                                id="document_category_id"
                                name="document_category_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">{{ t('None') }}</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                    :selected="document.document_category_id === category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="errors.document_category_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="organization_id">{{ t('Organization') }}</Label>
                            <select
                                id="organization_id"
                                name="organization_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">{{ t('None') }}</option>
                                <option
                                    v-for="organization in organizations"
                                    :key="organization.id"
                                    :value="organization.id"
                                    :selected="document.organization_id === organization.id"
                                >
                                    {{ organization.name }}
                                </option>
                            </select>
                            <InputError :message="errors.organization_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="project_id">{{ t('Project') }}</Label>
                            <select
                                id="project_id"
                                name="project_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">{{ t('None') }}</option>
                                <option
                                    v-for="project in projects"
                                    :key="project.id"
                                    :value="project.id"
                                    :selected="document.project_id === project.id"
                                >
                                    {{ project.code }} — {{ project.name }}
                                </option>
                            </select>
                            <InputError :message="errors.project_id" />
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="document_date">{{ t('Document date') }}</Label>
                                <Input
                                    id="document_date"
                                    name="document_date"
                                    type="date"
                                    :default-value="document.document_date?.slice(0, 10)"
                                />
                                <InputError :message="errors.document_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="received_at">{{ t('Received') }}</Label>
                                <Input
                                    id="received_at"
                                    name="received_at"
                                    type="date"
                                    :default-value="document.received_at?.slice(0, 10)"
                                />
                                <InputError :message="errors.received_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="sent_at">{{ t('Sent') }}</Label>
                                <Input
                                    id="sent_at"
                                    name="sent_at"
                                    type="date"
                                    :default-value="document.sent_at?.slice(0, 10)"
                                />
                                <InputError :message="errors.sent_at" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="file">{{ t('Replace file') }}</Label>
                            <Input id="file" name="file" type="file" />
                            <InputError :message="errors.file" />
                        </div>

                        <Button type="submit" :disabled="processing">
                            {{ t('Save changes') }}
                        </Button>
                    </Form>
                </CardContent>
            </Card>
            </Can>
        </div>
    </div>
</template>
