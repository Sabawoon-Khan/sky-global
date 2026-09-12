<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    Building2,
    DollarSign,
    FileText,
    FolderKanban,
    Mail,
    MapPin,
    Phone,
    Send,
} from '@lucide/vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
import MisTabs from '@/components/MisTabs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    V2DetailHero,
    V2ListPage,
    V2Panel,
    V2StatCard,
    V2StatGrid,
} from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
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
        bid_submitted_at?: string | null;
    }>;
    attachments: EntityAttachment[];
}

interface OrgStats {
    projects_total: number;
    projects_completed: number;
    bids_submitted: number;
    opportunities_total: number;
    total_contract_value: number;
    total_income: number;
    contacts_count: number;
}

const props = defineProps<{
    organization: Organization;
    organizationTypes: OrganizationType[];
    stats: OrgStats;
}>();

const { t, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Organizations', href: '/organizations' },
            { title: 'Details', href: '#' },
        ],
    },
});

type TabId = 'overview' | 'projects' | 'bids' | 'opportunities' | 'contacts' | 'attachments';

const tabs = computed(() => [
    { id: 'overview' as const, label: t('Overview') },
    { id: 'projects' as const, label: `${t('Projects')} (${props.stats.projects_total})` },
    { id: 'bids' as const, label: `${t('Bids Submitted')} (${props.stats.bids_submitted})` },
    { id: 'opportunities' as const, label: `${t('Opportunities')} (${props.stats.opportunities_total})` },
    { id: 'contacts' as const, label: `${t('Contacts')} (${props.stats.contacts_count})` },
    { id: 'attachments' as const, label: t('Attachments') },
]);

const activeTab = ref<TabId>('overview');

const submittedProjects = computed(() =>
    props.organization.projects.filter(
        (p) =>
            p.bid_submitted_at ||
            ['submitted', 'won', 'lost', 'active', 'completed', 'closed'].includes(
                p.status,
            ),
    ),
);

const completedProjects = computed(() =>
    props.organization.projects.filter((p) =>
        ['completed', 'closed', 'won'].includes(p.status),
    ),
);
</script>

