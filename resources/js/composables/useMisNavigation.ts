import {
    Archive,
    BarChart3,
    Briefcase,
    Building2,
    CalendarDays,
    DollarSign,
    LayoutGrid,
    Receipt,
    Settings,
    UserRound,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import type { NavGroup, NavItem } from '@/types';

type NavItemWithPermission = NavItem & {
    permission?: string | string[];
};

type QuickLinkWithPermission = {
    title: string;
    description: string;
    href: NavItem['href'];
    icon: NavItem['icon'];
    permission?: string | string[];
};

export function useMisNavigation() {
    const { t } = useTranslations();
    const { can, canAny } = usePermissions();

    const hasNavPermission = (permission?: string | string[]): boolean => {
        if (!permission) {
            return true;
        }

        if (Array.isArray(permission)) {
            return canAny(permission);
        }

        return can(permission);
    };

    const filterByPermission = (items: NavItemWithPermission[]): NavItem[] =>
        items
            .filter((item) => hasNavPermission(item.permission))
            .map((item) => ({
                title: item.title,
                href: item.href,
                icon: item.icon,
                isActive: item.isActive,
            }));

    const filterQuickLinks = (items: QuickLinkWithPermission[]) =>
        items
            .filter((item) => hasNavPermission(item.permission))
            .map((item) => ({
                title: item.title,
                description: item.description,
                href: item.href,
                icon: item.icon,
            }));

    const misNavGroups = computed<NavGroup[]>(() => {
        const groups: NavGroup[] = [
            {
                label: t('Platform'),
                items: filterByPermission([
                    {
                        title: t('Dashboard'),
                        href: dashboard(),
                        icon: LayoutGrid,
                    },
                    {
                        title: t('Projects'),
                        href: '/projects',
                        icon: Briefcase,
                        permission: 'projects.view',
                    },
                    {
                        title: t('Organizations'),
                        href: '/organizations',
                        icon: Building2,
                        permission: 'bidding.view',
                    },
                    {
                        title: t('Archive'),
                        href: '/archive',
                        icon: Archive,
                        permission: 'archive.view',
                    },
                    {
                        title: t('Finance'),
                        href: '/finance',
                        icon: DollarSign,
                        permission: 'finance.view',
                    },
                ]),
            },
            {
                label: t('Human Resources'),
                items: filterByPermission([
                    {
                        title: t('Employees'),
                        href: '/hr/employees',
                        icon: Users,
                        permission: 'hr.view',
                    },
                    {
                        title: t('Contractors'),
                        href: '/hr/contractors',
                        icon: UserRound,
                        permission: 'hr.view',
                    },
                    {
                        title: t('Attendance'),
                        href: '/hr/attendance',
                        icon: CalendarDays,
                        permission: 'hr.view',
                    },
                    {
                        title: t('Payroll Adjustments'),
                        href: '/hr/payroll-adjustments',
                        icon: Receipt,
                        permission: 'hr.view',
                    },
                    {
                        title: t('Payroll'),
                        href: '/hr/payroll',
                        icon: Wallet,
                        permission: 'hr.view',
                    },
                ]),
            },
            {
                label: t('Administration'),
                items: filterByPermission([
                    {
                        title: t('Analytics'),
                        href: can('bidding.view')
                            ? '/analytics/bidding'
                            : '/analytics/finance',
                        icon: BarChart3,
                        permission: ['bidding.view', 'finance.view'],
                    },
                    {
                        title: t('Settings'),
                        href: can('settings.manage_users')
                            ? '/settings/users'
                            : '/settings/organization-types',
                        icon: Settings,
                        permission: ['settings.manage_users', 'settings.edit'],
                    },
                ]),
            },
        ];

        return groups.filter((group) => group.items.length > 0);
    });

    const misQuickLinks = computed(() =>
        filterQuickLinks([
            {
                title: t('Document Archive'),
                description: t('Incoming and outgoing documents'),
                href: '/archive',
                icon: Archive,
                permission: 'archive.view',
            },
            {
                title: t('Finance'),
                description: t('Income, expenses, and invoices'),
                href: '/finance',
                icon: DollarSign,
                permission: 'finance.view',
            },
            {
                title: t('Attendance'),
                description: t('Monthly attendance records'),
                href: '/hr/attendance',
                icon: CalendarDays,
                permission: 'hr.view',
            },
            {
                title: t('Payroll'),
                description: t('Monthly payroll runs'),
                href: '/hr/payroll',
                icon: Wallet,
                permission: 'hr.view',
            },
        ]),
    );

    return {
        misNavGroups,
        misQuickLinks,
    };
}
