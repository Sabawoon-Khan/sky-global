<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Package,
    PackageCheck,
    PackageMinus,
    Plus,
    Search,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import EmptyState from '@/components/EmptyState.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import SortableTh from '@/components/SortableTh.vue';
import {
    V2FilterBar,
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2Pager,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { formatNumber, type Paginated } from '@/lib/format';

interface StockItem {
    id: number;
    name: string;
    sku: string | null;
    category: string | null;
    unit: string | null;
    description: string | null;
    is_active: boolean;
    quantity_on_hand: number;
    quantity_reserved: number;
}

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

interface ChartPoint {
    key: string;
    label: string;
    value: number;
}

const props = defineProps<{
    equipment: Paginated<StockItem>;
    categories: string[];
    projects: ProjectOption[];
    employees: PersonOption[];
    contractors: PersonOption[];
    stats: {
        total: number;
        active: number;
        inactive: number;
        low_stock: number;
        in_stock?: number;
        empty?: number;
    };
    chart: {
        status: ChartPoint[];
        monthly: ChartPoint[];
    };
    filters?: {
        search?: string | null;
        category?: string | null;
        is_active?: string | null;
    };
}>();

const { t, can } = useMisPage();

const { sortedRows } = provideTableSort(() => props.equipment.data, {
    accessors: {
        item: (row) => row.name,
        category: (row) => row.category,
        sku: (row) => row.sku,
        on_hand: (row) => row.quantity_on_hand,
    },
});

const onlyKeys = [
    'equipment',
    'categories',
    'projects',
    'employees',
    'contractors',
    'stats',
    'chart',
    'filters',
];

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'item', label: t('Item') },
    { key: 'category', label: t('Category') },
    { key: 'sku', label: t('SKU') },
    { key: 'on_hand', label: t('On hand') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Stock / Inventory', href: '/equipment' },
        ],
    },
});

const showCreateForm = ref(false);
const search = ref(props.filters?.search ?? '');
const category = ref(props.filters?.category ?? '');
const isActive = ref(props.filters?.is_active ?? '');
const adjustingId = ref<number | null>(null);
const issuingId = ref<number | null>(null);
const deletingId = ref<number | null>(null);
const issueMode = ref<'project' | 'personnel'>('project');
const personnelType = ref('App\\Models\\Hr\\Employee');
const issueProjectId = ref('');

const EMPLOYEE_TYPE = 'App\\Models\\Hr\\Employee';
const CONTRACTOR_TYPE = 'App\\Models\\Hr\\Contractor';

const adjustingItem = computed(
    () => props.equipment.data.find((item) => item.id === adjustingId.value) ?? null,
);
const issuingItem = computed(
    () => props.equipment.data.find((item) => item.id === issuingId.value) ?? null,
);
const deletingItem = computed(
    () => props.equipment.data.find((item) => item.id === deletingId.value) ?? null,
);

