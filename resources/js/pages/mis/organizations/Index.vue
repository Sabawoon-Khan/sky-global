<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Eye, FolderKanban, Pencil, Plus, Search, Trash2, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import MisPage from '@/components/MisPage.vue';
import MisPagination from '@/components/MisPagination.vue';
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
import { Label } from '@/components/ui/label';
import type { Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { toggleIsActiveAction } from '@/lib/status-actions';

interface OrganizationType {
    id: number;
    name: string;
    color: string | null;
}

interface Organization {
    id: number;
    name: string;
    province: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    tax_id: string | null;
    is_active: boolean;
    organization_type: OrganizationType | null;
    projects_count: number;
    procurement_opportunities_count: number;
}

interface Props {
    organizations: Paginated<Organization>;
    organizationTypes: OrganizationType[];
    stats: {
        total: number;
        active: number;
        with_projects: number;
    };
    filters?: {
        search?: string | null;
        organization_type_id?: number | null;
    };
}

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Organizations', href: '/organizations' },
        ],
    },
});

const organizationActions = (org: Organization): RowActionItem[] => [
    {
        label: 'View',
        icon: Eye,
        href: `/organizations/${org.id}`,
    },
    {
        label: 'Edit',
        icon: Pencil,
        href: `/organizations/${org.id}/edit`,
    },
    toggleIsActiveAction({
        url: `/organizations/${org.id}`,
        name: org.name,
        isActive: org.is_active,
        entityLabel: 'organization',
    }),
    {
        label: 'Delete',
        icon: Trash2,
        variant: 'destructive',
        separator: true,
        href: `/organizations/${org.id}`,
        method: 'delete',
        confirm: {
            title: 'Delete organization',
            description: `Are you sure you want to delete "${org.name}"? This cannot be undone.`,
            confirmLabel: 'Delete',
        },
    },
];
</script>

<template>
    <Head title="Organizations" />

    <MisPage>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                title="Organizations"
                description="Register clients, partners, and procurement bodies"
            />
            <Button as-child>
                <Link href="/organizations/create">
                    <Plus class="mr-2 size-4" />
                    Add Organization
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Total registered</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.total }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Active organizations</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.active }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>With active projects</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.with_projects }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Building2 class="size-5" />
                    All Organizations
                </CardTitle>
                <CardDescription>
                    {{ organizations.meta?.total ?? organizations.data.length }} organizations
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <form method="get" action="/organizations" class="grid gap-4 md:grid-cols-3">
                    <div class="relative md:col-span-2">
                        <Search
                            class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            name="search"
                            :default-value="filters?.search ?? ''"
                            placeholder="Search by name, email, phone, province..."
                            class="pl-9"
                        />
                    </div>
                    <div class="flex gap-2">
                        <div class="grid flex-1 gap-1.5">
                            <Label for="organization_type_id" class="sr-only">Type</Label>
                            <select
                                id="organization_type_id"
                                name="organization_type_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">All types</option>
                                <option
                                    v-for="type in organizationTypes"
                                    :key="type.id"
                                    :value="type.id"
                                    :selected="filters?.organization_type_id === type.id"
                                >
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>
                        <Button type="submit" variant="secondary">Filter</Button>
                    </div>
                </form>

                <div
                    v-if="organizations.data.length === 0"
                    class="rounded-lg border border-dashed p-10 text-center"
                >
                    <Building2 class="mx-auto mb-3 size-10 text-muted-foreground" />
                    <p class="font-medium">No organizations yet</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Add government bodies, NGOs, private companies, and other clients you bid to or serve.
                    </p>
                    <Button as-child class="mt-4">
                        <Link href="/organizations/create">Create first organization</Link>
                    </Button>
                </div>

                <div v-else class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Organization</th>
                                <th class="px-4 py-3 text-left font-medium">Type</th>
                                <th class="px-4 py-3 text-left font-medium">Location</th>
                                <th class="px-4 py-3 text-left font-medium">Contact</th>
                                <th class="px-4 py-3 text-left font-medium">Activity</th>
                                <th class="px-4 py-3 text-left font-medium">Status</th>
                                <th class="px-4 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="org in organizations.data"
                                :key="org.id"
                                class="transition-colors hover:bg-muted/50"
                            >
                                <td class="px-4 py-3">
                                    <Link
                                        :href="`/organizations/${org.id}`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ org.name }}
                                    </Link>
                                    <p v-if="org.tax_id" class="text-xs text-muted-foreground">
                                        Tax ID: {{ org.tax_id }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge
                                        v-if="org.organization_type"
                                        variant="secondary"
                                        :style="
                                            org.organization_type.color
                                                ? {
                                                      borderColor: org.organization_type.color,
                                                      color: org.organization_type.color,
                                                  }
                                                : undefined
                                        "
                                    >
                                        {{ org.organization_type.name }}
                                    </Badge>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    <div>{{ org.province ?? '—' }}</div>
                                    <div v-if="org.address" class="max-w-xs truncate text-xs">
                                        {{ org.address }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    <div>{{ org.email ?? '—' }}</div>
                                    <div class="text-xs">{{ org.phone ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2 text-xs text-muted-foreground">
                                        <span class="inline-flex items-center gap-1">
                                            <FolderKanban class="size-3" />
                                            {{ org.projects_count }} projects
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <Users class="size-3" />
                                            {{ org.procurement_opportunities_count }} bids
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="org.is_active ? 'default' : 'outline'">
                                        {{ org.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <RowActionsMenu :actions="organizationActions(org)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <MisPagination :pagination="organizations" />
            </CardContent>
        </Card>
    </MisPage>
</template>
