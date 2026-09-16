<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import SortableTh from '@/components/SortableTh.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    V2FilterBar,
    V2FormSection,
    V2Hero,
    V2ListPage,
    V2Pager,
    V2SelectFilter,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { formatDate, formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { Building2 } from '@lucide/vue';

interface TrainingGuard {
    id: number;
    name: string;
    father_name: string;
    batch_number: string;
    start_date?: string | null;
    end_date?: string | null;
    status: string;
    company_trainer?: string | null;
    company_location?: string | null;
}

interface AvailableGuard {
    id: number;
    name: string;
    father_name: string;
    batch_number: string;
    tazkira_number?: string | null;
    id_card_number?: string | null;
}

const props = defineProps<{
    guards: Paginated<TrainingGuard>;
    availableGuards: AvailableGuard[];
    stats: {
        total: number;
        in_training: number;
        certified: number;
    };
    filters?: {
        search?: string | null;
        status?: string | null;
        batch_number?: string | null;
    };
}>();

const onlyKeys = ['guards', 'availableGuards', 'stats', 'filters'];

const { filters, pending, apply, clear } = useMisFilters(
    '/training/company',
    {
        search: props.filters?.search ?? '',
        status: props.filters?.status ?? '',
        batch_number: props.filters?.batch_number ?? '',
    },
    { search: '', status: '', batch_number: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.guards.data, {
    accessors: {
        name: (row) => row.name,
        father: (row) => row.father_name,
        batch: (row) => row.batch_number,
        trainer: (row) => row.company_trainer,
        status: (row) => row.status,
    },
});
const { t, viewAction, can } = useMisPage();

const selectedIds = reactive<number[]>([]);

const toggle = (id: number): void => {
    const index = selectedIds.indexOf(id);
    if (index >= 0) {
        selectedIds.splice(index, 1);
        return;
    }
    selectedIds.push(id);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Training', href: '/training/guards' },
            { title: 'Company training', href: '/training/company' },
        ],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Name') },
    { key: 'father', label: t("Father's name") },
    { key: 'batch', label: t('Batch number') },
    { key: 'trainer', label: t('Trainer') },
    { key: 'status', label: t('Status') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const guardActions = (guard: TrainingGuard): RowActionItem[] => [
    viewAction(`/training/guards/${guard.id}`),
];
</script>

<template>
    <Head :title="t('Company training')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('Training') }}</template>
            <template #title>{{ t('Company training') }}</template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Guards')"
                        :value="formatNumber(stats.total)"
                    >
                        <template #icon><Building2 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('In training')"
                        :value="formatNumber(stats.in_training)"
                    >
                        <template #icon><Building2 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        accent
                        icon-tone="orange"
                        :title="t('Certified')"
                        :value="formatNumber(stats.certified)"
                    >
                        <template #icon><Building2 /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <Form
            v-if="can('training.edit') && availableGuards.length"
            action="/training/company"
            method="post"
            class="mb-6"
            v-slot="{ errors, processing }"
        >
            <V2FormSection :title="t('Assign registered guards')">
                <div class="mis-form-grid mb-4">
                    <div class="v2-field">
                        <Label for="company_trainer">{{ t('Trainer') }}</Label>
                        <Input id="company_trainer" name="company_trainer" />
                    </div>
                    <div class="v2-field">
                        <Label for="company_location">{{ t('Location') }}</Label>
                        <Input id="company_location" name="company_location" />
                    </div>
                    <div class="v2-field mis-form-span">
                        <Label for="notes">{{ t('Notes') }}</Label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="2"
                            class="mis-form-textarea"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="w-10 py-2" />
                                <th class="py-2">{{ t('Name') }}</th>
                                <th class="py-2">{{ t("Father's name") }}</th>
                                <th class="py-2">{{ t('Batch number') }}</th>
                                <th class="py-2">{{ t('Tazkira number') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="guard in availableGuards"
                                :key="guard.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-2">
                                    <input
                                        type="checkbox"
                                        class="size-4 rounded border border-input"
                                        :value="guard.id"
                                        :checked="selectedIds.includes(guard.id)"
                                        name="guard_ids[]"
                                        @change="toggle(guard.id)"
                                    />
                                </td>
                                <td class="py-2 font-medium">{{ guard.name }}</td>
                                <td class="py-2 text-muted-foreground">
                                    {{ guard.father_name }}
                                </td>
                                <td class="py-2">{{ guard.batch_number }}</td>
                                <td class="py-2">{{ guard.tazkira_number ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <InputError :message="errors.guard_ids" />
                <div class="mt-4 flex justify-end">
                    <Button type="submit" :disabled="processing || selectedIds.length === 0">
                        {{ t('Assign to company training') }}
                    </Button>
                </div>
            </V2FormSection>
        </Form>

        <V2TablePanel
            table-id="training-company"
            :columns="tableColumns"
            :pending="pending && guards.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search guards...')"
                            @submit="apply()"
                            @clear="clear"
                        />
                    </div>
                    <V2SelectFilter
                        v-model="filters.status"
                        :label="t('Status')"
                        @change="(value) => apply({ status: value })"
                    >
                        <option value="">{{ t('All statuses') }}</option>
                        <option value="company">{{ t('Company training') }}</option>
                        <option value="completed">{{ t('Completed') }}</option>
                        <option value="certified">{{ t('Certified') }}</option>
                    </V2SelectFilter>
                    <template #columns>
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>

            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="name">{{ t('Name') }}</SortableTh>
                            <SortableTh column="father">{{ t("Father's name") }}</SortableTh>
                            <SortableTh column="batch">{{ t('Batch number') }}</SortableTh>
                            <SortableTh column="trainer">{{ t('Trainer') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(guard, index) in sortedRows"
                            :key="guard.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="guards.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/training/guards/${guard.id}`"
                                    class="code-chip"
                                >
                                    {{ guard.name }}
                                </Link>
                            </td>
                            <td class="muted">{{ guard.father_name }}</td>
                            <td class="muted">{{ guard.batch_number }}</td>
                            <td class="muted">{{ guard.company_trainer ?? '—' }}</td>
                            <td><StatusBadge :status="guard.status" /></td>
                            <td class="end">
                                <RowActionsMenu :actions="guardActions(guard)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!guards.data.length"
                            :colspan="visibleColCount"
                            :title="t('No company training records.')"
                        />
                    </tbody>
                </table>
            </template>

            <template v-if="guards.links?.length" #pager>
                <V2Pager :items="guards" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
