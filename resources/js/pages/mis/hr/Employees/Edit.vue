<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import EmployeeSalariesField, {
    type SalaryRecord,
} from '@/components/EmployeeSalariesField.vue';
import InputError from '@/components/InputError.vue';
import MisPage from '@/components/MisPage.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import PersonnelFormsField, {
    type AttachmentTypeOption,
} from '@/components/PersonnelFormsField.vue';
import PersonnelFormsCard, {
    type PersonnelFormRecord,
} from '@/components/PersonnelFormsCard.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
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

interface JobDetail {
    department_id?: number | null;
    department?: { id: number } | null;
    designation?: string | null;
    hire_date?: string | null;
    salary_grade?: string | null;
}

interface Employee {
    id: number;
    first_name: string;
    last_name: string;
    father_name?: string | null;
    tazkira_number?: string | null;
    date_of_birth?: string | null;
    gender?: string | null;
    phone?: string | null;
    email?: string | null;
    current_address?: string | null;
    status: string;
    is_permanent?: boolean;
    job_detail?: JobDetail | null;
    salaries?: SalaryRecord[];
    personnel_attachments?: PersonnelFormRecord[];
}

const props = defineProps<{
    employee: Employee;
    departments: Department[];
    currencies: string[];
    attachmentTypes: AttachmentTypeOption[];
}>();

const { t } = useMisPage();

const isPermanent = ref(props.employee.is_permanent ?? false);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Employees', href: '/hr/employees' },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head
        :title="
            t('Edit :name', {
                name: `${employee.first_name} ${employee.last_name}`,
            })
        "
    />

    <MisPage narrow>
        <div class="flex justify-end">
            <Button variant="outline" as-child>
                <Link :href="`/hr/employees/${employee.id}`">{{ t('Cancel') }}</Link>
            </Button>
        </div>

        <Form
            v-bind="EmployeeController.update.form(employee.id)"
            class="space-y-6"
            :options="{ forceFormData: true }"
            validate-files
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="salaries_synced" value="1" />
            <Card>
                <CardHeader>
                    <CardTitle>Personal details</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="first_name">First name *</Label>
                        <Input
                            id="first_name"
                            name="first_name"
                            required
                            :default-value="employee.first_name"
                        />
                        <InputError :message="errors.first_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="last_name">Last name *</Label>
                        <Input
                            id="last_name"
                            name="last_name"
                            required
                            :default-value="employee.last_name"
                        />
                        <InputError :message="errors.last_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="father_name">Father's name</Label>
                        <Input
                            id="father_name"
                            name="father_name"
                            :default-value="employee.father_name ?? ''"
                        />
                        <InputError :message="errors.father_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="tazkira_number">Tazkira number</Label>
                        <Input
                            id="tazkira_number"
                            name="tazkira_number"
                            :default-value="employee.tazkira_number ?? ''"
                        />
                        <InputError :message="errors.tazkira_number" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="date_of_birth">Date of birth</Label>
                        <Input
                            id="date_of_birth"
                            name="date_of_birth"
                            type="date"
                            :default-value="employee.date_of_birth ?? ''"
                        />
                        <InputError :message="errors.date_of_birth" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="gender">Gender</Label>
                        <select
                            id="gender"
                            name="gender"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option value="">Select gender</option>
                            <option
                                value="male"
                                :selected="employee.gender === 'male'"
                            >
                                Male
                            </option>
                            <option
                                value="female"
                                :selected="employee.gender === 'female'"
                            >
                                Female
                            </option>
                            <option
                                value="other"
                                :selected="employee.gender === 'other'"
                            >
                                Other
                            </option>
                        </select>
                        <InputError :message="errors.gender" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input
                            id="phone"
                            name="phone"
                            type="tel"
                            :default-value="employee.phone ?? ''"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            :default-value="employee.email ?? ''"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            name="status"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option
                                value="active"
                                :selected="employee.status === 'active'"
                            >
                                Active
                            </option>
                            <option
                                value="inactive"
                                :selected="employee.status === 'inactive'"
                            >
                                Inactive
                            </option>
                            <option
                                value="terminated"
                                :selected="employee.status === 'terminated'"
                            >
                                Terminated
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="current_address">Current address</Label>
                        <textarea
                            id="current_address"
                            name="current_address"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        >{{ employee.current_address ?? '' }}</textarea>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Job details</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="job_detail_department_id">Department</Label>
                        <select
                            id="job_detail_department_id"
                            name="job_detail[department_id]"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option value="">Select department</option>
                            <option
                                v-for="dept in departments"
                                :key="dept.id"
                                :value="dept.id"
                                :selected="
                                    dept.id ===
                                    (employee.job_detail?.department_id ??
                                        employee.job_detail?.department?.id)
                                "
                            >
                                {{ dept.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="job_detail_designation">Designation</Label>
                        <Input
                            id="job_detail_designation"
                            name="job_detail[designation]"
                            :default-value="employee.job_detail?.designation ?? ''"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="job_detail_hire_date">Hire date</Label>
                        <Input
                            id="job_detail_hire_date"
                            name="job_detail[hire_date]"
                            type="date"
                            :default-value="employee.job_detail?.hire_date ?? ''"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="job_detail_salary_grade">{{ t('Salary grade') }}</Label>
                        <Input
                            id="job_detail_salary_grade"
                            name="job_detail[salary_grade]"
                            :default-value="employee.job_detail?.salary_grade ?? ''"
                        />
                    </div>
                    <div class="rounded-lg border bg-muted/20 p-4 md:col-span-2">
                        <input type="hidden" name="is_permanent" value="0" />
                        <div class="flex items-start gap-3">
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
                                    Permanent staff
                                </Label>
                                <p class="text-sm text-muted-foreground">
                                    Office-based employee included in general attendance, not assigned to projects.
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="isPermanent">
                <CardHeader>
                    <CardTitle>{{ t('Salary') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <EmployeeSalariesField
                        :currencies="currencies"
                        :initial-salaries="employee.salaries"
                        :errors="errors"
                    />
                </CardContent>
            </Card>

            <Card v-else>
                <CardHeader>
                    <CardTitle>{{ t('Project pay') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">
                        {{
                            t(
                                'Project-based employees are paid through their project assignment. Assign them to a project and set the monthly rate there.',
                            )
                        }}
                    </p>
                </CardContent>
            </Card>

            <PersonnelFormsCard
                personnel-type="employee"
                :personnel-id="employee.id"
                :forms="employee.personnel_attachments ?? []"
                :attachment-types="attachmentTypes"
                :can-manage="true"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Employee forms</CardTitle>
                </CardHeader>
                <CardContent>
                    <PersonnelFormsField
                        :attachment-types="attachmentTypes"
                        :errors="errors"
                    />
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
                    <Link :href="`/hr/employees/${employee.id}`">Cancel</Link>
                </Button>
                <Button type="submit" :disabled="processing">Save changes</Button>
            </div>
        </Form>
    </MisPage>
</template>
