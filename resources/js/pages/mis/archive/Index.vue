<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Archive, Plus, Search } from '@lucide/vue';
import Can from '@/components/Can.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
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
import { Textarea } from '@/components/ui/textarea';
import { useMisPage } from '@/composables/useMisPage';
import type { RowActionItem } from '@/lib/row-actions';

interface DocumentCategory {
    id: number;
    name: string;
}

interface Option {
    id: number;
    name: string;
    code?: string;
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
    categories?: DocumentCategory[];
    organizations?: Option[];
    projects?: Option[];
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
            :description="t('Register and manage all your documents')"
        />

        <Can permission="archive.create">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Plus class="size-5" />
                    {{ t('Register new document') }}
                </CardTitle>
                <CardDescription>{{ t('Upload a file and record its details') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    action="/archive"
                    method="post"
                    class="grid gap-4 lg:grid-cols-2"
                    :options="{ preserveScroll: true, forceFormData: true, resetOnSuccess: true }"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-3">
                        <div class="grid gap-2">
                            <Label for="doc-title">{{ t('Title') }} *</Label>
                            <Input id="doc-title" name="title" required />
                            <InputError :message="errors.title" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="doc-description">{{ t('Description') }}</Label>
                            <Textarea id="doc-description" name="description" rows="3" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="doc-file">{{ t('File') }} *</Label>
                            <Input id="doc-file" name="file" type="file" required />
                            <InputError :message="errors.file" />
                        </div>
                    </div>
                    <div class="grid gap-3">
                        <div class="grid gap-2">
                            <Label for="doc-direction">{{ t('Direction') }} *</Label>
                            <select id="doc-direction" name="direction" required class="h-9 rounded-md border border-input px-3 text-sm">
                                <option value="incoming">{{ t('Incoming') }}</option>
                                <option value="outgoing">{{ t('Outgoing') }}</option>
                                <option value="internal">{{ t('Internal') }}</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="doc-category">{{ t('Category') }}</Label>
                            <select id="doc-category" name="document_category_id" class="h-9 rounded-md border border-input px-3 text-sm">
                                <option value="">{{ t('None') }}</option>
                                <option v-for="cat in categories ?? []" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="doc-date">{{ t('Document date') }}</Label>
                            <Input id="doc-date" name="document_date" type="date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="doc-org">{{ t('Organization') }}</Label>
                            <select id="doc-org" name="organization_id" class="h-9 rounded-md border border-input px-3 text-sm">
                                <option value="">{{ t('None') }}</option>
                                <option v-for="org in organizations ?? []" :key="org.id" :value="org.id">{{ org.name }}</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="doc-project">{{ t('Project') }}</Label>
                            <select id="doc-project" name="project_id" class="h-9 rounded-md border border-input px-3 text-sm">
                                <option value="">{{ t('None') }}</option>
                                <option v-for="proj in projects ?? []" :key="proj.id" :value="proj.id">
                                    {{ proj.code }} — {{ proj.name }}
                                </option>
                            </select>
                        </div>
                        <Button type="submit" :disabled="processing">{{ t('Register document') }}</Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
        </Can>

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
