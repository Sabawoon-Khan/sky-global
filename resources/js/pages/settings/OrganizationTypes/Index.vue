<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Plus, Tags } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
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

interface OrganizationType {
    id: number;
    name: string;
    color?: string | null;
    description?: string | null;
    organizations_count?: number;
}

interface Props {
    organizationTypes: OrganizationType[];
}

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            {
                title: 'Organization Types',
                href: '/settings/organization-types',
            },
        ],
    },
});
</script>

<template>
    <Head title="Organization Types" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Organization Types"
            description="Configure client categories and classification colors"
        />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Tags class="size-5" />
                    Types
                </CardTitle>
                <CardDescription>
                    {{ organizationTypes.length }} organization types
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <Form
                    action="/settings/organization-types"
                    method="post"
                    class="grid gap-4 rounded-lg border p-4 sm:grid-cols-2"
                    v-slot="{ processing }"
                >
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            placeholder="e.g. Government"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="color">Color</Label>
                        <Input
                            id="color"
                            name="color"
                            type="color"
                            class="h-10 w-full cursor-pointer p-1"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            name="description"
                            placeholder="Optional description"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <Button type="submit" :disabled="processing">
                            <Plus class="size-4" />
                            Add Type
                        </Button>
                    </div>
                </Form>

                <div
                    v-if="organizationTypes.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    No organization types configured.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="type in organizationTypes"
                        :key="type.id"
                        class="flex items-center justify-between rounded-lg border px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="size-4 rounded-full border"
                                :style="{
                                    backgroundColor: type.color ?? 'var(--primary)',
                                }"
                            />
                            <div>
                                <div class="font-medium">{{ type.name }}</div>
                                <p
                                    v-if="type.description"
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ type.description }}
                                </p>
                            </div>
                        </div>
                        <span class="text-sm text-muted-foreground">
                            {{ type.organizations_count ?? 0 }} organizations
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
