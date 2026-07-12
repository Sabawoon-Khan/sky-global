<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Building2,
    CheckCircle2,
    FileText,
    FolderKanban,
    Search,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import MisCreateButton from '@/components/MisCreateButton.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useMisPage } from '@/composables/useMisPage';
import { formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { toggleIsActiveAction } from '@/lib/status-actions';
import { cn } from '@/lib/utils';

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
        inactive: number;
        with_projects: number;
        with_opportunities: number;
    };
    filters?: {
        search?: string | null;
        organization_type_id?: number | null;
    };
}

const props = defineProps<Props>();

const { t, viewAction, editAction, deleteAction, gateActions } = useMisPage();

const statCards = computed(() => {
    const { stats, organizationTypes } = props;
    const projectRate =
        stats.total > 0
            ? Math.round((stats.with_projects / stats.total) * 100)
            : 0;

    return [
        {
            label: t('Total registered'),
            value: formatNumber(stats.total),
            sub:
                organizationTypes.length > 0
                    ? t(':count organization types', {
                          count: String(organizationTypes.length),
                      })
                    : t('Clients, partners, and bidders'),
            icon: Building2,
            accent: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
        },
        {
            label: t('Active organizations'),
            value: formatNumber(stats.active),
            sub:
                stats.inactive > 0
                    ? t(':count inactive', { count: String(stats.inactive) })
                    : t('All organizations active'),
            icon: CheckCircle2,
            accent: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        },
        {
            label: t('With active projects'),
            value: formatNumber(stats.with_projects),
            sub: t(':percent of total', { percent: String(projectRate) }),
            icon: FolderKanban,
            accent: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
        },
        {
            label: t('With opportunities'),
            value: formatNumber(stats.with_opportunities),
            sub: t('Organizations with tracked bids'),
            icon: FileText,
            accent: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        },
    ];
});

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
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card
                v-for="card in statCards"
                :key="card.label"
                class="ui-surface-hover overflow-hidden transition-all"
            >
                <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        {{ card.label }}
                    </CardTitle>
                    <div
                        :class="
                            cn(
                                'flex size-9 shrink-0 items-center justify-center rounded-lg',
                                card.accent,
                            )
                        "
                    >
                        <component :is="card.icon" class="size-4" stroke-width="2" />
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold tracking-tight tabular-nums">
                        {{ card.value }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ card.sub }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Building2 class="size-5" />
                    {{ t('All Organizations') }}
                </CardTitle>
                <CardAction>
                    <MisCreateButton href="/organizations/create" permission="bidding.create">
                        {{ t('Add Organization') }}
                    </MisCreateButton>
                </CardAction>
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
                    class="ui-empty-state"
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
