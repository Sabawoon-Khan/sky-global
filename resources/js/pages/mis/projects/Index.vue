<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FolderKanban, Plus, Search } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
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

interface Organization {
    id: number;
    name: string;
}

interface Project {
    id: number;
    code: string;
    name: string;
    status: string;
    contract_start?: string | null;
    contract_end?: string | null;
    total_contract_value?: number | null;
    currency?: string | null;
    organization?: Organization | null;
}

interface PaginatedProjects {
    data: Project[];
    meta?: { total: number };
}

interface Props {
    projects: PaginatedProjects;
    filters?: { search?: string; status?: string };
}

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Projects', href: '/projects' }],
    },
});

const formatCurrency = (value?: number | null, currency = 'USD'): string => {
    if (value == null) {
        return '—';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(value);
};

const statusVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (status === 'active') {
        return 'default';
    }

    if (['completed', 'closed'].includes(status)) {
        return 'secondary';
    }

    if (['cancelled', 'suspended'].includes(status)) {
        return 'destructive';
    }

    return 'outline';
};
</script>

<template>
    <Head title="Projects" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                title="Projects"
                description="Active and archived security contracts"
            />
            <Button as-child>
                <Link href="/projects/create">
                    <Plus class="size-4" />
                    New Project
                </Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <FolderKanban class="size-5" />
                    All Projects
                </CardTitle>
                <CardDescription>
                    {{ projects.meta?.total ?? projects.data.length }} projects
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <form method="get" action="/projects" class="relative max-w-sm">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        name="search"
                        :default-value="filters?.search"
                        placeholder="Search projects..."
                        class="pl-9"
                    />
                </form>

                <div
                    v-if="projects.data.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    No projects found.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-3 pr-4 font-medium">Code</th>
                                <th class="pb-3 pr-4 font-medium">Name</th>
                                <th class="pb-3 pr-4 font-medium">Client</th>
                                <th class="pb-3 pr-4 font-medium">Contract Value</th>
                                <th class="pb-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="project in projects.data"
                                :key="project.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4 font-mono text-xs">
                                    <Link
                                        :href="`/projects/${project.id}`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ project.code }}
                                    </Link>
                                </td>
                                <td class="py-3 pr-4">{{ project.name }}</td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ project.organization?.name ?? '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    {{
                                        formatCurrency(
                                            project.total_contract_value,
                                            project.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                                <td class="py-3">
                                    <Badge :variant="statusVariant(project.status)">
                                        {{ project.status }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
