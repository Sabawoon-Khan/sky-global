<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import ProcurementOpportunityController from '@/actions/App/Http/Controllers/Procurement/ProcurementOpportunityController';

interface Organization {
    id: number;
    name: string;
}

defineProps<{
    organizations: Organization[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Opportunities', href: '/bidding/opportunities' },
            { title: 'Create', href: '/bidding/opportunities/create' },
        ],
    },
});
</script>

<template>
    <Head title="New Opportunity" />

    <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="New Procurement Opportunity"
                description="Record an RFP or tender before preparing your bid"
            />
            <Button variant="outline" as-child>
                <Link href="/bidding/opportunities">Cancel</Link>
            </Button>
        </div>

        <Form
            v-bind="ProcurementOpportunityController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Opportunity details</CardTitle>
                    <CardDescription>Link to the organization posting the request</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="organization_id">Organization *</Label>
                        <select
                            id="organization_id"
                            name="organization_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="" disabled selected>Select organization</option>
                            <option
                                v-for="org in organizations"
                                :key="org.id"
                                :value="org.id"
                            >
                                {{ org.name }}
                            </option>
                        </select>
                        <InputError :message="errors.organization_id" />
                        <p class="text-xs text-muted-foreground">
                            No organization?
                            <Link href="/organizations/create" class="underline">Add one first</Link>
                        </p>
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="title">Title *</Label>
                        <Input id="title" name="title" required placeholder="Static guard services for Kabul office" />
                        <InputError :message="errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="reference_number">Reference number</Label>
                        <Input id="reference_number" name="reference_number" placeholder="RFP-2026-001" />
                        <InputError :message="errors.reference_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="source">Source</Label>
                        <Input id="source" name="source" placeholder="Direct invite, portal, referral..." />
                        <InputError :message="errors.source" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                            placeholder="Scope summary, requirements, special conditions..."
                        />
                        <InputError :message="errors.description" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Schedule & value</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="published_at">Published date</Label>
                        <Input id="published_at" name="published_at" type="date" />
                        <InputError :message="errors.published_at" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="submission_deadline">Submission deadline</Label>
                        <Input id="submission_deadline" name="submission_deadline" type="date" />
                        <InputError :message="errors.submission_deadline" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="estimated_value">Estimated value</Label>
                        <Input id="estimated_value" name="estimated_value" type="number" min="0" step="0.01" />
                        <InputError :message="errors.estimated_value" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="currency">Currency</Label>
                        <select
                            id="currency"
                            name="currency"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="USD" selected>USD</option>
                            <option value="AFN">AFN</option>
                        </select>
                        <InputError :message="errors.currency" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="duration_months">Duration (months)</Label>
                        <Input id="duration_months" name="duration_months" type="number" min="1" />
                        <InputError :message="errors.duration_months" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            name="status"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="open" selected>Open</option>
                            <option value="closed">Closed</option>
                            <option value="awarded">Awarded</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <InputError :message="errors.status" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Security scope</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="security_scope">Scope type</Label>
                        <select
                            id="security_scope"
                            name="security_scope"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">Select scope</option>
                            <option value="static">Static guards</option>
                            <option value="mobile">Mobile patrol</option>
                            <option value="vip">VIP protection</option>
                            <option value="event">Event security</option>
                            <option value="escort">Escort services</option>
                            <option value="other">Other</option>
                        </select>
                        <InputError :message="errors.security_scope" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="location">Location</Label>
                        <Input id="location" name="location" placeholder="Kabul, Herat, etc." />
                        <InputError :message="errors.location" />
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/bidding/opportunities">Cancel</Link>
                </Button>
                <Button type="submit" :disabled="processing">Save opportunity</Button>
            </div>
        </Form>
    </div>
</template>
