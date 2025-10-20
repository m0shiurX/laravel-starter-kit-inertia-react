import { type NavItem, type NavSection } from '@/types';
import { Banknote, BarChartBig, CalendarCheck, CreditCard, FileText, HomeIcon, Settings2, Shield, Stethoscope, UserCircle2, Users } from 'lucide-react';

export function useAdminNavigation() {
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: HomeIcon,
        },
    ];

    const managementItems: NavItem[] = [
        {
            title: 'User Management',
            icon: Users,
            childRoutes: [
                {
                    title: 'Admins',
                    href: '/users',
                },
                {
                    title: 'Create User',
                    href: '/users/create',
                },
            ],
        },
        {
            title: 'Roles & Permissions',
            href: '/roles',
            icon: Shield,
        },
        {
            title: 'Activity Log',
            href: '/activities',
            icon: FileText,
        },
    ];

    const testSection: NavItem[] = [

        {
            title: 'Reservations',
            href: '/reservations',
            icon: CalendarCheck,
        },
        {
            title: 'Patients',
            href: '/patients',
            icon: UserCircle2,
        },
        {
            title: 'Treatments',
            href: '/treatments',
            icon: Stethoscope,
        },
        {
            title: 'Staff List',
            href: '/staff-list',
            icon: Users,
        },
    ];
    const financeSection: NavItem[] = [

        {
            title: 'Accounts',
            href: '/accounts',
            icon: Banknote,
        },
        {
            title: 'Sales',
            href: '/sales',
            icon: BarChartBig,
        },
        {
            title: 'Payment Methods',
            href: '/payment-methods',
            icon: CreditCard,
        },
        {
            title: 'Staff List',
            href: '/staff-list',
            icon: Users,
        },
    ];

    const navSections: NavSection[] = [
        {
            items: mainNavItems,
        },
        {
            title: 'Clinic',
            items: testSection,
        },
        {
            title: 'Finance',
            items: financeSection,
        },
        {
            title: 'Management',
            items: managementItems,
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Settings',
            href: '/settings',
            icon: Settings2,
        },
    ];

    return {
        navSections,
        mainNavItems,
        footerNavItems,
    };
}
