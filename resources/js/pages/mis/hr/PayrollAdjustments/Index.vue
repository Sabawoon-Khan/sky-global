<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Plus, Users } from '@lucide/vue';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import {
    V2FilterBar,
    V2Hero,
    V2ListPage,
    V2Pager,
    V2Panel,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { formatCurrency, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

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

const onlyKeys = [
    'adjustments',
    'projects',
    'employees',
    'contractors',
    'adjustmentTypes',
    'filters',
];

const { sortedRows } = provideTableSort(() => props.adjustments.data);

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'personnel', label: t('Personnel') },
    { key: 'project', label: t('Project') },
    { key: 'type', label: t('Type') },
    { key: 'amount', label: t('Amount') },
    { key: 'status', label: t('Status') },
    { key: 'notes', label: t('Notes') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

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

const filterYear = computed(
    () => props.filters?.year ?? new Date().getFullYear(),
);
const filterMonth = computed(
    () => props.filters?.month ?? new Date().getMonth() + 1,
);

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

const amountTone = (type: string): 'positive' | 'negative' | 'neutral' =>
    type === 'bonus' || type === 'salary' ? 'positive' : 'negative';
</script>

<template>
    <Head :title="t('Payroll Adjustments')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('HR') }}</template>
            <template #title>{{ t('Payroll Adjustments') }}</template>
            <template #description>
                {{
                    t('Bonuses, deductions, and one-off pay changes before payroll runs.')
                }}
            </template>
        </V2Hero>

        <div class="grid gap-6 xl:grid-cols-3">
            <Can permission="hr.create">
                <V2Panel
                    class="xl:col-span-1"
                    :title="bulkMode ? t('Bulk adjustments') : t('New adjustment')"
                >
                    <template #actions>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="bulkMode = !bulkMode"
                        >
                            {{
                                bulkMode
                                    ? t('Single entry')
                                    : t('Bulk mode')
                            }}
                        </Button>
                    </template>

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
                            <select v-model="personnelType" class="mis-form-select">
                                <option :value="EMPLOYEE_TYPE">{{ t('Employee') }}</option>
                                <option :value="CONTRACTOR_TYPE">{{ t('Contractor') }}</option>
                            </select>
                        </div>

                        <div class="grid gap-2">
                            <Label for="bulk_type">{{ t('Type') }} *</Label>
                            <select id="bulk_type" name="type" required class="mis-form-select">
                                <option v-for="option in adjustmentTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="bulk_period_year">{{ t('Year') }} *</Label>
                                <Input id="bulk_period_year" name="period_year" type="number" required :default-value="filterYear" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="bulk_period_month">{{ t('Month') }} *</Label>
                                <Input id="bulk_period_month" name="period_month" type="number" min="1" max="12" required :default-value="filterMonth" />
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
                                class="mis-form-select"
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
                                class="mis-form-select"
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
                                class="mis-form-select"
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
                                    :default-value="filterYear"
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
                                    :default-value="filterMonth"
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
                                class="mis-form-select"
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
                            <Plus class="size-4" />
                            {{ t('Save adjustment') }}
                        </Button>
                    </Form>
                </V2Panel>
            </Can>

            <V2TablePanel
                class="xl:col-span-2"
                table-id="hr-payroll-adjustments"
                :columns="tableColumns"
            >
                <template #filters>
                    <V2FilterBar>
                        <form
                            method="get"
                            action="/hr/payroll-adjustments"
                            class="flex flex-wrap items-end gap-2"
                        >
                            <Input
                                id="year"
                                name="year"
                                type="number"
                                :default-value="filterYear"
                                class="h-9 w-28"
                                :placeholder="t('Year')"
                            />
                            <Input
                                id="month"
                                name="month"
                                type="number"
                                min="1"
                                max="12"
                                :default-value="filterMonth"
                                class="h-9 w-20"
                                :placeholder="t('Month')"
                            />
                            <Button type="submit" variant="outline" class="h-9">
                                {{ t('Filter') }}
                            </Button>
                        </form>
                    </V2FilterBar>
                </template>

                <template #default="{ visibleColCount }">
                    <table>
                        <thead>
                            <tr>
                                <TableIndexTh />
                                <th>{{ t('Personnel') }}</th>
                                <th>{{ t('Project') }}</th>
                                <th>{{ t('Type') }}</th>
                                <th class="end">{{ t('Amount') }}</th>
                                <th>{{ t('Status') }}</th>
                                <th>{{ t('Notes') }}</th>
                                <th class="end">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(record, index) in sortedRows"
                                :key="record.id"
                                :style="{ '--i': index }"
                            >
                                <TableIndexTd
                                    :index="index"
                                    :from="adjustments.meta?.from"
                                />
                                <td>
                                    <div class="font-medium">
                                        {{ personnelLabel(record) }}
                                    </div>
                                    <div class="muted text-xs">
                                        {{ personnelTypeLabel(record.personnel_type) }}
                                    </div>
                                </td>
                                <td class="muted">
                                    {{ record.project?.code ?? '—' }}
                                </td>
                                <td>
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
                                    class="end nums"
                                    :class="
                                        amountTone(record.type) === 'positive'
                                            ? 'text-green-700 dark:text-green-400'
                                            : 'text-destructive'
                                    "
                                >
                                    {{ formatCurrency(record.amount) }}
                                </td>
                                <td>
                                    <Badge
                                        :variant="record.applied_at ? 'outline' : 'secondary'"
                                    >
                                        {{ record.applied_at ? t('Applied') : t('Pending') }}
                                    </Badge>
                                </td>
                                <td class="muted">
                                    {{ record.notes ?? '—' }}
                                </td>
                                <td class="end">
                                    <RowActionsMenu
                                        v-if="adjustmentActions(record).length"
                                        :actions="adjustmentActions(record)"
                                    />
                                    <span v-else class="muted text-xs">—</span>
                                </td>
                            </tr>
                            <EmptyState
                                v-if="!adjustments.data.length"
                                :colspan="visibleColCount"
                                :title="t('No adjustments for this period.')"
                            />
                        </tbody>
                    </table>
                </template>

                <template v-if="adjustments.links?.length" #pager>
                    <V2Pager :items="adjustments" :only="onlyKeys" />
                </template>
            </V2TablePanel>
        </div>
    </V2ListPage>
</template>
