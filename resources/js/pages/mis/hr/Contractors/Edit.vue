<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ContractorAgreementsField, {
    type AgreementRecord,
} from '@/components/ContractorAgreementsField.vue';
import ContractorRatesField, {
    type ProjectOption,
    type RateRecord,
} from '@/components/ContractorRatesField.vue';
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
import ContractorController from '@/actions/App/Http/Controllers/Hr/ContractorController';
import { useMisPage } from '@/composables/useMisPage';

interface Contractor {
    id: number;
    first_name: string;
    last_name: string;
    father_name?: string | null;
    original_address?: string | null;
    tazkira_number?: string | null;
    date_of_birth?: string | null;
    gender?: string | null;
    phone?: string | null;
    email?: string | null;
    current_address?: string | null;
    status: string;
    agreements?: AgreementRecord[];
    rates?: RateRecord[];
    personnel_attachments?: PersonnelFormRecord[];
}

defineProps<{
    contractor: Contractor;
    projects: ProjectOption[];
    currencies: string[];
    attachmentTypes: AttachmentTypeOption[];
}>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Contractors', href: '/hr/contractors' },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head
        :title="
            t('Edit :name', {
                name: `${contractor.first_name} ${contractor.last_name}`,
            })
        "
    />

    <MisPage narrow>
        <div class="flex justify-end">
            <Button variant="outline" as-child>
                <Link :href="`/hr/contractors/${contractor.id}`">{{ t('Cancel') }}</Link>
            </Button>
        </div>

        <Form
            v-bind="ContractorController.update.form(contractor.id)"
            class="space-y-6"
            :options="{ forceFormData: true }"
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="agreements_synced" value="1" />
            <input type="hidden" name="rates_synced" value="1" />

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Personal details') }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="first_name">{{ t('First name') }} *</Label>
                        <Input
                            id="first_name"
                            name="first_name"
                            required
                            :default-value="contractor.first_name"
                        />
                        <InputError :message="errors.first_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="last_name">{{ t('Last name') }} *</Label>
                        <Input
                            id="last_name"
                            name="last_name"
                            required
                            :default-value="contractor.last_name"
                        />
                        <InputError :message="errors.last_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="father_name">{{ t("Father's name") }}</Label>
                        <Input
                            id="father_name"
                            name="father_name"
                            :default-value="contractor.father_name ?? ''"
                        />
                        <InputError :message="errors.father_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="tazkira_number">{{ t('Tazkira number') }}</Label>
                        <Input
                            id="tazkira_number"
                            name="tazkira_number"
                            :default-value="contractor.tazkira_number ?? ''"
                        />
                        <InputError :message="errors.tazkira_number" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="date_of_birth">{{ t('Date of birth') }}</Label>
                        <Input
                            id="date_of_birth"
                            name="date_of_birth"
                            type="date"
                            :default-value="contractor.date_of_birth ?? ''"
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
                            <option
                                value="male"
                                :selected="contractor.gender === 'male'"
                            >
                                {{ t('Male') }}
                            </option>
                            <option
                                value="female"
                                :selected="contractor.gender === 'female'"
                            >
                                {{ t('Female') }}
                            </option>
                            <option
                                value="other"
                                :selected="contractor.gender === 'other'"
                            >
                                {{ t('Other') }}
                            </option>
                        </select>
                        <InputError :message="errors.gender" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="phone">{{ t('Phone') }}</Label>
                        <Input
                            id="phone"
                            name="phone"
                            type="tel"
                            :default-value="contractor.phone ?? ''"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">{{ t('Email') }}</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            :default-value="contractor.email ?? ''"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="status">{{ t('Status') }}</Label>
                        <select
                            id="status"
                            name="status"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option
                                value="active"
                                :selected="contractor.status === 'active'"
                            >
                                {{ t('Active') }}
                            </option>
                            <option
                                value="inactive"
                                :selected="contractor.status === 'inactive'"
                            >
                                {{ t('Inactive') }}
                            </option>
                            <option
                                value="terminated"
                                :selected="contractor.status === 'terminated'"
                            >
                                {{ t('Terminated') }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="original_address">{{ t('Original address') }}</Label>
                        <textarea
                            id="original_address"
                            name="original_address"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        >{{ contractor.original_address ?? '' }}</textarea>
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="current_address">{{ t('Current address') }}</Label>
                        <textarea
                            id="current_address"
                            name="current_address"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        >{{ contractor.current_address ?? '' }}</textarea>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Agreements') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <ContractorAgreementsField
                        :initial-agreements="contractor.agreements"
                        :errors="errors"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Rates') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <ContractorRatesField
                        :projects="projects"
                        :currencies="currencies"
                        :initial-rates="contractor.rates"
                        :errors="errors"
                    />
                </CardContent>
            </Card>

            <PersonnelFormsCard
                personnel-type="contractor"
                :personnel-id="contractor.id"
                :forms="contractor.personnel_attachments ?? []"
                :attachment-types="attachmentTypes"
                :can-manage="true"
            />

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Contractor forms') }}</CardTitle>
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
                    <CardTitle>{{ t('Documents') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <OptionalAttachmentField :error="errors.attachment" />
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link :href="`/hr/contractors/${contractor.id}`">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">{{ t('Save changes') }}</Button>
            </div>
        </Form>
    </MisPage>
</template>
