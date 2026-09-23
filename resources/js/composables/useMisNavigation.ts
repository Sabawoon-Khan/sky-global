import {
    Award,
    Archive,
    BookOpen,
    ArrowDownRight,
    ArrowUpRight,
    BarChart3,
    Briefcase,
    Building2,
    CalendarDays,
    ClipboardList,
    DollarSign,
    FileText,
    GraduationCap,
    LayoutGrid,
    Package,
    Percent,
    Receipt,
    Settings,
    Shield,
    UserRound,
    Users,
    Wallet,
} from '@lucide/vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { NavGroup, NavItem } from '@/types';

type NavItemWithPermission = NavItem & {
    permission?: string | string[];
    showWhenAssignee?: boolean;
};

type QuickLinkWithPermission = {
    title: string;
    description: string;
    href: NavItem['href'];
    icon: NavItem['icon'];
    permission?: string | string[];
};

function pathOf(href: NavItem['href']): string {
    const raw = toUrl(href).split('?')[0] ?? '';

    if (!raw.startsWith('http')) {
        return raw || '/';
    }

    try {
        return new URL(raw).pathname;
    } catch {
        return raw;
    }
}

export function isNavItemActive(item: NavItem, currentPath: string): boolean {
    if (item.matchPrefixes?.length) {
        return item.matchPrefixes.some(
            (prefix) =>
                currentPath === prefix || currentPath.startsWith(`${prefix}/`),
        );
    }

    const hrefPath = pathOf(item.href);

    if (currentPath === hrefPath) {
        return true;
    }

    if (item.exact) {
        return false;
    }

    return hrefPath !== '/' && currentPath.startsWith(`${hrefPath}/`);
}

