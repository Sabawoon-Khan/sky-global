import {
    computed,
    inject,
    provide,
    reactive,
    ref,
    toValue,
    type ComputedRef,
    type InjectionKey,
    type MaybeRefOrGetter,
    type Ref,
    type UnwrapNestedRefs,
} from 'vue';

export type SortDir = 'asc' | 'desc';

export type SortAccessors<T> = Record<string, (row: T) => unknown>;

export type TableSortOptions<T> = {
    defaultKey?: string;
    defaultDir?: SortDir;
    accessors?: SortAccessors<T>;
};

export type TableSortState<T = Record<string, unknown>> = {
    sortedRows: Ref<T[]>;
    sortKey: Ref<string>;
    sortDir: Ref<SortDir>;
    sortBy: (key: string) => void;
};

/** Reactive view of table sort state — nested refs auto-unwrap in templates (SSR-safe). */
export type ReactiveTableSortState<T = Record<string, unknown>> =
    UnwrapNestedRefs<{
        sortedRows: ComputedRef<T[]>;
        sortKey: Ref<string>;
        sortDir: Ref<SortDir>;
        sortBy: (key: string) => void;
    }>;

const TABLE_SORT_KEY: InjectionKey<TableSortState> = Symbol('tableSort');

function getByPath(row: Record<string, unknown>, path: string): unknown {
    return path.split('.').reduce<unknown>((acc, key) => {
        if (acc && typeof acc === 'object' && key in acc) {
            return (acc as Record<string, unknown>)[key];
        }

        return undefined;
    }, row);
}

function numericValue(value: unknown): number | null {
    if (typeof value === 'number' && Number.isFinite(value)) {
        return value;
    }

    if (typeof value === 'string') {
        const trimmed = value.replace(/,/g, '').trim();

        if (/^-?\d+(\.\d+)?$/.test(trimmed)) {
            return Number(trimmed);
        }
    }

    return null;
}

function compareValues(a: unknown, b: unknown): number {
    if (a == null && b == null) {
        return 0;
    }
    if (a == null) {
        return 1;
    }
    if (b == null) {
        return -1;
    }

    const aNum = numericValue(a);
    const bNum = numericValue(b);

    if (aNum != null && bNum != null) {
        return aNum - bNum;
    }

    if (typeof a === 'boolean' && typeof b === 'boolean') {
        return Number(a) - Number(b);
    }

    const as = String(a);
    const bs = String(b);
    const aTime = Date.parse(as);
    const bTime = Date.parse(bs);

    if (!Number.isNaN(aTime) && !Number.isNaN(bTime) && /[-T:]/.test(as) && /[-T:]/.test(bs)) {
        return aTime - bTime;
    }

    return as.localeCompare(bs, undefined, { numeric: true, sensitivity: 'base' });
}

function createTableSortState<T>(
    rows: MaybeRefOrGetter<T[]>,
    options: TableSortOptions<T> = {},
): TableSortState<T> {
    const sortKey = ref(options.defaultKey ?? 'id');
    const sortDir = ref<SortDir>(options.defaultDir ?? 'desc');

    const valueOf = (row: T, key: string): unknown => {
        const accessor = options.accessors?.[key];

        if (accessor) {
            return accessor(row);
        }

        return getByPath(row as Record<string, unknown>, key);
    };

    const sortedRows = computed(() => {
        const list = [...toValue(rows)];
        const key = sortKey.value;
        const dir = sortDir.value === 'asc' ? 1 : -1;

        list.sort((left, right) => dir * compareValues(valueOf(left, key), valueOf(right, key)));

        return list;
    });

    function sortBy(key: string): void {
        if (sortKey.value === key) {
            sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
            return;
        }

        sortKey.value = key;
        sortDir.value = 'asc';
    }

    return {
        sortedRows,
        sortKey,
        sortDir,
        sortBy,
    };
}

export function useTableSort<T>(
    rows: MaybeRefOrGetter<T[]>,
    options: TableSortOptions<T> = {},
): ReactiveTableSortState<T> {
    return reactive(createTableSortState(rows, options)) as ReactiveTableSortState<T>;
}

export function provideTableSort<T>(
    rows: MaybeRefOrGetter<T[]>,
    options: TableSortOptions<T> = {},
): TableSortState<T> {
    const state = createTableSortState(rows, options);
    provide(TABLE_SORT_KEY, state as TableSortState);

    return state;
}

export function useProvidedTableSort(): TableSortState | null {
    return inject(TABLE_SORT_KEY, null);
}
