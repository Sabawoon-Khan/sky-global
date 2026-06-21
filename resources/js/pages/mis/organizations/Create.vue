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
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';

interface OrganizationType {
    id: number;
    name: string;
}

defineProps<{
    organizationTypes: OrganizationType[];
    provinces: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Organizations', href: '/organizations' },
            { title: 'Create', href: '/organizations/create' },
        ],
    },
});
</script>

<template>
    <Head title="Add Organization" />

    <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="Add Organization"
                description="Register a client, partner, or procurement body with full details"
            />
            <Button variant="outline" as-child>
                <Link href="/organizations">Cancel</Link>
            </Button>
        </div>

        <Form
            v-bind="OrganizationController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Basic information</CardTitle>
                    <CardDescription>Name, type, and registration details</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="name">Organization name *</Label>
                        <Input id="name" name="name" required placeholder="e.g. Ministry of Interior" />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="organization_type_id">Organization type *</Label>
                        <select
                            id="organization_type_id"
                            name="organization_type_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="" disabled selected>Select type</option>
                            <option
                                v-for="type in organizationTypes"
                                :key="type.id"
                                :value="type.id"
                            >
                                {{ type.name }}
                            </option>
                        </select>
                        <InputError :message="errors.organization_type_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tax_id">Tax / registration ID</Label>
                        <Input id="tax_id" name="tax_id" placeholder="Optional" />
                        <InputError :message="errors.tax_id" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Location & contact</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="province">Province</Label>
                        <select
                            id="province"
                            name="province"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">Select province</option>
                            <option v-for="province in provinces" :key="province" :value="province">
                                {{ province }}
                            </option>
                        </select>
                        <InputError :message="errors.province" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input id="phone" name="phone" type="tel" placeholder="+93 ..." />
                        <InputError :message="errors.phone" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="email">Email</Label>
                        <Input id="email" name="email" type="email" placeholder="contact@organization.af" />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="address">Full address</Label>
                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                            placeholder="Street, district, city..."
                        />
                        <InputError :message="errors.address" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Notes</CardTitle>
                    <CardDescription>Internal notes about this organization</CardDescription>
                </CardHeader>
                <CardContent>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        placeholder="Relationship history, key contacts, preferences..."
                    />
                    <InputError :message="errors.notes" />
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/organizations">Cancel</Link>
                </Button>
                <Button type="submit" :disabled="processing">Save organization</Button>
            </div>
        </Form>
    </div>
</template>