<template>
    <Head :title="organization.name" />

    <V2ListPage>
        <V2DetailHero image="/images/gs-hero-operations.png">
            <template #eyebrow>{{ t('Organizations') }}</template>
            <template #title>{{ organization.name }}</template>
            <template #description>
                <span class="flex flex-wrap gap-2">
                    <Badge :variant="organization.is_active ? 'default' : 'outline'">
                        {{ organization.is_active ? t('Active') : t('Inactive') }}
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
                </span>
            </template>
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/organizations">{{ t('Back to list') }}</Link>
                </Button>
                <Button v-if="can('bidding.edit')" as-child>
                    <Link :href="`/organizations/${organization.id}/edit`">{{
                        t('Edit')
                    }}</Link>
                </Button>
            </template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Projects Done')"
                        :value="`${stats.projects_completed} / ${stats.projects_total}`"
                    />
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('Bids Submitted')"
                        :value="stats.bids_submitted"
                    />
                    <V2StatCard
                        :delay="2"
                        :title="t('Contract Value')"
                        :value="formatCurrency(stats.total_contract_value)"
                    />
                    <V2StatCard
                        :delay="3"
                        icon-tone="teal"
                        :title="t('Total Income')"
                        :value="formatCurrency(stats.total_income)"
                    />
                </V2StatGrid>
            </template>
        </V2DetailHero>

        <MisTabs v-model="activeTab" :tabs="tabs" nowrap />

        <V2Panel
            v-if="activeTab === 'overview'"
            :title="t('Organization profile')"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div v-if="organization.phone" class="flex gap-3">
                    <Phone class="mt-0.5 size-4 text-muted-foreground" />
                    <div>
                        <p class="text-xs text-muted-foreground">{{ t('Phone') }}</p>
                        <p class="text-sm">{{ organization.phone }}</p>
                    </div>
                </div>
                <div v-if="organization.email" class="flex gap-3">
                    <Mail class="mt-0.5 size-4 text-muted-foreground" />
                    <div>
                        <p class="text-xs text-muted-foreground">{{ t('Email') }}</p>
                        <p class="text-sm">{{ organization.email }}</p>
                    </div>
                </div>
                <div
                    v-if="organization.province || organization.address"
                    class="flex gap-3 sm:col-span-2"
                >
                    <MapPin class="mt-0.5 size-4 text-muted-foreground" />
                    <div>
                        <p class="text-xs text-muted-foreground">{{ t('Location') }}</p>
                        <p class="text-sm">
                            {{ organization.province }}
                            <span v-if="organization.address">
                                — {{ organization.address }}</span
                            >
                        </p>
                    </div>
                </div>
                <div v-if="organization.notes" class="sm:col-span-2">
                    <p class="text-xs text-muted-foreground">{{ t('Notes') }}</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm">{{ organization.notes }}</p>
                </div>
            </div>
        </V2Panel>

        <V2Panel v-else-if="activeTab === 'projects'" :title="t('Projects')">
                <ul v-if="organization.projects.length" class="divide-y">
                    <li
                        v-for="project in organization.projects"
                        :key="project.id"
                        class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <Link
                                :href="`/mis/projects/${project.id}`"
                                class="font-medium hover:underline"
                            >
                                {{ project.code }} — {{ project.name }}
                            </Link>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium">
                                {{
                                    formatCurrency(
                                        project.total_contract_value,
                                        project.currency,
                                    )
                                }}
                            </span>
                            <Badge variant="outline">{{ project.status }}</Badge>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('No projects linked yet.') }}
                </p>
        </V2Panel>

        <V2Panel v-else-if="activeTab === 'bids'" :title="t('Bids Submitted')">
                <ul v-if="submittedProjects.length" class="divide-y">
                    <li
                        v-for="project in submittedProjects"
                        :key="project.id"
                        class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <Link
                            :href="`/mis/projects/${project.id}?tab=bid`"
                            class="font-medium hover:underline"
                        >
                            {{ project.code }} — {{ project.name }}
                        </Link>
                        <Badge variant="outline">{{ project.status }}</Badge>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('No bids submitted yet.') }}
                </p>
        </V2Panel>

        <V2Panel
            v-else-if="activeTab === 'opportunities'"
            :title="t('Procurement opportunities')"
        >
                <ul v-if="organization.procurement_opportunities.length" class="divide-y">
                    <li
                        v-for="item in organization.procurement_opportunities"
                        :key="item.id"
                        class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="font-medium">{{ item.title }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ t('Deadline:') }}
                                {{ item.submission_deadline ?? t('Not set') }}
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
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('No opportunities recorded yet.') }}
                </p>
        </V2Panel>

        <V2Panel v-else-if="activeTab === 'contacts'" :title="t('Contacts')">
                <div
                    v-if="organization.contacts.length"
                    class="grid gap-3 md:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="contact in organization.contacts"
                        :key="contact.id"
                        class="ui-inset-panel"
                    >
                        <div class="flex items-center justify-between">
                            <p class="font-medium">{{ contact.name }}</p>
                            <Badge v-if="contact.is_primary" variant="secondary">{{
                                t('Primary')
                            }}</Badge>
                        </div>
                        <p v-if="contact.title" class="text-sm text-muted-foreground">
                            {{ contact.title }}
                        </p>
                        <p v-if="contact.phone" class="mt-2 text-sm">{{ contact.phone }}</p>
                        <p v-if="contact.email" class="text-sm">{{ contact.email }}</p>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('No contacts on file.') }}
                </p>
        </V2Panel>

        <!-- Attachments -->
        <EntityAttachments
            v-else-if="activeTab === 'attachments'"
            :attachments="organization.attachments"
        />
    </V2ListPage>
</template>
