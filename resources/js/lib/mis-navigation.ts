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
import { dashboard } from '@/routes';
import type { NavGroup } from '@/types';

export const misNavGroups: NavGroup[] = [
    {
        label: 'Platform',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
            {
                title: 'Projects',
                href: '/projects',
                icon: Briefcase,
            },
            {
                title: 'Organizations',
                href: '/organizations',
                icon: Building2,
            },
            {
                title: 'Archive',
                href: '/archive',
                icon: Archive,
            },
            {
                title: 'Finance',
                href: '/finance',
                icon: DollarSign,
            },
        ],
    },
    {
        label: 'Human Resources',
        items: [
            {
                title: 'Employees',
                href: '/hr/employees',
                icon: Users,
            },
            {
                title: 'Contractors',
                href: '/hr/contractors',
                icon: UserRound,
            },
            {
                title: 'Attendance',
                href: '/hr/attendance',
                icon: CalendarDays,
            },
            {
                title: 'Payroll Adjustments',
                href: '/hr/payroll-adjustments',
                icon: Receipt,
            },
            {
                title: 'Payroll',
                href: '/hr/payroll',
                icon: Wallet,
            },
        ],
    },
    {
        label: 'Administration',
        items: [
            {
                title: 'Analytics',
                href: '/analytics/bidding',
                icon: BarChart3,
            },
            {
                title: 'Settings',
                href: '/settings/users',
                icon: Settings,
            },
        ],
    },
];

export const misQuickLinks = [
    {
        title: 'Document Archive',
        description: 'Incoming and outgoing documents',
        href: '/archive',
        icon: Archive,
    },
    {
        title: 'Finance',
        description: 'Income, expenses, and invoices',
        href: '/finance',
        icon: DollarSign,
    },
    {
        title: 'Attendance',
        description: 'Monthly attendance records',
        href: '/hr/attendance',
        icon: CalendarDays,
    },
    {
        title: 'Payroll',
        description: 'Monthly payroll runs',
        href: '/hr/payroll',
        icon: Wallet,
    },
] as const;
