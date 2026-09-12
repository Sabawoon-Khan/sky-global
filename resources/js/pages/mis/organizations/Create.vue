<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { V2FormPage, V2FormSection } from '@/components/v2';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import { Button } from '@/components/ui/button';
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

    <V2FormPage
        :title="t('Add Organization')"
        :eyebrow="t('Organizations')"
        back-href="/organizations"
    >
        <Form
            v-bind="OrganizationController.store.form()"
            class="space-y-6"
            :options="{ forceFormData: true }"
            validate-files
            v-slot="{ errors, processing }"
        >
            <V2FormSection :title="t('Basic information')">
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="name">{{ t('Organization name') }} *</Label>
                        <Input id="name" name="name" required />
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
                        <Input id="tax_id" name="tax_id" />
                        <InputError :message="errors.tax_id" />
                    </div>
                </div>
            </V2FormSection>

            <V2FormSection :title="t('Location & contact')">
                <div class="grid gap-4">
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
                        <Input id="phone" name="phone" type="tel" />
                        <InputError :message="errors.phone" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">{{ t('Email') }}</Label>
                        <Input id="email" name="email" type="email" />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">{{ t('Full address') }}</Label>
                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        />
                        <InputError :message="errors.address" />
                    </div>
                </div>
            </V2FormSection>

            <V2FormSection :title="t('Notes')">
                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                    />
                    <InputError :message="errors.notes" />
            </V2FormSection>

            <V2FormSection :title="t('Attachment')">
                <OptionalAttachmentField :error="errors.attachment" />
            </V2FormSection>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/organizations">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ t('Save organization') }}
                </Button>
            </div>
        </Form>
    </V2FormPage>
</template>
