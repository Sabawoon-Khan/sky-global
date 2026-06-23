<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Plus, Receipt, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import MisPagination from '@/components/MisPagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
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
import { Textarea } from '@/components/ui/textarea';
import { useMisPage } from '@/composables/useMisPage';
import type { Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';

const EMPLOYEE_TYPE = 'App\\Models\\Hr\\Employee';
const CONTRACTOR_TYPE = 'App\\Models\\Hr\\Contractor';

interface PersonOption {
    id: number;
    first_name: string;
    last_name: string;
}

interface ProjectOption {
    id: number;
    code: string;
    name: string;
}

interface AdjustmentTypeOption {
    value: string;
    label: string;
}

interface Personnel {
    first_name?: string;
    last_name?: string;
}

interface AdjustmentRecord {
    id: number;
    personnel_type: string;
    personnel_id: number;
    personnel?: Personnel | null;
    project?: { id: number; code: string; name?: string } | null;
    period_year: number;
    period_month: number;
    type: string;
    amount: number;
    notes?: string | null;
    applied_at?: string | null;
}

interface Props {
    adjustments: Paginated<AdjustmentRecord>;
    projects: ProjectOption[];
    employees: PersonOption[];
    contractors: PersonOption[];
    adjustmentTypes: AdjustmentTypeOption[];
    filters?: {
        year?: number;
        month?: number;
    };
}

const props = defineProps<Props>();

const { t, deleteAction } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Payroll Adjustments', href: '/hr/payroll-adjustments' },
        ],
    },
});

const personnelType = ref(EMPLOYEE_TYPE);
const bulkMode = ref(false);
const bulkEntries = ref<Record<number, { amount: string; notes: string }>>({});

const personnelOptions = computed(() =>
    personnelType.value === EMPLOYEE_TYPE ? props.employees : props.contractors,
);

const personLabel = (person: PersonOption): string =>
    `${person.first_name} ${person.last_name}`.trim();

const monthName = (month: number): string => {
    if (!month || month < 1 || month > 12) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
        new Date(2000, month - 1, 1),
    );
};

const personnelLabel = (record: AdjustmentRecord): string => {
    if (record.personnel?.first_name || record.personnel?.last_name) {
        return [record.personnel.first_name, record.personnel.last_name]
            .filter(Boolean)
            .join(' ');
    }

    return `#${record.personnel_id}`;
};

const personnelTypeLabel = (type: string): string => {
    const parts = type.split('\\');

    return parts[parts.length - 1] ?? type;
};

const typeLabel = (type: string): string =>
    props.adjustmentTypes.find((option) => option.value === type)?.label ?? type;

const formatCurrency = (value: number): string =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(value);

const initBulkEntry = (id: number): void => {
    if (!bulkEntries.value[id]) {
        bulkEntries.value[id] = { amount: '', notes: '' };
    }
};

const toggleBulkPerson = (id: number): void => {
    if (bulkEntries.value[id]) {
        delete bulkEntries.value[id];
    } else {
        initBulkEntry(id);
    }
};

const bulkSelectedCount = computed(() => Object.keys(bulkEntries.value).length);

const adjustmentActions = (record: AdjustmentRecord): RowActionItem[] => {
    if (record.applied_at) {
        return [];
    }

    return [
        deleteAction(
            {
                href: `/hr/payroll-adjustments/${record.id}`,
                title: t('Remove adjustment?'),
                description: t(
                    'This entry will not be applied when payroll is processed.',
                ),
            },
            'hr.delete',
        ),
    ];
};
</script>

