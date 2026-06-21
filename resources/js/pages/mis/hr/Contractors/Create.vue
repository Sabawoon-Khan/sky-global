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
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ContractorController from '@/actions/App/Http/Controllers/Hr/ContractorController';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Contractors', href: '/hr/contractors' },
            { title: 'Create', href: '/hr/contractors/create' },
        ],
    },
});
</script>

<template>
    <Head title="Add Contractor" />

    <MisPage narrow>
        <div class="flex items-center justify-between gap-3">
            <Heading
                title="Add Contractor"
                description="Register a new contractor"
            />
            <Button variant="outline" as-child>
                <Link href="/hr/contractors">Cancel</Link>
            </Button>
        </div>

        <Form
            v-bind="ContractorController.store.form()"
            class="space-y-6"
            :options="{ forceFormData: true }"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Personal details</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="first_name">First name *</Label>
                        <Input id="first_name" name="first_name" required />
                        <InputError :message="errors.first_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="last_name">Last name *</Label>
                        <Input id="last_name" name="last_name" required />
                        <InputError :message="errors.last_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input id="phone" name="phone" type="tel" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" name="email" type="email" />
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="current_address">Current address</Label>
                        <textarea
                            id="current_address"
                            name="current_address"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Documents</CardTitle>
                </CardHeader>
                <CardContent>
                    <OptionalAttachmentField :error="errors.attachment" />
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/hr/contractors">Cancel</Link>
                </Button>
                <Button type="submit" :disabled="processing">Save contractor</Button>
            </div>
        </Form>
    </MisPage>
</template>
