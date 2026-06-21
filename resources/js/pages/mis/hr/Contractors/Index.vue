<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Search, UserRound } from '@lucide/vue';
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
import type { RowActionItem } from '@/lib/row-actions';

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
    {
        label: 'View',
        icon: Eye,
        href: `/hr/contractors/${contractor.id}`,
    },
    {
        label: 'Edit',
        icon: Pencil,
        href: `/hr/contractors/${contractor.id}/edit`,
    },
];
</script>

<template>
    <Head title="Contractors" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                title="Contractors"
                description="Manage contractor personnel and agreements"
            />
            <Button as-child>
                <Link href="/hr/contractors/create">
                    <Plus class="size-4" />
                    Add Contractor
                </Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <UserRound class="size-5" />
                    All Contractors
                </CardTitle>
                <CardDescription>
                    {{ contractors.meta?.total ?? contractors.data.length }}
                    contractors
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <form
                    method="get"
                    action="/hr/contractors"
                    class="relative max-w-sm"
                >
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        name="search"
                        :default-value="filters?.search"
                        placeholder="Search contractors..."
                        class="pl-9"
                    />
                </form>

                <div
                    v-if="contractors.data.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    No contractors found.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-3 pr-4 font-medium">Name</th>
                                <th class="pb-3 pr-4 font-medium">Phone</th>
                                <th class="pb-3 pr-4 font-medium">Email</th>
                                <th class="pb-3 pr-4 font-medium">Status</th>
                                <th class="pb-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="contractor in contractors.data"
                                :key="contractor.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">
                                    <Link
                                        :href="`/hr/contractors/${contractor.id}`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ fullName(contractor) }}
                                    </Link>
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ contractor.phone ?? '—' }}
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ contractor.email ?? '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    <Badge
                                        :variant="
                                            contractor.status === 'active'
                                                ? 'default'
                                                : 'outline'
                                        "
                                    >
                                        {{ contractor.status }}
                                    </Badge>
                                </td>
                                <td class="py-3 text-right">
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
