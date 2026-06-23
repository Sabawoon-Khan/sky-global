<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import MisPage from '@/components/MisPage.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
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

    <MisPage narrow>
        <div class="flex items-center justify-between gap-3">
            <Heading
                :title="t('New Project')"
                :description="
                    t(
                        'One record from proposal to delivery — bidding, win/loss, and operations stay together',
                    )
                "
            />
            <Button variant="outline" size="sm" as-child>
                <Link href="/projects">{{ t('Cancel') }}</Link>
            </Button>
        </div>

        <Form
            v-bind="ProjectController.store.form()"
            class="space-y-4"
            :options="{ forceFormData: true }"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base">{{ t('Client & project') }}</CardTitle>
                    <CardDescription>
                        {{ t('Select the organization and name this opportunity') }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-3 sm:grid-cols-2">
                    <div class="grid gap-1.5 sm:col-span-2">
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
                        <p class="text-xs text-muted-foreground">
                            <Can permission="bidding.create">
                                <Link href="/organizations/create" class="underline">{{
                                    t('Add organization')
                                }}</Link>
                                {{ t('if not listed') }}
                            </Can>
                        </p>
                    </div>

                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="name">{{ t('Project / opportunity title') }} *</Label>
                        <Input id="name" name="name" required placeholder="Static guard services — Kabul HQ" />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="reference_number">{{ t('Reference #') }}</Label>
                        <Input id="reference_number" name="reference_number" placeholder="RFP-2026-001" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="submission_deadline">{{ t('Submission deadline') }}</Label>
                        <Input id="submission_deadline" name="submission_deadline" type="date" />
                    </div>

                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="scope_summary">{{ t('Scope summary') }}</Label>
                        <textarea
                            id="scope_summary"
                            name="scope_summary"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                            placeholder="What security services are being requested?"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base">{{ t('Initial bid (optional now)') }}</CardTitle>
                    <CardDescription>
                        {{
                            t(
                                'You can fill in competitors and full pricing on the project page',
                            )
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-3 sm:grid-cols-3">
                    <div class="grid gap-1.5">
                        <Label for="our_bid_amount">{{ t('Our bid amount') }}</Label>
                        <Input id="our_bid_amount" name="our_bid_amount" type="number" min="0" step="0.01" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="currency">{{ t('Currency') }}</Label>
                        <select
                            id="currency"
                            name="currency"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option value="USD">USD</option>
                            <option value="AFN">AFN</option>
                        </select>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="security_scope">{{ t('Scope type') }}</Label>
                        <select
                            id="security_scope"
                            name="security_scope"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option value="">—</option>
                            <option value="static">{{ t('Static guards') }}</option>
                            <option value="mobile">{{ t('Mobile patrol') }}</option>
                            <option value="vip">{{ t('VIP') }}</option>
                            <option value="event">{{ t('Event') }}</option>
                        </select>
                    </div>
                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="location">{{ t('Location') }}</Label>
                        <Input id="location" name="location" placeholder="Kabul, Herat..." />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="source">{{ t('Source') }}</Label>
                        <Input id="source" name="source" placeholder="Direct invite, portal..." />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base">{{ t('Attachment') }}</CardTitle>
                    <CardDescription>
                        {{ t('RFP, scope document, or other supporting file') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <OptionalAttachmentField :error="errors.attachment" />
                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Button variant="outline" as-child>
                    <Link href="/projects">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">{{ t('Create project') }}</Button>
            </div>
        </Form>
    </MisPage>
</template>
