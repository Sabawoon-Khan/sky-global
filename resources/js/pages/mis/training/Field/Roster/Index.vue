<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import TrainingFieldSubnav from '@/components/mis/TrainingFieldSubnav.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
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
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { ClipboardList, Plus } from '@lucide/vue';
import { computed } from 'vue';

interface FieldGuard {
    id: number;
    name: string;
    father_name: string;
    grandfather_name?: string | null;
    tazkira_number?: string | null;
    id_card_number?: string | null;
    site?: string | null;
}

const props = defineProps<{
    roster: Paginated<FieldGuard>;
    stats: { roster: number; reports: number };
    filters?: { search?: string | null };
}>();

const onlyKeys = ['roster', 'stats', 'filters'];

const { filters, pending, apply, clear } = useMisFilters(
    '/training/field/roster',
    { search: props.filters?.search ?? '' },
    { search: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.roster.data, {
    accessors: {
        name: (row) => row.name,
        father: (row) => row.father_name,
        tazkira: (row) => row.tazkira_number,
        site: (row) => row.site,
    },
});

const { t, deleteAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Training', href: '/training/guards' },
            { title: 'On-site training', href: '/training/field/reports' },
            { title: 'Existing guards', href: '/training/field/roster' },
        ],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Name') },
    { key: 'father', label: t("Father's name") },
    { key: 'tazkira', label: t('Tazkira number') },
    { key: 'site', label: t('Site') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const rowActions = (guard: FieldGuard): RowActionItem[] => {
    const actions: RowActionItem[] = [];
    if (can('training.create')) {
        actions.push({
            label: t('New visit report'),
            href: `/training/field/reports/create?guard_ids=${guard.id}`,
        });
    }
    if (can('training.delete')) {
        actions.push(
            deleteAction(
                {
                    href: `/training/field/roster/${guard.id}`,
                    title: t('Remove from roster'),
                    description: t(
                        'Are you sure you want to delete ":name"? This cannot be undone.',
                        { name: guard.name },
                    ),
                },
                'training.delete',
            ),
        );
    }
    return actions;
};
</script>

<template>
    <Head :title="t('Existing guards')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('Education and Training') }}</template>
            <template #title>{{ t('Existing guards') }}</template>
            <template #description>
                {{
                    t(
                        'Guards already on duty before a trainer visit. Use them when filing visit reports.',
                    )
                }}
            </template>
            <template #actions>
                <Button v-if="can('training.create')" variant="outline" as-child>
                    <Link href="/training/field/reports/create">
                        <Plus class="me-2 size-4" />
                        {{ t('New visit report') }}
                    </Link>
                </Button>
            </template>
        </V2Hero>

        <TrainingFieldSubnav />

        <V2StatGrid>
            <V2StatCard
                :title="t('Existing guards')"
                :value="formatNumber(stats.roster)"
            />
            <V2StatCard
                :title="t('Visit reports')"
                :value="formatNumber(stats.reports)"
                :icon="ClipboardList"
            />
        </V2StatGrid>

        <V2FormSection
            v-if="can('training.create')"
            :title="t('Add guard to roster')"
            class="mb-6 rounded-2xl border border-border/70 bg-card p-6"
        >
            <Form
                action="/training/field/roster"
                method="post"
                class="mis-form-grid"
                v-slot="{ errors, processing }"
            >
                <div class="v2-field">
                    <Label for="name">{{ t('Name') }} *</Label>
                    <Input id="name" name="name" required />
                    <InputError :message="errors.name" />
                </div>
                <div class="v2-field">
                    <Label for="father_name">{{ t("Father's name") }} *</Label>
                    <Input id="father_name" name="father_name" required />
                    <InputError :message="errors.father_name" />
                </div>
                <div class="v2-field">
                    <Label for="grandfather_name">{{
                        t("Grandfather's name")
                    }}</Label>
                    <Input id="grandfather_name" name="grandfather_name" />
                </div>
                <div class="v2-field">
                    <Label for="tazkira_number">{{ t('Tazkira number') }}</Label>
                    <Input id="tazkira_number" name="tazkira_number" />
                </div>
                <div class="v2-field">
                    <Label for="id_card_number">{{ t('ID card number') }}</Label>
                    <Input id="id_card_number" name="id_card_number" />
                </div>
                <div class="v2-field">
                    <Label for="site">{{ t('Site') }}</Label>
                    <Input id="site" name="site" />
                </div>
                <div class="mis-form-span flex justify-end">
                    <Button type="submit" :disabled="processing">
                        {{ t('Add guard to roster') }}
                    </Button>
                </div>
            </Form>
        </V2FormSection>

        <V2TablePanel
            table-id="training-field-roster"
            :columns="tableColumns"
            :pending="pending && roster.data.length > 0"
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
                            <SortableTh column="father">{{
                                t("Father's name")
                            }}</SortableTh>
                            <SortableTh column="tazkira">{{
                                t('Tazkira number')
                            }}</SortableTh>
                            <SortableTh column="site">{{ t('Site') }}</SortableTh>
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
                                :from="roster.meta?.from"
                            />
                            <td class="font-medium">{{ guard.name }}</td>
                            <td class="muted">{{ guard.father_name }}</td>
                            <td class="muted">
                                {{ guard.tazkira_number ?? '—' }}
                            </td>
                            <td class="muted">{{ guard.site ?? '—' }}</td>
                            <td class="end">
                                <RowActionsMenu :actions="rowActions(guard)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!roster.data.length"
                            :colspan="visibleColCount"
                            :title="t('No guards on the roster yet.')"
                        />
                    </tbody>
                </table>
            </template>

            <template v-if="roster.links?.length" #pager>
                <V2Pager :items="roster" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
