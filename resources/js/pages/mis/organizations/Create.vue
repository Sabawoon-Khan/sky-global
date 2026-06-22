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
import { useMisPage } from '@/composables/useMisPage';

interface OrganizationType {
    id: number;
    name: string;
}

defineProps<{
    organizationTypes: OrganizationType[];
    provinces: string[];
}>();

const { t } = useMisPage();

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
    <Head :title="t('Add Organization')" />

    <MisPage narrow>
        <div class="flex items-center justify-between gap-3">
            <Heading
                :title="t('Add Organization')"
                :description="
                    t(
                        'Register a client, partner, or procurement body with full details',
                    )
                "
            />
            <Button variant="outline" as-child>
                <Link href="/organizations">{{ t('Cancel') }}</Link>
            </Button>
        </div>

        <Form
            v-bind="OrganizationController.store.form()"
            class="space-y-6"
            :options="{ forceFormData: true }"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Basic information') }}</CardTitle>
                    <CardDescription>
                        {{ t('Name, type, and registration details') }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="name">{{ t('Organization name') }} *</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            placeholder="e.g. Ministry of Interior"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="organization_type_id">{{ t('Organization type') }} *</Label>
                        <select
                            id="organization_type_id"
                            name="organization_type_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="" disabled selected>{{ t('Select type') }}</option>
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
                        <Label for="tax_id">{{ t('Tax / registration ID') }}</Label>
                        <Input
                            id="tax_id"
                            name="tax_id"
                            :placeholder="t('Optional')"
                        />
                        <InputError :message="errors.tax_id" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Location & contact') }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="province">{{ t('Province') }}</Label>
                        <select
                            id="province"
                            name="province"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">{{ t('Select province') }}</option>
                            <option v-for="province in provinces" :key="province" :value="province">
                                {{ province }}
                            </option>
                        </select>
                        <InputError :message="errors.province" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">{{ t('Phone') }}</Label>
                        <Input id="phone" name="phone" type="tel" placeholder="+93 ..." />
                        <InputError :message="errors.phone" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="email">{{ t('Email') }}</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            placeholder="contact@organization.af"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="address">{{ t('Full address') }}</Label>
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
                    <CardTitle>{{ t('Notes') }}</CardTitle>
                    <CardDescription>
                        {{ t('Internal notes about this organization') }}
                    </CardDescription>
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

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Attachment') }}</CardTitle>
                    <CardDescription>
                        {{ t('Upload a related document if available') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <OptionalAttachmentField :error="errors.attachment" />
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/organizations">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ t('Save organization') }}
                </Button>
            </div>
        </Form>
    </MisPage>
</template>
