import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    /** When true, only the exact path matches (no prefix children). */
    exact?: boolean;
    matchPrefixes?: string[];
    createHref?: string;
    createPermission?: string;
};

export type NavGroup = {
    label: string;
    items: NavItem[];
};
