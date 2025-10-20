import { PlatformSidebar } from '@/components/platform-sidebar';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';

interface PlatformLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: PlatformLayoutProps) => (
    <AppLayoutTemplate
        breadcrumbs={breadcrumbs}
        sidebar={<PlatformSidebar />}
        {...props}
    >
        {children}
    </AppLayoutTemplate>
);
