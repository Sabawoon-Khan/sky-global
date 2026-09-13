<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, History, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import MisPagination from '@/components/MisPagination.vue';
import SortableTh from '@/components/SortableTh.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { LATIN_NUMERALS, type Paginated } from '@/lib/format';

interface CauserSummary {
    id: number;
    name: string | null;
    email: string | null;
}

interface ActivityChange {
    field: string;
    old: string | null;
    new: string | null;
}

interface ActivityLogRecord {
    id: number;
    event: string | null;
    subject_type: string;
    subject_id: number | null;
    subject_label: string;
    causer: CauserSummary | null;
    changes: ActivityChange[];
    created_at: string | null;
}

interface SubjectTypeOption {
    value: string;
    label: string;
}

const props = defineProps<{
    logs: Paginated<ActivityLogRecord>;
    events: string[];
    subjectTypes: SubjectTypeOption[];
    filters?: {
        search?: string | null;
        event?: string | null;
        subject_type?: string | null;
    };
}>();

const { t } = useTranslations();
const expandedLogId = ref<number | null>(null);

const { sortedRows } = provideTableSort(() => props.logs.data, {
    accessors: {
        time: (row) => row.created_at,
        user: (row) => row.causer?.name ?? row.causer?.email,
        event: (row) => row.event,
        record: (row) => `${row.subject_type} ${row.subject_label}`,
    },
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Activity Logs', href: '/settings/activity-logs' },
        ],
    },
});

const eventLabel = (event: string | null): string => {
    if (!event) {
        return '—';
    }

    const labels: Record<string, string> = {
        created: t('Created'),
        updated: t('Updated'),
        deleted: t('Deleted'),
        restored: t('Restored'),
    };

    return labels[event] ?? event;
};

const eventVariant = (
    event: string | null,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (event === 'created') {
        return 'default';
    }

    if (event === 'deleted') {
        return 'destructive';
    }

    if (event === 'updated') {
        return 'secondary';
    }

    return 'outline';
};

const hasLogs = computed(() => props.logs.data.length > 0);

const formatDateTime = (value?: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', {
        ...LATIN_NUMERALS,
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const toggleExpanded = (id: number): void => {
    expandedLogId.value = expandedLogId.value === id ? null : id;
};
</script>

<template>
    <Head :title="t('Activity Logs')" />

    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <History class="size-5" />
                    {{ t('Activity Logs') }}
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <form
                    method="get"
                    action="/settings/activity-logs"
                    class="grid gap-4 md:grid-cols-4"
                >
                    <div class="relative md:col-span-2">
                        <Search
                            class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            name="search"
                            :default-value="filters?.search ?? ''"
                            :placeholder="t('Search by user or record...')"
                            class="pl-9"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="event">{{ t('Event') }}</Label>
                        <select
                            id="event"
                            name="event"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            :value="filters?.event ?? ''"
                        >
                            <option value="">{{ t('All events') }}</option>
                            <option v-for="event in events" :key="event" :value="event">
                                {{ eventLabel(event) }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="subject_type">{{ t('Record') }}</Label>
                        <select
                            id="subject_type"
                            name="subject_type"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            :value="filters?.subject_type ?? ''"
                        >
                            <option value="">{{ t('All records') }}</option>
                            <option
                                v-for="type in subjectTypes"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-4">
                        <button
                            type="submit"
                            class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground"
                        >
                            {{ t('Apply filters') }}
                        </button>
                    </div>
                </form>

                <div
                    v-if="!hasLogs"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    {{ t('No activity logs found.') }}
                </div>

                <div v-else class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-start text-muted-foreground">
                            <tr>
                                <SortableTh column="time" class="px-3 py-2 font-medium">
                                    {{ t('Date') }}
                                </SortableTh>
                                <SortableTh column="user" class="px-3 py-2 font-medium">
                                    {{ t('User') }}
                                </SortableTh>
                                <SortableTh column="event" class="px-3 py-2 font-medium">
                                    {{ t('Event') }}
                                </SortableTh>
                                <SortableTh column="record" class="px-3 py-2 font-medium">
                                    {{ t('Record') }}
                                </SortableTh>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="log in sortedRows" :key="log.id">
                                <tr
                                    class="cursor-pointer border-b last:border-0 hover:bg-muted/30"
                                    @click="toggleExpanded(log.id)"
                                >
                                    <td class="px-3 py-2 whitespace-nowrap text-muted-foreground">
                                        {{ formatDateTime(log.created_at) }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ log.causer?.name ?? t('Unknown user') }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <Badge :variant="eventVariant(log.event)">
                                            {{ eventLabel(log.event) }}
                                        </Badge>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <span>
                                                <span class="text-muted-foreground">
                                                    {{ log.subject_type }}
                                                </span>
                                                · {{ log.subject_label }}
                                            </span>
                                            <component
                                                :is="
                                                    expandedLogId === log.id
                                                        ? ChevronUp
                                                        : ChevronDown
                                                "
                                                class="size-4 shrink-0 text-muted-foreground"
                                            />
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="expandedLogId === log.id">
                                    <td colspan="4" class="bg-muted/20 px-3 py-3">
                                        <p
                                            v-if="!log.changes.length"
                                            class="text-muted-foreground"
                                        >
                                            {{ t('No field changes recorded.') }}
                                        </p>
                                        <table v-else class="w-full text-sm">
                                            <thead>
                                                <tr class="text-muted-foreground">
                                                    <th class="py-1 text-start font-medium">
                                                        {{ t('Field') }}
                                                    </th>
                                                    <th class="py-1 text-start font-medium">
                                                        {{ t('Old value') }}
                                                    </th>
                                                    <th class="py-1 text-start font-medium">
                                                        {{ t('New value') }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-for="change in log.changes"
                                                    :key="`${log.id}-${change.field}`"
                                                >
                                                    <td class="py-1 font-medium">
                                                        {{ change.field }}
                                                    </td>
                                                    <td class="py-1 text-muted-foreground">
                                                        {{ change.old ?? '—' }}
                                                    </td>
                                                    <td class="py-1">
                                                        {{ change.new ?? '—' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <MisPagination :pagination="logs" />
            </CardContent>
        </Card>
    </div>
</template>
