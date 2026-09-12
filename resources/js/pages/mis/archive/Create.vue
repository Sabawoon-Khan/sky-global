<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ArchiveDocumentFields from '@/components/archive/ArchiveDocumentFields.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import { V2FormPage, V2FormSection } from '@/components/v2';
import { Button } from '@/components/ui/button';
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

    <V2FormPage
        :title="t('Register new document')"
        :description="t('Upload a file and record its details')"
        :eyebrow="t('Archive')"
        back-href="/archive"
    >
        <Form
            action="/archive"
            method="post"
            class="space-y-6"
            :options="{ forceFormData: true }"
            validate-files
            v-slot="{ errors, processing }"
        >
            <V2FormSection
                :title="t('Basic information')"
                :description="t('Title, direction, and document category')"
            >
                <ArchiveDocumentFields
                    fields-section="basic"
                    :errors="errors"
                    :categories="categories"
                    :organizations="organizations"
                    :projects="projects"
                />
            </V2FormSection>

            <V2FormSection
                :title="t('Dates')"
                :description="t('Document date, received, and sent dates')"
            >
                <ArchiveDocumentFields
                    fields-section="dates"
                    :errors="errors"
                    :categories="categories"
                    :organizations="organizations"
                    :projects="projects"
                />
            </V2FormSection>

            <V2FormSection
                :title="t('Linked To')"
                :description="
                    t('Connect this document to an organization or project')
                "
            >
                <ArchiveDocumentFields
                    fields-section="links"
                    :errors="errors"
                    :categories="categories"
                    :organizations="organizations"
                    :projects="projects"
                />
            </V2FormSection>

            <V2FormSection
                :title="t('Attachment')"
                :description="t('Upload the document file')"
            >
                <OptionalAttachmentField
                    name="file"
                    :label="t('File')"
                    :error="errors.file"
                    required
                />
            </V2FormSection>

            <V2FormSection
                :title="t('Description')"
                :description="
                    t('Optional notes or summary about this document')
                "
            >
                <ArchiveDocumentFields
                    fields-section="description"
                    :errors="errors"
                    :categories="categories"
                    :organizations="organizations"
                    :projects="projects"
                />
            </V2FormSection>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Button variant="outline" as-child class="sm:min-w-28">
                    <Link href="/archive">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing" class="sm:min-w-40">
                    {{ t('Register document') }}
                </Button>
            </div>
        </Form>
    </V2FormPage>
</template>
