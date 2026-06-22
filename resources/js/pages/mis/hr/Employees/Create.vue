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
import EmployeeController from '@/actions/App/Http/Controllers/Hr/EmployeeController';
import { useMisPage } from '@/composables/useMisPage';

interface Department {
    id: number;
    name: string;
}

defineProps<{
    departments: Department[];
}>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Employees', href: '/hr/employees' },
            { title: 'Create', href: '/hr/employees/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Add Employee')" />

    <MisPage narrow>
        <div class="flex items-center justify-between gap-3">
            <Heading
                :title="t('Add Employee')"
                :description="t('Register a new staff member')"
            />
            <Button variant="outline" as-child>
                <Link href="/hr/employees">{{ t('Cancel') }}</Link>
            </Button>
        </div>

        <Form
            v-bind="EmployeeController.store.form()"
            class="space-y-6"
            :options="{ forceFormData: true }"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Personal details') }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="first_name">{{ t('First name') }} *</Label>
                        <Input id="first_name" name="first_name" required />
                        <InputError :message="errors.first_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="last_name">{{ t('Last name') }} *</Label>
                        <Input id="last_name" name="last_name" required />
                        <InputError :message="errors.last_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="father_name">{{ t("Father's name") }}</Label>
                        <Input id="father_name" name="father_name" />
                        <InputError :message="errors.father_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="tazkira_number">{{ t('Tazkira number') }}</Label>
                        <Input id="tazkira_number" name="tazkira_number" />
                        <InputError :message="errors.tazkira_number" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="date_of_birth">{{ t('Date of birth') }}</Label>
                        <Input
                            id="date_of_birth"
                            name="date_of_birth"
                            type="date"
                        />
                        <InputError :message="errors.date_of_birth" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="gender">{{ t('Gender') }}</Label>
                        <select
                            id="gender"
                            name="gender"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option value="">{{ t('Select gender') }}</option>
                            <option value="male">{{ t('Male') }}</option>
                            <option value="female">{{ t('Female') }}</option>
                            <option value="other">{{ t('Other') }}</option>
                        </select>
                        <InputError :message="errors.gender" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="phone">{{ t('Phone') }}</Label>
                        <Input id="phone" name="phone" type="tel" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">{{ t('Email') }}</Label>
                        <Input id="email" name="email" type="email" />
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="current_address">{{ t('Current address') }}</Label>
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
                    <CardTitle>{{ t('Job details') }}</CardTitle>
                    <CardDescription>{{ t('Optional employment information') }}</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="job_detail_department_id">{{ t('Department') }}</Label>
                        <select
                            id="job_detail_department_id"
                            name="job_detail[department_id]"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option value="">{{ t('Select department') }}</option>
                            <option
                                v-for="dept in departments"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ dept.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="job_detail_designation">{{ t('Designation') }}</Label>
                        <Input
                            id="job_detail_designation"
                            name="job_detail[designation]"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="job_detail_hire_date">{{ t('Hire date') }}</Label>
                        <Input
                            id="job_detail_hire_date"
                            name="job_detail[hire_date]"
                            type="date"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Documents') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <OptionalAttachmentField :error="errors.attachment" />
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/hr/employees">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">{{ t('Save employee') }}</Button>
            </div>
        </Form>
    </MisPage>
</template>
