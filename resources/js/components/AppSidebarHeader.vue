<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Bell, Search } from '@lucide/vue';
import { computed } from 'vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useInitials } from '@/composables/useInitials';
import { useTranslations } from '@/composables/useTranslations';
import type { BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const { t } = useTranslations();
const { getInitials } = useInitials();

const user = computed(() => page.props.auth.user);

const pageTitle = computed(() => {
    const last = props.breadcrumbs.at(-1);
    return last ? t(last.title) : t('Dashboard');
});

const showAvatar = computed(
    () => user.value?.avatar && user.value.avatar !== '',
);
</script>

<template>
    <header
        class="glass-header sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-4 border-b border-slate-200/70 px-6 md:px-8 dark:border-white/10"
    >
        <div class="flex min-w-0 items-center gap-3">
            <SidebarTrigger class="shrink-0 md:hidden" />
            <h1 class="truncate text-lg font-semibold tracking-tight text-foreground">
                {{ pageTitle }}
            </h1>
        </div>

        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            <div class="relative hidden sm:block">
                <Search
                    class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    type="search"
                    :placeholder="t('Search...')"
                    class="h-9 w-56 rounded-full border-slate-200/80 bg-white/90 ps-9 shadow-sm md:w-64 dark:bg-card/90"
                />
            </div>

            <LanguageSwitcher />

            <Button
                variant="ghost"
                size="icon"
                class="relative size-9 rounded-full text-muted-foreground hover:text-foreground"
            >
                <Bell class="size-[18px]" stroke-width="1.75" />
                <span
                    class="absolute end-2 top-2 size-2 rounded-full bg-school-gold ring-2 ring-background"
                />
                <span class="sr-only">{{ t('Notifications') }}</span>
            </Button>

            <DropdownMenu v-if="user">
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        class="size-9 rounded-full p-0 hover:bg-accent"
                    >
                        <Avatar class="size-9">
                            <AvatarImage
                                v-if="showAvatar"
                                :src="user.avatar!"
                                :alt="user.name"
                            />
                            <AvatarFallback
                                class="bg-school-navy text-xs font-semibold text-white"
                            >
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="end"
                    class="min-w-56 rounded-2xl border-border/60 p-1.5 shadow-[0_20px_60px_-20px_rgba(15,23,42,0.25)] backdrop-blur-md"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
