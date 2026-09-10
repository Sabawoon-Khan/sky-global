<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ChevronDown, Package, Plus, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import MisPage from '@/components/MisPage.vue';
import MisPagination from '@/components/MisPagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useMisPage } from '@/composables/useMisPage';
import { type Paginated } from '@/lib/format';
import { cn } from '@/lib/utils';

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

const props = defineProps<{
    equipment: Paginated<StockItem>;
    categories: string[];
    projects: ProjectOption[];
    employees: PersonOption[];
    contractors: PersonOption[];
    filters?: {
        search?: string | null;
        category?: string | null;
    };
}>();

const { t } = useMisPage();

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
const adjustingId = ref<number | null>(null);
const issuingId = ref<number | null>(null);
const issueMode = ref<'project' | 'personnel'>('project');
const personnelType = ref('App\\Models\\Hr\\Employee');
const issueProjectId = ref('');

const EMPLOYEE_TYPE = 'App\\Models\\Hr\\Employee';
const CONTRACTOR_TYPE = 'App\\Models\\Hr\\Contractor';

const applyFilters = (): void => {
    router.get(
        '/equipment',
        {
            search: search.value || undefined,
            category: category.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const lowStockCount = computed(
    () => props.equipment.data.filter((item) => item.quantity_on_hand <= 5).length,
);

const issueToProject = (itemId: number, form: HTMLFormElement): void => {
    if (!issueProjectId.value) {
        return;
    }

    const data = new FormData(form);

    router.post(
        `/projects/${issueProjectId.value}/equipment-issues`,
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

    <MisPage>
        <div class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        {{ t('Items in depot') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold tabular-nums">
                        {{ equipment.meta?.total ?? equipment.data.length }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        {{ t('Categories') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold tabular-nums">{{ categories.length }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        {{ t('Low stock') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold tabular-nums">{{ lowStockCount }}</p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <Collapsible v-model:open="showCreateForm">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Package class="size-5" />
                        {{ t('Depot stock') }}
                    </CardTitle>
                    <CardAction>
                        <Can permission="inventory.create">
                            <CollapsibleTrigger as-child>
                                <Button variant="outline" size="sm">
                                    <Plus class="me-1 size-4" />
                                    {{ t('Add item') }}
                                    <ChevronDown
                                        class="ms-1 size-4 transition-transform"
                                        :class="cn(showCreateForm && 'rotate-180')"
                                    />
                                </Button>
                            </CollapsibleTrigger>
                        </Can>
                    </CardAction>
                </CardHeader>
                <CardContent class="space-y-4">
                    <Can permission="inventory.create">
                        <CollapsibleContent class="rounded-md border bg-muted/20 p-4">
                            <Form
                                action="/equipment"
                                method="post"
                                class="grid gap-3 sm:grid-cols-2"
                                :options="{ preserveScroll: true, resetOnSuccess: true }"
                                v-slot="{ errors, processing }"
                                @success="showCreateForm = false"
                            >
                                <div class="grid gap-2">
                                    <Label for="eq-name">{{ t('Name') }} *</Label>
                                    <Input id="eq-name" name="name" required :placeholder="t('e.g. AK-47 Rifle')" />
                                    <InputError :message="errors.name" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="eq-sku">{{ t('SKU') }}</Label>
                                    <Input id="eq-sku" name="sku" :placeholder="t('e.g. GUN-AK47-001')" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="eq-category">{{ t('Category') }}</Label>
                                    <Input id="eq-category" name="category" :placeholder="t('e.g. Weapons, Radios')" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="eq-unit">{{ t('Unit') }}</Label>
                                    <Input id="eq-unit" name="unit" placeholder="pcs" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="eq-qty">{{ t('Initial quantity') }}</Label>
                                    <Input id="eq-qty" name="initial_quantity" type="number" min="0" placeholder="0" />
                                </div>
                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="eq-desc">{{ t('Description') }}</Label>
                                    <Textarea id="eq-desc" name="description" rows="2" />
                                </div>
                                <div class="sm:col-span-2">
                                    <Button type="submit" size="sm" :disabled="processing">
                                        {{ t('Save to stock') }}
                                    </Button>
                                </div>
                            </Form>
                        </CollapsibleContent>
                    </Can>

                    <form class="flex flex-col gap-2 sm:flex-row" @submit.prevent="applyFilters">
                        <div class="relative flex-1">
                            <Search class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="search"
                                class="ps-9"
                                :placeholder="t('Search name or SKU')"
                            />
                        </div>
                        <select
                            v-model="category"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">{{ t('All categories') }}</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">
                                {{ cat }}
                            </option>
                        </select>
                        <Button type="submit" variant="secondary" size="sm">{{ t('Filter') }}</Button>
                    </form>

                    <div v-if="!equipment.data.length" class="ui-empty-state">
                        {{ t('No stock items yet. Add guns, radios, and other goods to the depot.') }}
                    </div>
                    <div v-else class="space-y-4">
                        <div class="overflow-x-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead class="border-b bg-muted/40 text-start text-muted-foreground">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">{{ t('Item') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('Category') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('SKU') }}</th>
                                        <th class="px-3 py-2 text-end font-medium">{{ t('On hand') }}</th>
                                        <th class="px-3 py-2 text-end font-medium">{{ t('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="item in equipment.data"
                                        :key="item.id"
                                        class="hover:bg-muted/30"
                                    >
                                        <td class="px-3 py-2">
                                            <p class="font-medium">{{ item.name }}</p>
                                            <p v-if="item.description" class="text-xs text-muted-foreground">
                                                {{ item.description }}
                                            </p>
                                        </td>
                                        <td class="px-3 py-2">
                                            <Badge v-if="item.category" variant="outline">
                                                {{ item.category }}
                                            </Badge>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </td>
                                        <td class="px-3 py-2 text-muted-foreground">
                                            {{ item.sku ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-end font-semibold tabular-nums">
                                            {{ item.quantity_on_hand }}
                                            <span class="text-xs font-normal text-muted-foreground">
                                                {{ item.unit ?? 'pcs' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="flex justify-end gap-1">
                                                <Can permission="inventory.edit">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        @click="
                                                            adjustingId =
                                                                adjustingId === item.id ? null : item.id;
                                                            issuingId = null;
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
                                                            issuingId =
                                                                issuingId === item.id ? null : item.id;
                                                            adjustingId = null;
                                                        "
                                                    >
                                                        {{ t('Issue') }}
                                                    </Button>
                                                </Can>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="adjustingId"
                                        :key="`adjust-${adjustingId}`"
                                        class="bg-muted/20"
                                    >
                                        <td colspan="5" class="px-3 py-3">
                                            <Form
                                                v-for="item in equipment.data.filter((i) => i.id === adjustingId)"
                                                :key="item.id"
                                                :action="`/equipment/${item.id}/adjust-stock`"
                                                method="post"
                                                class="flex flex-wrap items-end gap-2"
                                                :options="{ preserveScroll: true, resetOnSuccess: true }"
                                                v-slot="{ errors, processing }"
                                                @success="adjustingId = null"
                                            >
                                                <div class="grid gap-1">
                                                    <Label>{{ t('Adjustment (+/-)') }}</Label>
                                                    <Input
                                                        name="adjustment"
                                                        type="number"
                                                        required
                                                        :placeholder="t('e.g. 10 or -2')"
                                                        class="w-36"
                                                    />
                                                    <InputError :message="errors.adjustment" />
                                                </div>
                                                <div class="grid gap-1">
                                                    <Label>{{ t('Notes') }}</Label>
                                                    <Input name="notes" class="w-56" />
                                                </div>
                                                <Button type="submit" size="sm" :disabled="processing">
                                                    {{ t('Update stock') }}
                                                </Button>
                                            </Form>
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="issuingId"
                                        :key="`issue-${issuingId}`"
                                        class="bg-muted/20"
                                    >
                                        <td colspan="5" class="px-3 py-3">
                                            <div class="mb-3 flex gap-2">
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

                                            <template
                                                v-for="item in equipment.data.filter((i) => i.id === issuingId)"
                                                :key="item.id"
                                            >
                                                <form
                                                    v-if="issueMode === 'project'"
                                                    class="flex flex-wrap items-end gap-2"
                                                    @submit.prevent="issueToProject(item.id, $event.target as HTMLFormElement)"
                                                >
                                                    <div class="grid gap-1">
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
                                                    <div class="grid gap-1">
                                                        <Label>{{ t('Quantity') }} *</Label>
                                                        <Input
                                                            name="quantity"
                                                            type="number"
                                                            min="1"
                                                            :max="item.quantity_on_hand"
                                                            required
                                                            class="w-28"
                                                        />
                                                    </div>
                                                    <div class="grid gap-1">
                                                        <Label>{{ t('Date') }}</Label>
                                                        <Input name="issued_at" type="date" />
                                                    </div>
                                                    <Button type="submit" size="sm">
                                                        {{ t('Issue to project') }}
                                                    </Button>
                                                </form>

                                                <Form
                                                    v-else
                                                    action="/equipment/issues"
                                                    method="post"
                                                    class="flex flex-wrap items-end gap-2"
                                                    :options="{ preserveScroll: true, resetOnSuccess: true }"
                                                    v-slot="{ errors, processing }"
                                                    @success="issuingId = null"
                                                >
                                                    <input type="hidden" name="equipment_catalog_id" :value="item.id" />
                                                    <input type="hidden" name="personnel_type" :value="personnelType" />
                                                    <div class="grid gap-1">
                                                        <Label>{{ t('Type') }}</Label>
                                                        <select
                                                            v-model="personnelType"
                                                            class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                                        >
                                                            <option :value="EMPLOYEE_TYPE">{{ t('Employee') }}</option>
                                                            <option :value="CONTRACTOR_TYPE">{{ t('Contractor') }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="grid gap-1">
                                                        <Label>{{ t('Person') }} *</Label>
                                                        <select
                                                            name="personnel_id"
                                                            required
                                                            class="h-9 min-w-[10rem] rounded-md border border-input bg-background px-3 text-sm"
                                                        >
                                                            <option value="" disabled selected>
                                                                {{ t('Select person') }}
                                                            </option>
                                                            <option
                                                                v-for="person in personnelType === EMPLOYEE_TYPE
                                                                    ? employees
                                                                    : contractors"
                                                                :key="person.id"
                                                                :value="person.id"
                                                            >
                                                                {{ person.first_name }} {{ person.last_name }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="grid gap-1">
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
                                                    <div class="grid gap-1">
                                                        <Label>{{ t('Quantity') }} *</Label>
                                                        <Input
                                                            name="quantity"
                                                            type="number"
                                                            min="1"
                                                            :max="item.quantity_on_hand"
                                                            required
                                                            class="w-28"
                                                        />
                                                        <InputError :message="errors.quantity" />
                                                    </div>
                                                    <Button type="submit" size="sm" :disabled="processing">
                                                        {{ t('Issue') }}
                                                    </Button>
                                                </Form>
                                            </template>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <MisPagination :pagination="equipment" />
                    </div>
                </CardContent>
            </Collapsible>
        </Card>
    </MisPage>
</template>
