<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

interface DocumentDefaults {
    title?: string;
    description?: string | null;
    direction?: string | null;
    document_category_id?: number | null;
    organization_id?: number | null;
    project_id?: number | null;
    document_date?: string | null;
    received_at?: string | null;
    sent_at?: string | null;
}

const props = withDefaults(
    defineProps<{
        errors: Record<string, string | undefined>;
        categories?: DocumentCategory[];
        organizations?: Option[];
        projects?: Option[];
        document?: DocumentDefaults;
        fileRequired?: boolean;
        showFileField?: boolean;
        fileLabel?: string;
        section?: 'all' | 'basic' | 'dates' | 'links' | 'file' | 'description';
    }>(),
    {
        section: 'all',
    },
);

const { t } = useMisPage();

const selectClass =
    'flex h-10 w-full rounded-xl border border-input bg-transparent px-3.5 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/30';

const dateValue = (value?: string | null): string | undefined =>
    value?.slice(0, 10) || undefined;

const show = (name: typeof props.section): boolean =>
    props.section === 'all' || props.section === name;
</script>

<template>
    <div :class="section === 'all' ? 'space-y-8' : ''">
        <div v-if="show('basic')" class="grid gap-4 md:grid-cols-3">
            <div class="grid gap-2 md:col-span-3">
                <Label for="doc-title">{{ t('Title') }} *</Label>
                <Input
                    id="doc-title"
                    name="title"
                    required
                    class="h-10 rounded-xl"
                    :default-value="document?.title"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="doc-direction">{{ t('Direction') }} *</Label>
                <select
                    id="doc-direction"
                    name="direction"
                    required
                    :class="selectClass"
                >
                    <option
                        value="incoming"
                        :selected="document?.direction === 'incoming'"
                    >
                        {{ t('Incoming') }}
                    </option>
                    <option
                        value="outgoing"
                        :selected="document?.direction === 'outgoing'"
                    >
                        {{ t('Outgoing') }}
                    </option>
                    <option
                        value="internal"
                        :selected="document?.direction === 'internal'"
                    >
                        {{ t('Internal') }}
                    </option>
                </select>
                <InputError :message="errors.direction" />
            </div>

            <div class="grid gap-2 md:col-span-2">
                <Label for="doc-category">{{ t('Category') }}</Label>
                <select
                    id="doc-category"
                    name="document_category_id"
                    :class="selectClass"
                >
                    <option value="">{{ t('None') }}</option>
                    <option
                        v-for="cat in categories ?? []"
                        :key="cat.id"
                        :value="cat.id"
                        :selected="document?.document_category_id === cat.id"
                    >
                        {{ cat.name }}
                    </option>
                </select>
                <InputError :message="errors.document_category_id" />
            </div>
        </div>

        <div v-if="show('dates')" class="grid gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="doc-date">{{ t('Document date') }}</Label>
                <Input
                    id="doc-date"
                    name="document_date"
                    type="date"
                    class="h-10 rounded-xl"
                    :default-value="dateValue(document?.document_date)"
                />
                <InputError :message="errors.document_date" />
            </div>

            <div class="grid gap-2">
                <Label for="doc-received">{{ t('Received') }}</Label>
                <Input
                    id="doc-received"
                    name="received_at"
                    type="date"
                    class="h-10 rounded-xl"
                    :default-value="dateValue(document?.received_at)"
                />
                <InputError :message="errors.received_at" />
            </div>

            <div class="grid gap-2">
                <Label for="doc-sent">{{ t('Sent') }}</Label>
                <Input
                    id="doc-sent"
                    name="sent_at"
                    type="date"
                    class="h-10 rounded-xl"
                    :default-value="dateValue(document?.sent_at)"
                />
                <InputError :message="errors.sent_at" />
            </div>
        </div>

        <div v-if="show('links')" class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="doc-org">{{ t('Organization') }}</Label>
                <select
                    id="doc-org"
                    name="organization_id"
                    :class="selectClass"
                >
                    <option value="">{{ t('None') }}</option>
                    <option
                        v-for="org in organizations ?? []"
                        :key="org.id"
                        :value="org.id"
                        :selected="document?.organization_id === org.id"
                    >
                        {{ org.name }}
                    </option>
                </select>
                <InputError :message="errors.organization_id" />
            </div>

            <div class="grid gap-2">
                <Label for="doc-project">{{ t('Project') }}</Label>
                <select
                    id="doc-project"
                    name="project_id"
                    :class="selectClass"
                >
                    <option value="">{{ t('None') }}</option>
                    <option
                        v-for="proj in projects ?? []"
                        :key="proj.id"
                        :value="proj.id"
                        :selected="document?.project_id === proj.id"
                    >
                        {{ proj.code }} — {{ proj.name }}
                    </option>
                </select>
                <InputError :message="errors.project_id" />
            </div>
        </div>

        <div v-if="show('file') && showFileField !== false" class="grid gap-2">
            <Label for="doc-file">
                {{ fileLabel ?? t('File') }}
                <span v-if="fileRequired"> *</span>
            </Label>
            <Input
                id="doc-file"
                name="file"
                type="file"
                class="h-10 rounded-xl file:me-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1 file:text-sm"
                :required="fileRequired"
            />
            <InputError :message="errors.file" />
        </div>

        <div v-if="show('description')" class="grid gap-2">
            <Label for="doc-description">{{ t('Description') }}</Label>
            <RichTextEditor
                id="doc-description"
                name="description"
                :default-value="document?.description ?? ''"
                :placeholder="t('Description')"
                min-height="180px"
            />
            <InputError :message="errors.description" />
        </div>
    </div>
</template>
