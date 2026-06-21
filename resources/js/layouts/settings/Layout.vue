<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const accountNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Security',
        href: editSecurity(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Users',
        href: '/settings/users',
    },
    {
        title: 'Organization Types',
        href: '/settings/organization-types',
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-6 space-x-0" aria-label="Settings">
                    <div class="space-y-1">
                        <p class="px-2 text-xs font-medium text-muted-foreground">
                            Account
                        </p>
                        <Button
                            v-for="item in accountNavItems"
                            :key="toUrl(item.href)"
                            variant="ghost"
                            :class="[
                                'w-full justify-start',
                                { 'bg-muted': isCurrentOrParentUrl(item.href) },
                            ]"
                            as-child
                        >
                            <Link :href="item.href">
                                <component
                                    :is="item.icon"
                                    v-if="item.icon"
                                    class="h-4 w-4"
                                />
                                {{ item.title }}
                            </Link>
                        </Button>
                    </div>
                    <div class="space-y-1">
                        <p class="px-2 text-xs font-medium text-muted-foreground">
                            Administration
                        </p>
                        <Button
                            v-for="item in adminNavItems"
                            :key="toUrl(item.href)"
                            variant="ghost"
                            :class="[
                                'w-full justify-start',
                                { 'bg-muted': isCurrentOrParentUrl(item.href) },
                            ]"
                            as-child
                        >
                            <Link :href="item.href">
                                <component
                                    :is="item.icon"
                                    v-if="item.icon"
                                    class="h-4 w-4"
                                />
                                {{ item.title }}
                            </Link>
                        </Button>
                    </div>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
