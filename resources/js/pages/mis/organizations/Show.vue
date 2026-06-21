<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, FileText, FolderKanban, Mail, MapPin, Phone } from '@lucide/vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
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
import { formatCurrency } from '@/lib/format';

interface OrganizationType {
    id: number;
    name: string;
    color: string | null;
}

interface Contact {
    id: number;
    name: string;
    title: string | null;
    phone: string | null;
    email: string | null;
    is_primary: boolean;
}

interface Organization {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    province: string | null;
    address: string | null;
    tax_id: string | null;
    notes: string | null;
    is_active: boolean;
    organization_type: OrganizationType;
    contacts: Contact[];
    procurement_opportunities: Array<{
        id: number;
        title: string;
        status: string;
        submission_deadline: string | null;
        estimated_value: number | null;
        currency: string | null;
    }>;
    projects: Array<{
        id: number;
        code: string;
        name: string;
        status: string;
        total_contract_value: number | null;
        currency: string | null;
    }>;
    attachments: EntityAttachment[];
}

defineProps<{
    organization: Organization;
    organizationTypes: OrganizationType[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Organizations', href: '/organizations' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="organization.name" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <Heading
                    :title="organization.name"
                    :description="organization.organization_type.name"
                />
                <div class="mt-3 flex flex-wrap gap-2">
                    <Badge
                        :variant="organization.is_active ? 'default' : 'outline'"
                    >
                        {{ organization.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                    <Badge
                        variant="secondary"
                        :style="
                            organization.organization_type.color
                                ? {
                                      borderColor: organization.organization_type.color,
                                      color: organization.organization_type.color,
                                  }
                                : undefined
                        "
                    >
                        {{ organization.organization_type.name }}
                    </Badge>
                    <Badge v-if="organization.tax_id" variant="outline">
                        Tax ID: {{ organization.tax_id }}
                    </Badge>
                </div>
            </div>
            <Button variant="outline" as-child>
                <Link href="/organizations">Back to list</Link>
            </Button>
            <Button as-child>
                <Link :href="`/organizations/${organization.id}/edit`">Edit</Link>
            </Button>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Building2 class="size-5" />
                        Organization profile
                    </CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-2">
                    <div v-if="organization.phone" class="flex gap-3">
                        <Phone class="mt-0.5 size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">Phone</p>
                            <p class="text-sm">{{ organization.phone }}</p>
                        </div>
                    </div>
                    <div v-if="organization.email" class="flex gap-3">
                        <Mail class="mt-0.5 size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">Email</p>
                            <p class="text-sm">{{ organization.email }}</p>
                        </div>
                    </div>
                    <div v-if="organization.province || organization.address" class="flex gap-3 sm:col-span-2">
                        <MapPin class="mt-0.5 size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">Location</p>
                            <p class="text-sm">
                                {{ organization.province }}
                                <span v-if="organization.address"> — {{ organization.address }}</span>
                            </p>
                        </div>
                    </div>
                    <div v-if="organization.notes" class="sm:col-span-2">
                        <p class="text-xs text-muted-foreground">Notes</p>
                        <p class="mt-1 whitespace-pre-wrap text-sm">{{ organization.notes }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Summary</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Opportunities</span>
                        <span class="font-medium">{{ organization.procurement_opportunities.length }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Projects</span>
                        <span class="font-medium">{{ organization.projects.length }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Contacts</span>
                        <span class="font-medium">{{ organization.contacts.length }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card v-if="organization.contacts.length">
            <CardHeader>
                <CardTitle>Contacts</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-3 md:grid-cols-2">
                    <div
                        v-for="contact in organization.contacts"
                        :key="contact.id"
                        class="rounded-lg border p-4"
                    >
                        <div class="flex items-center justify-between">
                            <p class="font-medium">{{ contact.name }}</p>
                            <Badge v-if="contact.is_primary" variant="secondary">Primary</Badge>
                        </div>
                        <p v-if="contact.title" class="text-sm text-muted-foreground">{{ contact.title }}</p>
                        <p v-if="contact.phone" class="mt-2 text-sm">{{ contact.phone }}</p>
                        <p v-if="contact.email" class="text-sm">{{ contact.email }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <FileText class="size-5" />
                    Procurement opportunities
                </CardTitle>
                <CardDescription>RFPs and tenders from this organization</CardDescription>
            </CardHeader>
            <CardContent>
                <ul v-if="organization.procurement_opportunities.length" class="space-y-3">
                    <li
                        v-for="item in organization.procurement_opportunities"
                        :key="item.id"
                        class="flex flex-col gap-2 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <Link
                                :href="`/bidding/opportunities/${item.id}`"
                                class="font-medium hover:underline"
                            >
                                {{ item.title }}
                            </Link>
                            <p class="text-xs text-muted-foreground">
                                Deadline: {{ item.submission_deadline ?? 'Not set' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium">
                                {{ formatCurrency(item.estimated_value, item.currency) }}
                            </span>
                            <Badge variant="outline">{{ item.status }}</Badge>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">No opportunities recorded yet.</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <FolderKanban class="size-5" />
                    Projects
                </CardTitle>
                <CardDescription>Won contracts and active service delivery</CardDescription>
            </CardHeader>
            <CardContent>
                <ul v-if="organization.projects.length" class="space-y-3">
                    <li
                        v-for="project in organization.projects"
                        :key="project.id"
                        class="flex flex-col gap-2 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <Link :href="`/projects/${project.id}`" class="font-medium hover:underline">
                                {{ project.code }} — {{ project.name }}
                            </Link>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium">
                                {{ formatCurrency(project.total_contract_value, project.currency) }}
                            </span>
                            <Badge variant="outline">{{ project.status }}</Badge>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">No projects linked yet.</p>
            </CardContent>
        </Card>

        <EntityAttachments :attachments="organization.attachments" />
    </div>
</template>
