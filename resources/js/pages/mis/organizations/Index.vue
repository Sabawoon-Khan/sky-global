<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, FolderKanban, Plus, Search, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import MisCreateButton from '@/components/MisCreateButton.vue';
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
import { useMisPage } from '@/composables/useMisPage';
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

const { t, viewAction, editAction, deleteAction, gateActions } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Organizations', href: '/organizations' },
        ],
    },
});

const organizationActions = (org: Organization): RowActionItem[] => [
    viewAction(`/organizations/${org.id}`),
    editAction(`/organizations/${org.id}/edit`, 'bidding.edit'),
    ...gateActions(
        [
            toggleIsActiveAction({
                url: `/organizations/${org.id}`,
                name: org.name,
                isActive: org.is_active,
                entityLabel: t('organization'),
                t,
            }),
        ],
        'bidding.edit',
    ),
    deleteAction(
        {
            href: `/organizations/${org.id}`,
            title: t('Delete organization'),
            description: t('Are you sure you want to delete ":name"? This cannot be undone.', {
                name: org.name,
            }),
        },
        'bidding.delete',
    ),
];
</script>

<template>
    <Head :title="t('Organizations')" />

    <MisPage>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                :title="t('Organizations')"
                :description="t('Register clients, partners, and procurement bodies')"
            />
            <MisCreateButton href="/organizations/create" permission="bidding.create">
                <Plus class="me-2 size-4" />
                {{ t('Add Organization') }}
            </MisCreateButton>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('Total registered') }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.total }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('Active organizations') }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.active }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('With active projects') }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.with_projects }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Building2 class="size-5" />
                    {{ t('All Organizations') }}
                </CardTitle>
                <CardDescription>
                    {{
                        t(':count organizations', {
                            count: String(
                                organizations.meta?.total ?? organizations.data.length,
                            ),
                        })
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <form method="get" action="/organizations" class="grid gap-4 md:grid-cols-3">
                    <div class="relative md:col-span-2">
                        <Search
                            class="absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            name="search"
                            :default-value="filters?.search ?? ''"
                            :placeholder="t('Search by name, email, phone, province...')"
                            class="ps-9"
                        />
                    </div>
                    <div class="flex gap-2">
                        <div class="grid flex-1 gap-1.5">
                            <Label for="organization_type_id" class="sr-only">{{
                                t('Type')
                            }}</Label>
                            <select
                                id="organization_type_id"
                                name="organization_type_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">{{ t('All types') }}</option>
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
                        <Button type="submit" variant="secondary">{{ t('Filter') }}</Button>
                    </div>
                </form>

                <div
                    v-if="organizations.data.length === 0"
                    class="rounded-lg border border-dashed p-10 text-center"
                >
                    <Building2 class="mx-auto mb-3 size-10 text-muted-foreground" />
                    <p class="font-medium">{{ t('No organizations yet') }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            t('Add government bodies, NGOs, private companies, and other clients you bid to or serve.')
                        }}
                    </p>
                    <MisCreateButton
                        href="/organizations/create"
                        permission="bidding.create"
                        class="mt-4"
                    >
                        {{ t('Create first organization') }}
                    </MisCreateButton>
                </div>

                <div v-else class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium">{{
                                    t('Organization')
                                }}</th>
                                <th class="px-4 py-3 text-start font-medium">{{ t('Type') }}</th>
                                <th class="px-4 py-3 text-start font-medium">{{
                                    t('Location')
                                }}</th>
                                <th class="px-4 py-3 text-start font-medium">{{
                                    t('Contact')
                                }}</th>
                                <th class="px-4 py-3 text-start font-medium">{{
                                    t('Activity')
                                }}</th>
                                <th class="px-4 py-3 text-start font-medium">{{
                                    t('Status')
                                }}</th>
                                <th class="px-4 py-3 text-end font-medium">{{ t('Actions') }}</th>
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
                                        {{ t('Tax ID') }}: {{ org.tax_id }}
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
                                            {{
                                                t(':count projects', {
                                                    count: String(org.projects_count),
                                                })
                                            }}
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <Users class="size-3" />
                                            {{
                                                t(':count bids', {
                                                    count: String(
                                                        org.procurement_opportunities_count,
                                                    ),
                                                })
                                            }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="org.is_active ? 'default' : 'outline'">
                                        {{ org.is_active ? t('Active') : t('Inactive') }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-end">
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