export function useMisNavigation() {
    const page = usePage();
    const { t } = useTranslations();
    const { can, canAny } = usePermissions();

    const isAssignmentAssignee = computed(
        () =>
            Boolean(
                (
                    page.props.auth as {
                        user?: { assignments?: { is_assignee?: boolean } };
                    }
                )?.user?.assignments?.is_assignee,
            ),
    );

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
            .filter(
                (item) =>
                    (item.showWhenAssignee && isAssignmentAssignee.value) ||
                    hasNavPermission(item.permission),
            )
            .map((item) => ({
                title: item.title,
                href: item.href,
                icon: item.icon,
                isActive: item.isActive,
                exact: item.exact,
                matchPrefixes: item.matchPrefixes,
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

    const tabGroups = computed<NavGroup[]>(() => {
        const groups: NavGroup[] = [
            {
                label: t('Dashboard'),
                items: filterByPermission([
                    {
                        title: t('Dashboard'),
                        href: dashboard(),
                        icon: LayoutGrid,
                    },
                ]),
            },
            {
                label: t('Assignments'),
                items: filterByPermission([
                    {
                        title: t('Assignments'),
                        href: '/assignments',
                        icon: ClipboardList,
                        permission: 'assignments.view',
                        showWhenAssignee: true,
                    },
                ]),
            },
            {
                label: t('Projects'),
                items: filterByPermission([
                    {
                        title: t('Projects'),
                        href: '/mis/projects',
                        icon: Briefcase,
                        permission: 'projects.view',
                    },
                ]),
            },
            {
                label: t('Organizations'),
                items: filterByPermission([
                    {
                        title: t('Organizations'),
                        href: '/organizations',
                        icon: Building2,
                        permission: 'bidding.view',
                    },
                ]),
            },
            {
                label: t('Archive'),
                items: filterByPermission([
                    {
                        title: t('Archive'),
                        href: '/archive',
                        icon: Archive,
                        permission: 'archive.view',
                    },
                ]),
            },
            {
                label: t('Finance'),
                items: filterByPermission([
                    {
                        title: t('Overview'),
                        href: '/finance',
                        icon: DollarSign,
                        permission: 'finance.view',
                        exact: true,
                    },
                    {
                        title: t('Monthly ledger'),
                        href: '/finance/ledger',
                        icon: BookOpen,
                        permission: 'finance.view',
                    },
                    {
                        title: t('Tax'),
                        href: '/finance/tax',
                        icon: Percent,
                        permission: 'finance.view',
                    },
                    {
                        title: t('Project Income'),
                        href: '/finance/income',
                        icon: ArrowUpRight,
                        permission: 'finance.view',
                    },
                    {
                        title: t('Project Expenses'),
                        href: '/finance/expenses',
                        icon: ArrowDownRight,
                        permission: 'finance.view',
                    },
                    {
                        title: t('Other Income'),
                        href: '/finance/general-income',
                        icon: Wallet,
                        permission: 'finance.view',
                    },
                    {
                        title: t('Overhead & Salaries'),
                        href: '/finance/general-expenses',
                        icon: Receipt,
                        permission: 'finance.view',
                    },
                    {
                        title: t('Invoices'),
                        href: '/finance/invoices',
                        icon: FileText,
                        permission: 'finance.view',
                    },
                    {
                        title: t('Quotations'),
                        href: '/finance/quotations',
                        icon: ClipboardList,
                        permission: 'finance.view',
                    },
                ]),
            },
            {
                label: t('Stock / Inventory'),
                items: filterByPermission([
                    {
                        title: t('Stock / Inventory'),
                        href: '/equipment',
                        icon: Package,
                        permission: 'inventory.view',
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
                label: t('Training'),
                items: filterByPermission([
                    {
                        title: t('Guards'),
                        href: '/training/guards',
                        icon: GraduationCap,
                        permission: 'training.view',
                    },
                    {
                        title: t('Public Protection Deputy'),
                        href: '/training/payments',
                        icon: Shield,
                        permission: 'training.view',
                    },
                    {
                        title: t('Company training'),
                        href: '/training/company',
                        icon: Building2,
                        permission: 'training.view',
                    },
                    {
                        title: t('Certificates'),
                        href: '/training/certificates',
                        icon: Award,
                        permission: 'training.view',
                    },
                    {
                        title: t('On-site training'),
                        href: '/training/field/reports',
                        icon: ClipboardList,
                        permission: 'training.view',
                        matchPrefixes: ['/training/field'],
                    },
                ]),
            },
            {
                label: t('Analytics'),
                items: filterByPermission([
                    {
                        title: t('Bidding'),
                        href: '/analytics/bidding',
                        icon: BarChart3,
                        permission: 'bidding.view',
                    },
                    {
                        title: t('Finance'),
                        href: '/analytics/finance',
                        icon: DollarSign,
                        permission: 'finance.view',
                    },
                    {
                        title: t('Reports'),
                        href: '/analytics/reports',
                        icon: FileText,
                        permission: [
                            'projects.view',
                            'bidding.view',
                            'finance.view',
                            'hr.view',
                            'training.view',
                            'inventory.view',
                            'archive.view',
                        ],
                    },
                ]),
            },
            {
                label: t('Settings'),
                items: filterByPermission([
                    {
                        title: t('Settings'),
                        href: can('settings.manage_users')
                            ? '/settings/users'
                            : '/settings/organization-types',
                        icon: Settings,
                        permission: ['settings.manage_users', 'settings.edit'],
                        matchPrefixes: ['/settings'],
                    },
                ]),
            },
        ];

        return groups.filter((group) => group.items.length > 0);
    });

    /** Legacy flat groups used by older sidebar components. */
    const misNavGroups = computed<NavGroup[]>(() => {
        const platformLabels = new Set([
            t('Dashboard'),
            t('Assignments'),
            t('Projects'),
            t('Organizations'),
            t('Archive'),
            t('Stock / Inventory'),
        ]);
        const platform = tabGroups.value
            .filter((group) => platformLabels.has(group.label))
            .flatMap((group) => group.items);
        const finance = tabGroups.value.find(
            (group) => group.label === t('Finance'),
        );
        const hr = tabGroups.value.find(
            (group) => group.label === t('Human Resources'),
        );
        const training = tabGroups.value.find(
            (group) => group.label === t('Training'),
        );
        const analytics = tabGroups.value.find(
            (group) => group.label === t('Analytics'),
        );
        const settings = tabGroups.value.find(
            (group) => group.label === t('Settings'),
        );

        const groups: NavGroup[] = [];

        if (platform.length) {
            groups.push({ label: t('Platform'), items: platform });
        }

        if (finance?.items.length) {
            groups.push({ label: t('Finance'), items: finance.items });
        }

        if (hr?.items.length) {
            groups.push({ label: t('Human Resources'), items: hr.items });
        }

        if (training?.items.length) {
            groups.push({ label: t('Training'), items: training.items });
        }

        const adminItems: NavItem[] = [];

        if (analytics?.items.length) {
            adminItems.push({
                title: t('Analytics'),
                href: analytics.items[0]!.href,
                icon: BarChart3,
                matchPrefixes: ['/analytics'],
            });
        }

        if (settings?.items.length) {
            adminItems.push(settings.items[0]!);
        }

        if (adminItems.length) {
            groups.push({ label: t('Administration'), items: adminItems });
        }

        return groups;
    });

    const navGroups = computed<NavGroup[]>(() => {
        const items: NavItem[] = [];

        for (const group of tabGroups.value) {
            if (group.items.length === 1) {
                items.push(group.items[0]!);
                continue;
            }

            const first = group.items[0];
            if (!first) continue;

            items.push({
                title: group.label,
                href: first.href,
                icon: first.icon,
                matchPrefixes: group.items.map((entry) => pathOf(entry.href)),
            });
        }

        return items.length
            ? [{ label: t('Navigation'), items }]
            : [];
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
            {
                title: t('Training'),
                description: t('Register guards and issue certificates'),
                href: '/training/guards',
                icon: GraduationCap,
                permission: 'training.view',
            },
        ]),
    );

    const allNavItems = computed(() =>
        tabGroups.value.flatMap((group) => group.items),
    );

    return {
        misNavGroups,
        misQuickLinks,
        tabGroups,
        navGroups,
        allNavItems,
    };
}
