import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    Folder,
    LayoutGrid,
    Shield,
    Users,
} from 'lucide-react';
import AppLogo from './app-logo';

export function PlatformSidebar() {
    const page = usePage();
    const auth = page.props.auth as { globalRoles: string[] };
    const globalRoles = auth.globalRoles;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Users',
            href: '/admin/users',
            icon: Users,
        },
        {
            title: 'Roles',
            href: '/admin/roles',
            icon: Shield,
        },
        {
            title: 'Businesses',
            href: '/admin/businesses',
            icon: Building2,
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/react-starter-kit',
            icon: Folder,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#react',
            icon: BookOpen,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <div className="flex items-center gap-2 px-2 py-1.5 text-sm">
                            <Shield className="h-4 w-4 text-primary" />
                            <div className="flex-1">
                                <div className="font-medium">
                                    Platform Admin
                                </div>
                                <div className="text-xs text-muted-foreground">
                                    {globalRoles.join(', ')}
                                </div>
                            </div>
                        </div>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
