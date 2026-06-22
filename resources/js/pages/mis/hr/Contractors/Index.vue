<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Search, UserRound } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
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
import type { RowActionItem } from '@/lib/row-actions';
import { personnelStatusActions } from '@/lib/status-actions';

interface Contractor {
    id: number;
    first_name: string;
    last_name: string;
    phone?: string | null;
    email?: string | null;
    status: string;
}

interface PaginatedContractors {
    data: Contractor[];
    meta?: { total: number };
}

interface Props {
    contractors: PaginatedContractors;
    filters?: { search?: string; status?: string };
}

defineProps<Props>();

const { t, viewAction, editAction } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Contractors', href: '/hr/contractors' },
        ],
    },
});

const fullName = (contractor: Contractor): string =>
    `${contractor.first_name} ${contractor.last_name}`;

const contractorActions = (contractor: Contractor): RowActionItem[] => [
    viewAction(`/hr/contractors/${contractor.id}`),
    editAction(`/hr/contractors/${contractor.id}/edit`),
    ...personnelStatusActions({
        url: `/hr/contractors/${contractor.id}`,
        name: fullName(contractor),
        status: contractor.status,
        t,
    }),
];
</script>

<template>
    <Head :title="t('Contractors')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                :title="t('Contractors')"
                :description="t('Manage contractor personnel and agreements')"
            />
            <Button as-child>
                <Link href="/hr/contractors/create">
                    <Plus class="me-1 size-4" />
                    {{ t('Add Contractor') }}
                </Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <UserRound class="size-5" />
                    {{ t('All Contractors') }}
                </CardTitle>
                <CardDescription>
                    {{
                        t(':count contractors', {
                            count: String(
                                contractors.meta?.total ?? contractors.data.length,
                            ),
                        })
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <form
                    method="get"
                    action="/hr/contractors"
                    class="relative max-w-sm"
                >
                    <Search
                        class="absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        name="search"
                        :default-value="filters?.search"
                        :placeholder="t('Search contractors...')"
                        class="ps-9"
                    />
                </form>

                <div
                    v-if="contractors.data.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    {{ t('No contractors found.') }}
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-start text-muted-foreground">
                                <th class="pb-3 pe-4 font-medium">{{ t('Name') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{ t('Phone') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{ t('Email') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{ t('Status') }}</th>
                                <th class="pb-3 text-end font-medium">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="contractor in contractors.data"
                                :key="contractor.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pe-4">
                                    <Link
                                        :href="`/hr/contractors/${contractor.id}`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ fullName(contractor) }}
                                    </Link>
                                </td>
                                <td class="py-3 pe-4 text-muted-foreground">
                                    {{ contractor.phone ?? '—' }}
                                </td>
                                <td class="py-3 pe-4 text-muted-foreground">
                                    {{ contractor.email ?? '—' }}
                                </td>
                                <td class="py-3 pe-4">
                                    <Badge
                                        :variant="
                                            contractor.status === 'active'
                                                ? 'default'
                                                : 'outline'
                                        "
                                    >
                                        {{
                                            contractor.status === 'active'
                                                ? t('Active')
                                                : contractor.status === 'inactive'
                                                  ? t('Inactive')
                                                  : contractor.status
                                        }}
                                    </Badge>
                                </td>
                                <td class="py-3 text-end">
                                    <RowActionsMenu
                                        :actions="contractorActions(contractor)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
