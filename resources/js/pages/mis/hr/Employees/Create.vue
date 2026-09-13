<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import EmployeeSalariesField from '@/components/EmployeeSalariesField.vue';
import InputError from '@/components/InputError.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import PersonnelFormsField, {
    type AttachmentTypeOption,
} from '@/components/PersonnelFormsField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { V2FormPage, V2FormSection } from '@/components/v2';
import EmployeeController from '@/actions/App/Http/Controllers/Hr/EmployeeController';
import { useMisPage } from '@/composables/useMisPage';

interface Department {
    id: number;
    name: string;
}

defineProps<{
    departments: Department[];
    currencies: string[];
    attachmentTypes: AttachmentTypeOption[];
}>();

const { t } = useMisPage();

const isPermanent = ref(false);

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

    <V2FormPage
        :title="t('Add Employee')"
        :eyebrow="t('HR')"
        back-href="/hr/employees"
    >
        <Form
            v-bind="EmployeeController.store.form()"
            class="space-y-5"
            :options="{ forceFormData: true }"
            validate-files
            v-slot="{ errors, processing }"
        >
            <input v-if="isPermanent" type="hidden" name="salaries_synced" value="1" />

            <V2FormSection :title="t('Personal details')">
                <div class="mis-form-grid">
                    <div class="v2-field">
                        <Label for="first_name">{{ t('First name') }} *</Label>
                        <Input id="first_name" name="first_name" required />
                        <InputError :message="errors.first_name" />
                    </div>
                    <div class="v2-field">
                        <Label for="last_name">{{ t('Last name') }} *</Label>
                        <Input id="last_name" name="last_name" required />
                        <InputError :message="errors.last_name" />
                    </div>
                    <div class="v2-field">
                        <Label for="father_name">{{ t("Father's name") }}</Label>
                        <Input id="father_name" name="father_name" />
                        <InputError :message="errors.father_name" />
                    </div>
                    <div class="v2-field">
                        <Label for="tazkira_number">{{ t('Tazkira number') }}</Label>
                        <Input id="tazkira_number" name="tazkira_number" />
                        <InputError :message="errors.tazkira_number" />
                    </div>
                    <div class="v2-field">
                        <Label for="date_of_birth">{{ t('Date of birth') }}</Label>
                        <Input
                            id="date_of_birth"
                            name="date_of_birth"
                            type="date"
                        />
                        <InputError :message="errors.date_of_birth" />
                    </div>
                    <div class="v2-field">
                        <Label for="gender">{{ t('Gender') }}</Label>
                        <select
                            id="gender"
                            name="gender"
                            class="mis-form-select"
                        >
                            <option value="">{{ t('Select gender') }}</option>
                            <option value="male">{{ t('Male') }}</option>
                            <option value="female">{{ t('Female') }}</option>
                            <option value="other">{{ t('Other') }}</option>
                        </select>
                        <InputError :message="errors.gender" />
                    </div>
                    <div class="v2-field">
                        <Label for="phone">{{ t('Phone') }}</Label>
                        <Input id="phone" name="phone" type="tel" />
                    </div>
                    <div class="v2-field">
                        <Label for="email">{{ t('Email') }}</Label>
                        <Input id="email" name="email" type="email" />
                    </div>
                    <div class="v2-field mis-form-span">
                        <Label for="current_address">{{ t('Current address') }}</Label>
                        <textarea
                            id="current_address"
                            name="current_address"
                            rows="2"
                            class="mis-form-textarea"
                        />
                    </div>
                </div>
            </V2FormSection>

            <V2FormSection :title="t('Job details')">
                <div class="mis-form-grid">
                    <div class="v2-field">
                        <Label for="job_detail_department_id">{{ t('Department') }}</Label>
                        <select
                            id="job_detail_department_id"
                            name="job_detail[department_id]"
                            class="mis-form-select"
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
                    <div class="v2-field">
                        <Label for="job_detail_designation">{{ t('Designation') }}</Label>
                        <Input
                            id="job_detail_designation"
                            name="job_detail[designation]"
                        />
                    </div>
                    <div class="v2-field">
                        <Label for="job_detail_hire_date">{{ t('Hire date') }}</Label>
                        <Input
                            id="job_detail_hire_date"
                            name="job_detail[hire_date]"
                            type="date"
                        />
                    </div>
                    <div class="v2-field">
                        <Label for="job_detail_salary_grade">{{ t('Salary grade') }}</Label>
                        <Input
                            id="job_detail_salary_grade"
                            name="job_detail[salary_grade]"
                        />
                    </div>
                    <div class="v2-field mis-form-span">
                        <input type="hidden" name="is_permanent" value="0" />
                        <div class="flex items-start gap-3 rounded-2xl border border-border/70 bg-muted/20 p-4">
                            <input
                                id="is_permanent"
                                v-model="isPermanent"
                                name="is_permanent"
                                type="checkbox"
                                value="1"
                                class="mt-1 size-4 rounded border border-input"
                            />
                            <div class="space-y-1">
                                <Label for="is_permanent" class="cursor-pointer font-medium">
                                    {{ t('Permanent staff') }}
                                </Label>
                            </div>
                        </div>
                    </div>
                </div>
            </V2FormSection>

            <V2FormSection v-if="isPermanent" :title="t('Salary')">
                <EmployeeSalariesField
                    :currencies="currencies"
                    :errors="errors"
                />
            </V2FormSection>

            <V2FormSection :title="t('Employee forms')">
                <PersonnelFormsField
                    :attachment-types="attachmentTypes"
                    :errors="errors"
                />
            </V2FormSection>

            <V2FormSection :title="t('Documents')">
                <OptionalAttachmentField :error="errors.attachment" />
            </V2FormSection>

            <div class="mis-form-actions">
                <Button variant="outline" as-child>
                    <Link href="/hr/employees">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">{{ t('Save employee') }}</Button>
            </div>
        </Form>
    </V2FormPage>
</template>