const applyFilters = (): void => {
    router.get(
        '/equipment',
        {
            search: search.value || undefined,
            category: category.value || undefined,
            is_active: isActive.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const statusPalette = [
    'var(--school-navy)',
    'var(--brand-accent)',
    'var(--school-gold)',
    'var(--muted-foreground)',
    '#3d5a80',
    '#8b9bb4',
];

const pipeline = computed(() => {
    const rows = (props.chart?.status ?? []).filter((row) => row.value > 0);
    const total = Math.max(
        rows.reduce((sum, row) => sum + row.value, 0),
        props.stats.total,
        1,
    );
    const activeShare =
        total > 0 ? Math.round((props.stats.active / total) * 100) : 0;

    return {
        activeShare,
        segments: rows.slice(0, 4).map((row, index) => ({
            key: row.key,
            label: row.label,
            value: row.value,
            color: statusPalette[index % statusPalette.length],
            width: Math.max(row.value > 0 ? 6 : 0, (row.value / total) * 100),
        })),
    };
});

const monthlyBars = computed(() => {
    const rows =
        props.chart?.monthly?.length > 0
            ? props.chart.monthly
            : Array.from({ length: 6 }, (_, index) => ({
                  key: `m-${index}`,
                  label: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'][index],
                  value: 0,
              }));
    const max = Math.max(...rows.map((row) => Number(row.value) || 0), 1);

    return rows.map((row) => {
        const value = Number(row.value) || 0;
        return {
            key: row.key,
            label: row.label,
            value,
            height: Math.max(value > 0 ? 6 : 3, Math.round((value / max) * 44)),
            peak: value === max && value > 0,
        };
    });
});

const issueToProject = (itemId: number, form: HTMLFormElement): void => {
    if (!issueProjectId.value) {
        return;
    }

    const data = new FormData(form);

    router.post(
        `/mis/projects/${issueProjectId.value}/equipment-issues`,
        {
            equipment_catalog_id: itemId,
            quantity: data.get('quantity'),
            issued_at: data.get('issued_at') || undefined,
            notes: data.get('notes') || undefined,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                issuingId.value = null;
                issueProjectId.value = '';
            },
        },
    );
};
</script>

<template>
    <Head :title="t('Stock / Inventory')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-operations.png">
            <template #eyebrow>{{ t('Operations') }}</template>
            <template #title>{{ t('Stock / Inventory') }}</template>
            <template #side>
                <button
                    v-if="can('inventory.create')"
                    type="button"
                    class="create-btn"
                    @click="showCreateForm = true"
                >
                    <Plus />
                    {{ t('Add item') }}
                </button>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Catalog') }}</template>
                        <template #meta
                            >{{ pipeline.activeShare }}%
                            {{ t('Active') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{
                                    formatNumber(stats.active)
                                }}</strong>
                                <small>{{ t('Active') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in pipeline.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in pipeline.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ formatNumber(seg.value) }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Created by month') }}</template>
                        <template #meta
                            >{{ formatNumber(stats.total) }}
                            {{ t('Total') }}</template
                        >

                        <div class="money-chart">
                            <div
                                v-for="bar in monthlyBars"
                                :key="bar.key"
                                class="money-col"
                                :class="{ peak: bar.peak }"
                                :title="`${bar.label}: ${bar.value}`"
                            >
                                <div class="money-pair">
                                    <i
                                        class="usd"
                                        :style="{ height: `${bar.height}px` }"
                                    />
                                </div>
                                <span>{{ bar.label }}</span>
                            </div>
                        </div>
                    </V2IndicatorCard>
                </div>
            </template>

            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Items')"
                        :value="formatNumber(stats.total)"
                    >
                        <template #icon><Package /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Active')"
                        :value="formatNumber(stats.active)"
                    >
                        <template #icon><PackageCheck /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Inactive')"
                        :value="formatNumber(stats.inactive)"
                    >
                        <template #icon><PackageMinus /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Low stock')"
                        :value="formatNumber(stats.low_stock)"
                    >
                        <template #icon><AlertTriangle /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel table-id="equipment-catalog" :columns="tableColumns">
            <template #filters>
                <V2FilterBar>
                    <form class="flex flex-col gap-2 sm:flex-row sm:flex-1 sm:items-center" @submit.prevent="applyFilters">
                        <div class="relative flex-1">
                            <Search class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="search"
                                class="h-9 ps-9"
                                :placeholder="t('Search name or SKU')"
                            />
                        </div>
                        <select
                            v-model="category"
                            class="mis-form-select h-9 min-w-[8rem]"
                        >
                            <option value="">{{ t('All categories') }}</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">
                                {{ cat }}
                            </option>
                        </select>
                        <select
                            v-model="isActive"
                            class="mis-form-select h-9 min-w-[8rem]"
                        >
                            <option value="">{{ t('All statuses') }}</option>
                            <option value="1">{{ t('Active') }}</option>
                            <option value="0">{{ t('Inactive') }}</option>
                        </select>
                        <Button type="submit" variant="outline" class="h-9">{{ t('Filter') }}</Button>
                    </form>
                    <Can permission="inventory.create">
                        <Button
                            type="button"
                            variant="secondary"
                            class="h-9 gap-1"
                            @click="showCreateForm = true"
                        >
                            <Plus class="size-4" />
                            {{ t('Add item') }}
                        </Button>
                    </Can>
                </V2FilterBar>
            </template>

            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="item">{{ t('Item') }}</SortableTh>
                            <SortableTh column="category">{{ t('Category') }}</SortableTh>
                            <SortableTh column="sku">{{ t('SKU') }}</SortableTh>
                            <SortableTh column="on_hand" align="end" class="end">{{ t('On hand') }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in sortedRows"
                            :key="item.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="equipment.meta?.from"
                            />
                            <td>
                                <p class="font-medium">{{ item.name }}</p>
                                <p v-if="item.description" class="muted text-xs">
                                    {{ item.description }}
                                </p>
                            </td>
                            <td>
                                <Badge v-if="item.category" variant="outline">
                                    {{ item.category }}
                                </Badge>
                                <span v-else class="muted">—</span>
                            </td>
                            <td class="muted">{{ item.sku ?? '—' }}</td>
                            <td class="end nums">
                                {{ item.quantity_on_hand }}
                                <span class="muted text-xs">
                                    {{ item.unit ?? 'pcs' }}
                                </span>
                            </td>
                            <td class="end">
                                <div class="flex justify-end gap-1">
                                                <Can permission="inventory.edit">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        @click="
                                                            adjustingId = item.id;
                                                            issuingId = null;
                                                            deletingId = null;
                                                        "
                                                    >
                                                        {{ t('Adjust') }}
                                                    </Button>
                                                </Can>
                                                <Can permission="inventory.create">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        @click="
                                                            issuingId = item.id;
                                                            adjustingId = null;
                                                            deletingId = null;
                                                        "
                                                    >
                                                        {{ t('Issue') }}
                                                    </Button>
                                                </Can>
                                                <Can permission="inventory.delete">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        class="text-destructive hover:text-destructive"
                                                        @click="
                                                            deletingId = item.id;
                                                            adjustingId = null;
                                                            issuingId = null;
                                                        "
                                                    >
                                                        {{ t('Delete') }}
                                                    </Button>
                                                </Can>
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!equipment.data.length"
                            :colspan="visibleColCount"
                            :title="
                                t(
                                    'No stock items yet. Add guns, radios, and other goods to the depot.',
                                )
                            "
                        />
                    </tbody>
                </table>
            </template>

            <template v-if="equipment.links?.length" #pager>
                <V2Pager :items="equipment" :only="onlyKeys" />
            </template>
        </V2TablePanel>

        <Dialog
            :open="showCreateForm"
            @update:open="showCreateForm = $event"
        >
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <Form
                    action="/equipment"
                    method="post"
                    :options="{ preserveScroll: true, resetOnSuccess: true }"
                    v-slot="{ errors, processing }"
                    @success="showCreateForm = false"
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Add item') }}</DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-3 py-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="eq-name">{{ t('Name') }} *</Label>
                            <Input
                                id="eq-name"
                                name="name"
                                required
                                :placeholder="t('e.g. AK-47 Rifle')"
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="eq-sku">{{ t('SKU') }}</Label>
                            <Input
                                id="eq-sku"
                                name="sku"
                                :placeholder="t('e.g. GUN-AK47-001')"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="eq-category">{{ t('Category') }}</Label>
                            <Input
                                id="eq-category"
                                name="category"
                                :placeholder="t('e.g. Weapons, Radios')"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="eq-unit">{{ t('Unit') }}</Label>
                            <Input id="eq-unit" name="unit" placeholder="pcs" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="eq-qty">{{ t('Initial quantity') }}</Label>
                            <Input
                                id="eq-qty"
                                name="initial_quantity"
                                type="number"
                                min="0"
                                placeholder="0"
                            />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="eq-desc">{{ t('Description') }}</Label>
                            <Textarea id="eq-desc" name="description" rows="2" />
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="showCreateForm = false"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Save to stock') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="adjustingItem !== null"
            @update:open="(open) => !open && (adjustingId = null)"
        >
            <DialogContent v-if="adjustingItem">
                <Form
                    :action="`/equipment/${adjustingItem.id}/adjust-stock`"
                    method="post"
                    :options="{ preserveScroll: true, resetOnSuccess: true }"
                    v-slot="{ errors, processing }"
                    @success="adjustingId = null"
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Adjust') }}</DialogTitle>
                        <DialogDescription>
                            {{ adjustingItem.name }}
                            · {{ adjustingItem.quantity_on_hand }}
                            {{ adjustingItem.unit ?? 'pcs' }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-3 py-4">
                        <div class="grid gap-2">
                            <Label>{{ t('Adjustment (+/-)') }}</Label>
                            <Input
                                name="adjustment"
                                type="number"
                                required
                                :placeholder="t('e.g. 10 or -2')"
                            />
                            <InputError :message="errors.adjustment" />
                        </div>
                        <div class="grid gap-2">
                            <Label>{{ t('Notes') }}</Label>
                            <Input name="notes" />
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="adjustingId = null"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Update stock') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="issuingItem !== null"
            @update:open="(open) => !open && (issuingId = null)"
        >
            <DialogContent v-if="issuingItem" class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ t('Issue') }}</DialogTitle>
                    <DialogDescription>
                        {{ issuingItem.name }}
                        · {{ issuingItem.quantity_on_hand }}
                        {{ issuingItem.unit ?? 'pcs' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="flex gap-2">
                    <Button
                        type="button"
                        size="sm"
                        :variant="issueMode === 'project' ? 'default' : 'outline'"
                        @click="issueMode = 'project'"
                    >
                        {{ t('To project') }}
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="issueMode === 'personnel' ? 'default' : 'outline'"
                        @click="issueMode = 'personnel'"
                    >
                        {{ t('To personnel') }}
                    </Button>
                </div>

                <form
                    v-if="issueMode === 'project'"
                    class="grid gap-3 py-2"
                    @submit.prevent="
                        issueToProject(
                            issuingItem.id,
                            $event.target as HTMLFormElement,
                        )
                    "
                >
                    <div class="grid gap-2">
                        <Label>{{ t('Project') }} *</Label>
                        <select
                            v-model="issueProjectId"
                            required
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="" disabled>
                                {{ t('Select project') }}
                            </option>
                            <option
                                v-for="project in projects"
                                :key="project.id"
                                :value="String(project.id)"
                            >
                                {{ project.code }} — {{ project.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label>{{ t('Quantity') }} *</Label>
                        <Input
                            name="quantity"
                            type="number"
                            min="1"
                            :max="issuingItem.quantity_on_hand"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label>{{ t('Date') }}</Label>
                        <Input name="issued_at" type="date" />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="issuingId = null"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit">
                            {{ t('Issue to project') }}
                        </Button>
                    </DialogFooter>
                </form>

                <Form
                    v-else
                    action="/equipment/issues"
                    method="post"
                    :options="{ preserveScroll: true, resetOnSuccess: true }"
                    v-slot="{ errors, processing }"
                    @success="issuingId = null"
                >
                    <input
                        type="hidden"
                        name="equipment_catalog_id"
                        :value="issuingItem.id"
                    />
                    <input
                        type="hidden"
                        name="personnel_type"
                        :value="personnelType"
                    />
                    <div class="grid gap-3 py-2">
                        <div class="grid gap-2">
                            <Label>{{ t('Type') }}</Label>
                            <select
                                v-model="personnelType"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option :value="EMPLOYEE_TYPE">
                                    {{ t('Employee') }}
                                </option>
                                <option :value="CONTRACTOR_TYPE">
                                    {{ t('Contractor') }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label>{{ t('Person') }} *</Label>
                            <select
                                name="personnel_id"
                                required
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="" disabled selected>
                                    {{ t('Select person') }}
                                </option>
                                <option
                                    v-for="person in personnelType ===
                                    EMPLOYEE_TYPE
                                        ? employees
                                        : contractors"
                                    :key="person.id"
                                    :value="person.id"
                                >
                                    {{ person.first_name }}
                                    {{ person.last_name }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label>{{ t('Project') }}</Label>
                            <select
                                name="project_id"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">{{ t('Optional') }}</option>
                                <option
                                    v-for="project in projects"
                                    :key="project.id"
                                    :value="project.id"
                                >
                                    {{ project.code }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label>{{ t('Quantity') }} *</Label>
                            <Input
                                name="quantity"
                                type="number"
                                min="1"
                                :max="issuingItem.quantity_on_hand"
                                required
                            />
                            <InputError :message="errors.quantity" />
                        </div>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="issuingId = null"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Issue') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="deletingItem !== null"
            @update:open="(open) => !open && (deletingId = null)"
        >
            <DialogContent v-if="deletingItem">
                <Form
                    :action="`/equipment/${deletingItem.id}`"
                    method="delete"
                    :options="{ preserveScroll: true }"
                    v-slot="{ processing }"
                    @success="deletingId = null"
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Delete item') }}</DialogTitle>
                        <DialogDescription>
                            {{
                                t(
                                    'Are you sure you want to delete ":name"? This cannot be undone.',
                                    { name: deletingItem.name },
                                )
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="deletingId = null"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                        >
                            {{ t('Delete') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </V2ListPage>
</template>