<template>
    <Head :title="t('Payroll Adjustments')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            :title="t('Payroll Adjustments')"
            :description="
                t(
                    'Record bonus, deductions, and advances by month — applied automatically when you process payroll for that period',
                )
            "
        />

        <div class="grid gap-6 xl:grid-cols-3">
            <Can permission="hr.create">
            <Card class="xl:col-span-1">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Plus class="size-5" />
                        {{ bulkMode ? t('Bulk adjustments') : t('New adjustment') }}
                    </CardTitle>
                    <CardDescription>
                        <button type="button" class="text-primary underline-offset-4 hover:underline" @click="bulkMode = !bulkMode">
                            {{ bulkMode ? t('Switch to single entry') : t('Add many people at once') }}
                        </button>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-if="bulkMode"
                        action="/hr/payroll-adjustments/bulk"
                        method="post"
                        class="grid gap-4"
                        :options="{ preserveScroll: true, resetOnSuccess: true }"
                        v-slot="{ errors, processing }"
                    >
                        <input type="hidden" name="personnel_type" :value="personnelType" />
                        <template v-for="(entry, personId) in bulkEntries" :key="personId">
                            <input type="hidden" :name="`entries[${personId}][personnel_id]`" :value="personId" />
                            <input type="hidden" :name="`entries[${personId}][amount]`" :value="entry.amount" />
                            <input type="hidden" :name="`entries[${personId}][notes]`" :value="entry.notes" />
                        </template>

                        <div class="grid gap-2">
                            <Label>{{ t('Personnel type') }}</Label>
                            <select v-model="personnelType" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs">
                                <option :value="EMPLOYEE_TYPE">{{ t('Employee') }}</option>
                                <option :value="CONTRACTOR_TYPE">{{ t('Contractor') }}</option>
                            </select>
                        </div>

                        <div class="grid gap-2">
                            <Label for="bulk_type">{{ t('Type') }} *</Label>
                            <select id="bulk_type" name="type" required class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs">
                                <option v-for="option in adjustmentTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="bulk_period_year">{{ t('Year') }} *</Label>
                                <Input id="bulk_period_year" name="period_year" type="number" required :default-value="filters?.year ?? new Date().getFullYear()" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="bulk_period_month">{{ t('Month') }} *</Label>
                                <Input id="bulk_period_month" name="period_month" type="number" min="1" max="12" required :default-value="filters?.month ?? new Date().getMonth() + 1" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label>{{ t('People & amounts') }}</Label>
                            <div class="max-h-64 space-y-2 overflow-y-auto rounded-md border p-2">
                                <div v-for="person in personnelOptions" :key="person.id" class="flex items-center gap-2 rounded px-1 py-1">
                                    <input type="checkbox" :checked="!!bulkEntries[person.id]" @change="toggleBulkPerson(person.id)" />
                                    <span class="min-w-0 flex-1 truncate text-sm">{{ personLabel(person) }}</span>
                                    <Input
                                        v-if="bulkEntries[person.id]"
                                        v-model="bulkEntries[person.id].amount"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        class="w-24"
                                        :placeholder="t('Amount')"
                                    />
                                </div>
                            </div>
                            <InputError :message="errors.entries" />
                        </div>

                        <Button type="submit" :disabled="processing || bulkSelectedCount === 0">
                            <Users class="size-4" />
                            {{ t('Save for :count people', { count: String(bulkSelectedCount) }) }}
                        </Button>
                    </Form>

                    <Form
                        v-else
                        action="/hr/payroll-adjustments"
                        method="post"
                        class="grid gap-4"
                        :options="{ preserveScroll: true, resetOnSuccess: true }"
                        v-slot="{ errors, processing }"
                    >
                        <input type="hidden" name="personnel_type" :value="personnelType" />

                        <div class="grid gap-2">
                            <Label for="personnel_type">{{ t('Personnel type') }}</Label>
                            <select
                                id="personnel_type"
                                v-model="personnelType"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option :value="EMPLOYEE_TYPE">{{ t('Employee') }}</option>
                                <option :value="CONTRACTOR_TYPE">{{ t('Contractor') }}</option>
                            </select>
                        </div>

                        <div class="grid gap-2">
                            <Label for="personnel_id">{{ t('Person') }} *</Label>
                            <select
                                id="personnel_id"
                                name="personnel_id"
                                required
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="" disabled selected>{{ t('Select person') }}</option>
                                <option
                                    v-for="person in personnelOptions"
                                    :key="person.id"
                                    :value="person.id"
                                >
                                    {{ personLabel(person) }}
                                </option>
                            </select>
                            <InputError :message="errors.personnel_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="project_id">{{ t('Project') }}</Label>
                            <select
                                id="project_id"
                                name="project_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">{{ t('None') }}</option>
                                <option
                                    v-for="project in projects"
                                    :key="project.id"
                                    :value="project.id"
                                >
                                    {{ project.code }} — {{ project.name }}
                                </option>
                            </select>
                            <InputError :message="errors.project_id" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="period_year">{{ t('Year') }} *</Label>
                                <Input
                                    id="period_year"
                                    name="period_year"
                                    type="number"
                                    required
                                    :default-value="filters?.year ?? new Date().getFullYear()"
                                />
                                <InputError :message="errors.period_year" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="period_month">{{ t('Month') }} *</Label>
                                <Input
                                    id="period_month"
                                    name="period_month"
                                    type="number"
                                    min="1"
                                    max="12"
                                    required
                                    :default-value="filters?.month ?? new Date().getMonth() + 1"
                                />
                                <InputError :message="errors.period_month" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="type">{{ t('Type') }} *</Label>
                            <select
                                id="type"
                                name="type"
                                required
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="" disabled selected>{{ t('Select type') }}</option>
                                <option
                                    v-for="option in adjustmentTypes"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="errors.type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="amount">{{ t('Amount') }} *</Label>
                            <Input
                                id="amount"
                                name="amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                required
                            />
                            <InputError :message="errors.amount" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="notes">{{ t('Notes') }}</Label>
                            <Textarea id="notes" name="notes" rows="3" :placeholder="t('Optional reason')" />
                            <InputError :message="errors.notes" />
                        </div>

                        <Button type="submit" :disabled="processing">
                            {{ t('Save adjustment') }}
                        </Button>
                    </Form>
                </CardContent>
            </Card>
            </Can>

            <Card class="xl:col-span-2">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Receipt class="size-5" />
                        {{ t('Adjustments for :month :year', {
                            month: monthName(filters?.month ?? 1),
                            year: String(filters?.year ?? new Date().getFullYear()),
                        }) }}
                    </CardTitle>
                    <CardDescription>
                        {{
                            t(
                                'Pending entries are included when payroll is processed for the same month',
                            )
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <form
                        method="get"
                        action="/hr/payroll-adjustments"
                        class="flex flex-wrap items-end gap-4"
                    >
                        <div class="grid gap-2">
                            <Label for="year">{{ t('Year') }}</Label>
                            <Input
                                id="year"
                                name="year"
                                type="number"
                                :default-value="filters?.year ?? new Date().getFullYear()"
                                class="w-28"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="month">{{ t('Month') }}</Label>
                            <Input
                                id="month"
                                name="month"
                                type="number"
                                min="1"
                                max="12"
                                :default-value="filters?.month ?? new Date().getMonth() + 1"
                                class="w-20"
                            />
                        </div>
                        <Button type="submit" variant="outline">{{ t('Filter') }}</Button>
                    </form>

                    <div
                        v-if="adjustments.data.length === 0"
                        class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                    >
                        {{ t('No adjustments for this period.') }}
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="pb-3 pr-4 font-medium">{{ t('Personnel') }}</th>
                                    <th class="pb-3 pr-4 font-medium">{{ t('Project') }}</th>
                                    <th class="pb-3 pr-4 font-medium">{{ t('Type') }}</th>
                                    <th class="pb-3 pr-4 text-right font-medium">{{ t('Amount') }}</th>
                                    <th class="pb-3 pr-4 font-medium">{{ t('Status') }}</th>
                                    <th class="pb-3 pr-4 font-medium">{{ t('Notes') }}</th>
                                    <th class="pb-3 text-right font-medium">{{ t('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="record in adjustments.data"
                                    :key="record.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3 pr-4">
                                        <div class="font-medium">
                                            {{ personnelLabel(record) }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ personnelTypeLabel(record.personnel_type) }}
                                        </div>
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground">
                                        {{ record.project?.code ?? '—' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge
                                        :variant="
                                            record.type === 'bonus' || record.type === 'salary'
                                                ? 'default'
                                                : 'secondary'
                                        "
                                        >
                                            {{ typeLabel(record.type) }}
                                        </Badge>
                                    </td>
                                    <td
                                        class="py-3 pr-4 text-right font-medium"
                                        :class="
                                            record.type === 'bonus' || record.type === 'salary'
                                                ? 'text-green-700 dark:text-green-400'
                                                : 'text-destructive'
                                        "
                                    >
                                        {{ formatCurrency(record.amount) }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge
                                            :variant="
                                                record.applied_at ? 'outline' : 'secondary'
                                            "
                                        >
                                            {{ record.applied_at ? t('Applied') : t('Pending') }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground">
                                        {{ record.notes ?? '—' }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <RowActionsMenu
                                            v-if="adjustmentActions(record).length"
                                            :actions="adjustmentActions(record)"
                                        />
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <MisPagination :pagination="adjustments" />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
