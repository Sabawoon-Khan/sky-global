<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editLanguage } from '@/routes/language';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';
import { computed } from 'vue';

const { t } = useTranslations();
const { can, canAny } = usePermissions();

const accountNavItems = computed<NavItem[]>(() => [
    {
        title: t('Profile'),
        href: editProfile(),
    },
    {
        title: t('Security'),
        href: editSecurity(),
    },
    {
        title: t('Appearance'),
        href: editAppearance(),
    },
    {
        title: t('Language'),
        href: editLanguage(),
    },
]);

const adminNavItems = computed<NavItem[]>(() =>
    [
        can('settings.manage_users')
            ? {
                  title: t('Users'),
                  href: '/settings/users',
              }
            : null,
        can('settings.manage_users')
            ? {
                  title: t('Roles'),
                  href: '/settings/roles',
              }
            : null,
        can('settings.edit')
            ? {
                  title: t('Organization Types'),
                  href: '/settings/organization-types',
              }
            : null,
        can('settings.edit')
            ? {
                  title: t('Form Types'),
                  href: '/settings/form-types',
              }
            : null,
        can('settings.edit')
            ? {
                  title: t('Currencies'),
                  href: '/settings/currencies',
              }
            : null,
    ].filter((item): item is NavItem => item !== null),
);

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            :title="t('Settings')"
            :description="t('Manage your profile and account settings')"
        />

        <div class="flex flex-col gap-6 lg:flex-row lg:gap-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-6 space-x-0" aria-label="Settings">
                    <div class="space-y-1">
                        <p class="px-2 text-xs font-medium text-muted-foreground">
                            {{ t('Account') }}
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
                    <div v-if="adminNavItems.length > 0" class="space-y-1">
                        <p class="px-2 text-xs font-medium text-muted-foreground">
                            {{ t('Administration') }}
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
                <section class="max-w-3xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
