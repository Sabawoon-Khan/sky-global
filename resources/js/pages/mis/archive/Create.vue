<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    FileText,
    Link2,
    Paperclip,
    StickyNote,
} from '@lucide/vue';
import ArchiveDocumentFields from '@/components/archive/ArchiveDocumentFields.vue';
import MisPage from '@/components/MisPage.vue';
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

interface Option {
    id: number;
    name: string;
    code?: string;
}

defineProps<{
    categories?: DocumentCategory[];
    organizations?: Option[];
    projects?: Option[];
}>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Archive', href: '/archive' },
            { title: 'Register new document', href: '/archive/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Register new document')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('Register new document') }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('Upload a file and record its details') }}
                </p>
            </div>
            <Button variant="outline" as-child class="w-fit">
                <Link href="/archive">{{ t('Cancel') }}</Link>
            </Button>
        </div>

        <Form
            action="/archive"
            method="post"
            class="flex flex-1 flex-col gap-6"
            :options="{ forceFormData: true }"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <FileText class="size-4 text-primary" />
                        {{ t('Basic information') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('Title, direction, and document category') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ArchiveDocumentFields
                        section="basic"
                        :errors="errors"
                        :categories="categories"
                        :organizations="organizations"
                        :projects="projects"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <CalendarDays class="size-4 text-primary" />
                        {{ t('Dates') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('Document date, received, and sent dates') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ArchiveDocumentFields
                        section="dates"
                        :errors="errors"
                        :categories="categories"
                        :organizations="organizations"
                        :projects="projects"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Link2 class="size-4 text-primary" />
                        {{ t('Linked To') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('Connect this document to an organization or project') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ArchiveDocumentFields
                        section="links"
                        :errors="errors"
                        :categories="categories"
                        :organizations="organizations"
                        :projects="projects"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Paperclip class="size-4 text-primary" />
                        {{ t('Attachment') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('Upload the document file') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ArchiveDocumentFields
                        section="file"
                        :errors="errors"
                        :categories="categories"
                        :organizations="organizations"
                        :projects="projects"
                        file-required
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <StickyNote class="size-4 text-primary" />
                        {{ t('Description') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('Optional notes or summary about this document') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ArchiveDocumentFields
                        section="description"
                        :errors="errors"
                        :categories="categories"
                        :organizations="organizations"
                        :projects="projects"
                    />
                </CardContent>
            </Card>

            <div class="sticky bottom-0 -mx-4 border-t border-border bg-background/95 px-4 py-4 backdrop-blur supports-[backdrop-filter]:bg-background/80">
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Button variant="outline" as-child class="sm:min-w-28">
                        <Link href="/archive">{{ t('Cancel') }}</Link>
                    </Button>
                    <Button type="submit" :disabled="processing" class="sm:min-w-40">
                        {{ t('Register document') }}
                    </Button>
                </div>
            </div>
        </Form>
    </div>
</template>
