<script setup lang="ts">
import { computed, ref } from 'vue';
import { Check, Minus } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import { useMisPage } from '@/composables/useMisPage';

export interface PrintPerson {
    id: number;
    name: string;
}

const props = defineProps<{
    employees: PrintPerson[];
    contractors: PrintPerson[];
}>();

const selectedIds = defineModel<number[]>({ required: true });

const { t } = useMisPage();
const query = ref('');

const allIds = computed(() => [
    ...props.employees.map((person) => person.id),
    ...props.contractors.map((person) => person.id),
]);

const selectedCount = computed(() => selectedIds.value.length);
const totalCount = computed(() => allIds.value.length);

const normalizedQuery = computed(() => query.value.trim().toLowerCase());

const matchesQuery = (person: PrintPerson): boolean => {
    if (!normalizedQuery.value) {
        return true;
    }

    return person.name.toLowerCase().includes(normalizedQuery.value);
};

const visibleEmployees = computed(() =>
    props.employees.filter(matchesQuery),
);
const visibleContractors = computed(() =>
    props.contractors.filter(matchesQuery),
);

const hasVisiblePeople = computed(
    () =>
        visibleEmployees.value.length > 0 ||
        visibleContractors.value.length > 0,
);

const isSelected = (id: number): boolean => selectedIds.value.includes(id);

const groupState = (
    people: PrintPerson[],
): boolean | 'indeterminate' => {
    if (people.length === 0) {
        return false;
    }

    const selected = people.filter((person) => isSelected(person.id)).length;

    if (selected === 0) {
        return false;
    }

    if (selected === people.length) {
        return true;
    }

    return 'indeterminate';
};

const employeeGroupState = computed(() => groupState(props.employees));
const contractorGroupState = computed(() => groupState(props.contractors));

function setSelected(id: number, checked: boolean): void {
    if (checked) {
        if (!isSelected(id)) {
            selectedIds.value = [...selectedIds.value, id];
        }

        return;
    }

    selectedIds.value = selectedIds.value.filter((value) => value !== id);
}

function toggleGroup(
    people: PrintPerson[],
    checked: boolean | 'indeterminate',
): void {
    const ids = new Set(people.map((person) => person.id));

    if (checked === true) {
        selectedIds.value = [
            ...new Set([...selectedIds.value, ...ids]),
        ];

        return;
    }

    selectedIds.value = selectedIds.value.filter((id) => !ids.has(id));
}

function selectAll(): void {
    selectedIds.value = [...allIds.value];
}

function clearSelection(): void {
    selectedIds.value = [];
}

function onPersonChange(
    id: number,
    checked: boolean | 'indeterminate',
): void {
    setSelected(id, checked === true);
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-muted-foreground">
                {{
                    t(':selected of :total selected', {
                        selected: String(selectedCount),
                        total: String(totalCount),
                    })
                }}
            </p>
            <div class="flex items-center gap-1">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :disabled="selectedCount === totalCount || totalCount === 0"
                    @click="selectAll"
                >
                    {{ t('Select all') }}
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :disabled="selectedCount === 0"
                    @click="clearSelection"
                >
                    {{ t('Clear') }}
                </Button>
            </div>
        </div>

        <MisSearchInput
            v-model="query"
            :placeholder="t('Search employees or contractors')"
            :aria-label="t('Search employees or contractors')"
        />

        <div
            v-if="!hasVisiblePeople"
            class="rounded-lg border border-dashed px-3 py-6 text-center text-sm text-muted-foreground"
        >
            {{ t('No matching personnel.') }}
        </div>

        <div
            v-else
            class="grid max-h-64 gap-3 overflow-y-auto sm:grid-cols-2"
        >
            <section v-if="visibleEmployees.length > 0" class="space-y-2">
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="print-employees-group"
                        :model-value="employeeGroupState"
                        @update:model-value="
                            (checked) => toggleGroup(employees, checked)
                        "
                    >
                        <Minus
                            v-if="employeeGroupState === 'indeterminate'"
                            class="size-3.5"
                        />
                        <Check v-else class="size-3.5" />
                    </Checkbox>
                    <Label
                        for="print-employees-group"
                        class="cursor-pointer text-xs font-semibold uppercase tracking-wide"
                    >
                        {{ t('Employees') }}
                        ({{ employees.filter((person) => isSelected(person.id)).length }}/{{
                            employees.length
                        }})
                    </Label>
                </div>
                <ul class="space-y-1.5">
                    <li
                        v-for="person in visibleEmployees"
                        :key="`emp-${person.id}`"
                        class="flex items-center gap-2 rounded-md px-1 py-1 text-sm hover:bg-muted/60"
                    >
                        <Checkbox
                            :id="`print-emp-${person.id}`"
                            :model-value="isSelected(person.id)"
                            @update:model-value="
                                (checked) =>
                                    onPersonChange(person.id, checked)
                            "
                        />
                        <Label
                            :for="`print-emp-${person.id}`"
                            class="min-w-0 cursor-pointer truncate font-normal"
                        >
                            {{ person.name }}
                        </Label>
                    </li>
                </ul>
            </section>

            <section v-if="visibleContractors.length > 0" class="space-y-2">
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="print-contractors-group"
                        :model-value="contractorGroupState"
                        @update:model-value="
                            (checked) => toggleGroup(contractors, checked)
                        "
                    >
                        <Minus
                            v-if="contractorGroupState === 'indeterminate'"
                            class="size-3.5"
                        />
                        <Check v-else class="size-3.5" />
                    </Checkbox>
                    <Label
                        for="print-contractors-group"
                        class="cursor-pointer text-xs font-semibold uppercase tracking-wide"
                    >
                        {{ t('Contractors') }}
                        ({{
                            contractors.filter((person) => isSelected(person.id))
                                .length
                        }}/{{ contractors.length }})
                    </Label>
                </div>
                <ul class="space-y-1.5">
                    <li
                        v-for="person in visibleContractors"
                        :key="`con-${person.id}`"
                        class="flex items-center gap-2 rounded-md px-1 py-1 text-sm hover:bg-muted/60"
                    >
                        <Checkbox
                            :id="`print-con-${person.id}`"
                            :model-value="isSelected(person.id)"
                            @update:model-value="
                                (checked) =>
                                    onPersonChange(person.id, checked)
                            "
                        />
                        <Label
                            :for="`print-con-${person.id}`"
                            class="min-w-0 cursor-pointer truncate font-normal"
                        >
                            {{ person.name }}
                        </Label>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
