<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
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
    <Head title="New Project" />

    <MisPage narrow>
        <div class="flex items-center justify-between gap-3">
            <Heading
                title="New Project"
                description="One record from proposal to delivery — bidding, win/loss, and operations stay together"
            />
            <Button variant="outline" size="sm" as-child>
                <Link href="/projects">Cancel</Link>
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
                    <CardTitle class="text-base">Client & project</CardTitle>
                    <CardDescription>Select the organization and name this opportunity</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-3 sm:grid-cols-2">
                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="organization_id">Organization *</Label>
                        <select
                            id="organization_id"
                            name="organization_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option value="" disabled selected>Select organization</option>
                            <option v-for="org in organizations" :key="org.id" :value="org.id">
                                {{ org.name }}
                                <template v-if="org.province"> — {{ org.province }}</template>
                            </option>
                        </select>
                        <InputError :message="errors.organization_id" />
                        <p class="text-xs text-muted-foreground">
                            <Link href="/organizations/create" class="underline">Add organization</Link>
                            if not listed
                        </p>
                    </div>

                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="name">Project / opportunity title *</Label>
                        <Input id="name" name="name" required placeholder="Static guard services — Kabul HQ" />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="reference_number">Reference #</Label>
                        <Input id="reference_number" name="reference_number" placeholder="RFP-2026-001" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="submission_deadline">Submission deadline</Label>
                        <Input id="submission_deadline" name="submission_deadline" type="date" />
                    </div>

                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="scope_summary">Scope summary</Label>
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
                    <CardTitle class="text-base">Initial bid (optional now)</CardTitle>
                    <CardDescription>You can fill in competitors and full pricing on the project page</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-3 sm:grid-cols-3">
                    <div class="grid gap-1.5">
                        <Label for="our_bid_amount">Our bid amount</Label>
                        <Input id="our_bid_amount" name="our_bid_amount" type="number" min="0" step="0.01" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="currency">Currency</Label>
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
                        <Label for="security_scope">Scope type</Label>
                        <select
                            id="security_scope"
                            name="security_scope"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option value="">—</option>
                            <option value="static">Static guards</option>
                            <option value="mobile">Mobile patrol</option>
                            <option value="vip">VIP</option>
                            <option value="event">Event</option>
                        </select>
                    </div>
                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="location">Location</Label>
                        <Input id="location" name="location" placeholder="Kabul, Herat..." />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="source">Source</Label>
                        <Input id="source" name="source" placeholder="Direct invite, portal..." />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base">Attachment</CardTitle>
                    <CardDescription>RFP, scope document, or other supporting file</CardDescription>
                </CardHeader>
                <CardContent>
                    <OptionalAttachmentField :error="errors.attachment" />
                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Button variant="outline" as-child>
                    <Link href="/projects">Cancel</Link>
                </Button>
                <Button type="submit" :disabled="processing">Create project</Button>
            </div>
        </Form>
    </MisPage>
</template>
