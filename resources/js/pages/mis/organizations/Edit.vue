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
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';

interface OrganizationType {
    id: number;
    name: string;
}

interface Organization {
    id: number;
    organization_type_id: number;
    name: string;
    tax_id: string | null;
    province: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    notes: string | null;
    is_active: boolean;
}

defineProps<{
    organization: Organization;
    organizationTypes: OrganizationType[];
    provinces: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Organizations', href: '/organizations' },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${organization.name}`" />

    <MisPage narrow>
        <div class="flex items-center justify-between gap-3">
            <Heading
                title="Edit Organization"
                :description="organization.name"
            />
            <Button variant="outline" as-child>
                <Link :href="`/organizations/${organization.id}`">Cancel</Link>
            </Button>
        </div>

        <Form
            v-bind="OrganizationController.update.form(organization.id)"
            class="space-y-6"
            :options="{ forceFormData: true }"
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
                        <Input
                            id="name"
                            name="name"
                            required
                            :default-value="organization.name"
                        />
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
                            <option
                                v-for="type in organizationTypes"
                                :key="type.id"
                                :value="type.id"
                                :selected="type.id === organization.organization_type_id"
                            >
                                {{ type.name }}
                            </option>
                        </select>
                        <InputError :message="errors.organization_type_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tax_id">Tax / registration ID</Label>
                        <Input
                            id="tax_id"
                            name="tax_id"
                            :default-value="organization.tax_id ?? ''"
                        />
                        <InputError :message="errors.tax_id" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="is_active">Status</Label>
                        <select
                            id="is_active"
                            name="is_active"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option :value="1" :selected="organization.is_active">
                                Active
                            </option>
                            <option :value="0" :selected="!organization.is_active">
                                Inactive
                            </option>
                        </select>
                        <InputError :message="errors.is_active" />
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
                            <option
                                v-for="province in provinces"
                                :key="province"
                                :value="province"
                                :selected="province === organization.province"
                            >
                                {{ province }}
                            </option>
                        </select>
                        <InputError :message="errors.province" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input
                            id="phone"
                            name="phone"
                            type="tel"
                            :default-value="organization.phone ?? ''"
                        />
                        <InputError :message="errors.phone" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            :default-value="organization.email ?? ''"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="address">Full address</Label>
                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        >{{ organization.address ?? '' }}</textarea>
                        <InputError :message="errors.address" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Notes</CardTitle>
                </CardHeader>
                <CardContent>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                    >{{ organization.notes ?? '' }}</textarea>
                    <InputError :message="errors.notes" />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Attachment</CardTitle>
                    <CardDescription>Upload a new related document if needed</CardDescription>
                </CardHeader>
                <CardContent>
                    <OptionalAttachmentField :error="errors.attachment" />
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link :href="`/organizations/${organization.id}`">Cancel</Link>
                </Button>
                <Button type="submit" :disabled="processing">
                    Save changes
                </Button>
            </div>
        </Form>
    </MisPage>
</template>
