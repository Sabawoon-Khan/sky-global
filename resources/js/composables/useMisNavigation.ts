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
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import type { NavGroup } from '@/types';

export function useMisNavigation() {
    const { t } = useTranslations();

    const misNavGroups = computed<NavGroup[]>(() => [
        {
            label: t('Platform'),
            items: [
                {
                    title: t('Dashboard'),
                    href: dashboard(),
                    icon: LayoutGrid,
                },
                {
                    title: t('Projects'),
                    href: '/projects',
                    icon: Briefcase,
                },
                {
                    title: t('Organizations'),
                    href: '/organizations',
                    icon: Building2,
                },
                {
                    title: t('Archive'),
                    href: '/archive',
                    icon: Archive,
                },
                {
                    title: t('Finance'),
                    href: '/finance',
                    icon: DollarSign,
                },
            ],
        },
        {
            label: t('Human Resources'),
            items: [
                {
                    title: t('Employees'),
                    href: '/hr/employees',
                    icon: Users,
                },
                {
                    title: t('Contractors'),
                    href: '/hr/contractors',
                    icon: UserRound,
                },
                {
                    title: t('Attendance'),
                    href: '/hr/attendance',
                    icon: CalendarDays,
                },
                {
                    title: t('Payroll Adjustments'),
                    href: '/hr/payroll-adjustments',
                    icon: Receipt,
                },
                {
                    title: t('Payroll'),
                    href: '/hr/payroll',
                    icon: Wallet,
                },
            ],
        },
        {
            label: t('Administration'),
            items: [
                {
                    title: t('Analytics'),
                    href: '/analytics/bidding',
                    icon: BarChart3,
                },
                {
                    title: t('Settings'),
                    href: '/settings/users',
                    icon: Settings,
                },
            ],
        },
    ]);

    const misQuickLinks = computed(() => [
        {
            title: t('Document Archive'),
            description: t('Incoming and outgoing documents'),
            href: '/archive',
            icon: Archive,
        },
        {
            title: t('Finance'),
            description: t('Income, expenses, and invoices'),
            href: '/finance',
            icon: DollarSign,
        },
        {
            title: t('Attendance'),
            description: t('Monthly attendance records'),
            href: '/hr/attendance',
            icon: CalendarDays,
        },
        {
            title: t('Payroll'),
            description: t('Monthly payroll runs'),
            href: '/hr/payroll',
            icon: Wallet,
        },
    ]);

    return {
        misNavGroups,
        misQuickLinks,
    };
}
