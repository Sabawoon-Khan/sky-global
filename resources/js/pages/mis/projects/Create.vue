<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import MisPage from '@/components/MisPage.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import SecurityScopeField from '@/components/SecurityScopeField.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ProjectController from '@/actions/App/Http/Controllers/Project/ProjectController';
import { useMisPage } from '@/composables/useMisPage';

interface Organization {
    id: number;
    name: string;
    province: string | null;
    organization_type?: { name: string } | null;
}

interface OrganizationType {
    id: number;
    name: string;
}

defineProps<{
    organizations: Organization[];
    organizationTypes: OrganizationType[];
}>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: '/projects' },
            { title: 'New', href: '/projects/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('New Project')" />

    <MisPage>
        <Form
            v-bind="ProjectController.store.form()"
            class="space-y-6"
            :options="{ forceFormData: true }"
            validate-files
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Client & project') }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="organization_id">{{ t('Organization') }} *</Label>
                        <select
                            id="organization_id"
                            name="organization_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option value="" disabled selected>
                                {{ t('Select organization') }}
                            </option>
                            <option v-for="org in organizations" :key="org.id" :value="org.id">
                                {{ org.name }}
                                <template v-if="org.province"> — {{ org.province }}</template>
                            </option>
                        </select>
                        <InputError :message="errors.organization_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">{{ t('Project / opportunity title') }} *</Label>
                        <Input id="name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="reference_number">{{ t('Reference #') }}</Label>
                        <Input id="reference_number" name="reference_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="submission_deadline">{{ t('Submission deadline') }}</Label>
                        <Input id="submission_deadline" name="submission_deadline" type="date" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="scope_summary">{{ t('Scope summary') }}</Label>
                        <textarea
                            id="scope_summary"
                            name="scope_summary"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Initial bid (optional now)') }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="our_bid_amount">{{ t('Our bid amount (AFN)') }}</Label>
                        <Input id="our_bid_amount" name="our_bid_amount" type="number" min="0" step="0.01" />
                        <input type="hidden" name="currency" value="AFN" />
                    </div>
                    <SecurityScopeField :error="errors.security_scope" />
                    <div class="grid gap-2">
                        <Label for="location">{{ t('Location') }}</Label>
                        <Input id="location" name="location" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="source">{{ t('Source') }}</Label>
                        <Input id="source" name="source" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Attachment') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <OptionalAttachmentField :error="errors.attachment" />
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/projects">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">{{ t('Create project') }}</Button>
            </div>
        </Form>
    </MisPage>
</template>
