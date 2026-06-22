<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FolderKanban, Plus, Search } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import MisPage from '@/components/MisPage.vue';
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
import { useMisPage } from '@/composables/useMisPage';
import { formatCurrency, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { projectStatusActions } from '@/lib/status-actions';

interface Organization {
    id: number;
    name: string;
}

interface Project {
    id: number;
    code: string;
    name: string;
    status: string;
    submission_deadline: string | null;
    our_bid_amount: number | null;
    total_contract_value: number | null;
    currency: string | null;
    organization?: Organization | null;
}

interface StatusOption {
    value: string;
    label: string;
}

defineProps<{
    projects: Paginated<Project>;
    statusOptions: StatusOption[];
    filters?: { search?: string | null; status?: string | null };
}>();

const { t, viewAction, editAction } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Projects', href: '/projects' }],
    },
});

const statusVariant = (status: string) => {
    if (['won', 'active'].includes(status)) return 'default';
    if (status === 'lost') return 'destructive';
    if (status === 'submitted') return 'secondary';
    return 'outline';
};

const statusLabel = (status: string) =>
    t(status.charAt(0).toUpperCase() + status.slice(1));

const projectActions = (project: Project): RowActionItem[] => [
    viewAction(`/projects/${project.id}`),
    editAction(`/projects/${project.id}`),
    ...projectStatusActions(project.id, project.status, t),
];
</script>

<template>
    <Head :title="t('Projects')" />

    <MisPage>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                :title="t('Projects')"
                :description="t('Every proposal and contract in one place — from first bid to final delivery')"
            />
            <Button as-child size="sm">
                <Link href="/projects/create">
                    <Plus class="me-1 size-4" />
                    {{ t('New Project') }}
                </Link>
            </Button>
        </div>

        <Card>
            <CardHeader class="pb-3">
                <CardTitle class="flex items-center gap-2 text-base">
                    <FolderKanban class="size-4" />
                    {{ t('All Projects') }}
                </CardTitle>
                <CardDescription>
                    {{
                        t(':count projects', {
                            count: String(
                                projects.meta?.total ?? projects.data.length,
                            ),
                        })
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <form method="get" action="/projects" class="flex flex-col gap-2 sm:flex-row">
                    <div class="relative flex-1">
                        <Search class="absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            name="search"
                            :default-value="filters?.search ?? ''"
                            :placeholder="t('Search code, name, reference...')"
                            class="ps-9"
                        />
                    </div>
                    <select
                        name="status"
                        class="h-9 rounded-md border border-input px-3 text-sm"
                    >
                        <option value="">{{ t('All statuses') }}</option>
                        <option
                            v-for="opt in statusOptions"
                            :key="opt.value"
                            :value="opt.value"
                            :selected="filters?.status === opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                    <Button type="submit" variant="secondary" size="sm">{{
                        t('Filter')
                    }}</Button>
                </form>

                <div
                    v-if="projects.data.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center"
                >
                    <p class="font-medium">{{ t('No projects yet') }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            t('Create a project to start bidding — win or lose, everything stays on one record.')
                        }}
                    </p>
                    <Button as-child class="mt-4" size="sm">
                        <Link href="/projects/create">{{ t('Create project') }}</Link>
                    </Button>
                </div>

                <div v-else class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-start text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ t('Code') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Project') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Client') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Our bid') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Status') }}</th>
                                <th class="px-3 py-2 text-end font-medium">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="project in projects.data"
                                :key="project.id"
                                class="hover:bg-muted/30"
                            >
                                <td class="px-3 py-2 font-mono text-xs">
                                    <Link :href="`/projects/${project.id}`" class="hover:underline">
                                        {{ project.code }}
                                    </Link>
                                </td>
                                <td class="px-3 py-2">
                                    <Link :href="`/projects/${project.id}`" class="font-medium hover:underline">
                                        {{ project.name }}
                                    </Link>
                                </td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ project.organization?.name ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{
                                        formatCurrency(
                                            project.our_bid_amount ?? project.total_contract_value,
                                            project.currency,
                                        )
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    <Badge :variant="statusVariant(project.status)">
                                        {{ statusLabel(project.status) }}
                                    </Badge>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <RowActionsMenu :actions="projectActions(project)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <MisPagination :pagination="projects" />
            </CardContent>
        </Card>
    </MisPage>
</template>
