<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { useTableSort } from '@/composables/useTableSort';
import SortableTh from '@/components/SortableTh.vue';
import { Plus, Trash2 } from '@lucide/vue';

export interface FinanceCategoryOption {
    id: number;
    name: string;
    applies_to: 'income' | 'expense' | 'both';
}

const props = withDefaults(
    defineProps<{
        name?: string;
        categories: FinanceCategoryOption[];
        appliesTo: 'income' | 'expense';
        error?: string;
        required?: boolean;
        defaultValue?: string | null;
        manage?: boolean;
    }>(),
    {
        name: 'category',
        required: false,
        manage: true,
    },
);

const { t } = useTranslations();
const showTable = ref(props.manage);
const newName = ref('');
const adding = ref(false);
const removingId = ref<number | null>(null);

const filtered = computed(() =>
    props.categories.filter(
        (category) =>
            category.applies_to === props.appliesTo ||
            category.applies_to === 'both',
    ),
);

const categorySort = useTableSort(() => filtered.value, {
    accessors: {
        name: (row) => row.name,
    },
    defaultKey: 'name',
    defaultDir: 'asc',
});

const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm';

const addCategory = (): void => {
    const name = newName.value.trim();

    if (!name || adding.value) {
        return;
    }

    adding.value = true;
    router.post(
        '/finance/categories',
        { name, applies_to: props.appliesTo },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                newName.value = '';
            },
            onFinish: () => {
                adding.value = false;
            },
        },
    );
};

const removeCategory = (id: number): void => {
    removingId.value = id;
    router.delete(`/finance/categories/${id}`, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            removingId.value = null;
        },
    });
};
</script>

<template>
    <div class="grid gap-2">
        <div class="flex items-center justify-between gap-2">
            <Label :for="name">
                {{ t('Category') }}
                <span v-if="required" class="text-destructive">*</span>
            </Label>
            <button
                v-if="manage"
                type="button"
                class="text-xs font-medium text-primary hover:underline"
                @click="showTable = !showTable"
            >
                {{ t('Categories') }}
            </button>
        </div>
        <select
            :id="name"
            :name="name"
            :required="required"
            :class="selectClass"
        >
            <option value="">{{ t('Select category') }}</option>
            <option
                v-for="category in filtered"
                :key="category.id"
                :value="category.name"
                :selected="category.name === defaultValue"
            >
                {{ category.name }}
            </option>
        </select>
        <InputError :message="error" />

        <div
            v-if="manage && showTable"
            class="rounded-md border bg-muted/20 p-3"
        >
            <div
                v-if="!filtered.length"
                class="mb-2 text-xs text-muted-foreground"
            >
                {{ t('No categories yet.') }}
            </div>
            <table v-else class="mb-3 w-full text-sm">
                <thead class="text-start text-muted-foreground">
                    <tr>
                        <SortableTh
                            column="name"
                            class="py-1 font-medium"
                            :sort-key="categorySort.sortKey"
                            :sort-dir="categorySort.sortDir"
                            @sort="categorySort.sortBy"
                        >
                            {{ t('Category') }}
                        </SortableTh>
                        <th class="w-10" />
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="category in categorySort.sortedRows" :key="category.id">
                        <td class="py-1.5">{{ category.name }}</td>
                        <td class="py-1.5 text-end">
                            <Can permission="finance.delete">
                                <button
                                    type="button"
                                    class="text-muted-foreground hover:text-destructive"
                                    :disabled="removingId === category.id"
                                    :aria-label="t('Delete')"
                                    @click="removeCategory(category.id)"
                                >
                                    <Trash2 class="size-3.5" />
                                </button>
                            </Can>
                        </td>
                    </tr>
                </tbody>
            </table>
            <Can permission="finance.create">
                <div class="flex gap-2">
                    <Input
                        v-model="newName"
                        class="h-8"
                        :placeholder="t('Add category')"
                        @keydown.enter.prevent="addCategory"
                    />
                    <Button
                        type="button"
                        size="sm"
                        :disabled="adding || !newName.trim()"
                        @click="addCategory"
                    >
                        <Plus class="size-4" />
                    </Button>
                </div>
            </Can>
        </div>
    </div>
</template>
