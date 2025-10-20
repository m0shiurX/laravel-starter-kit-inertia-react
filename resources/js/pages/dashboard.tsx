import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type Business } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Building2, Shield } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    const page = usePage();
    const currentBusiness = page.props.currentBusiness as Business | null;
    const auth = page.props.auth as {
        isPlatformUser: boolean;
        globalRoles: string[];
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {/* User Context Info */}
                <div className="rounded-lg border bg-card p-6">
                    <h2 className="mb-4 text-2xl font-bold">
                        Welcome to Your Dashboard
                    </h2>

                    <div className="grid gap-4 md:grid-cols-2">
                        {/* User Type Card */}
                        <div className="space-y-2 rounded-lg border p-4">
                            <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <Shield className="h-4 w-4" />
                                User Type
                            </div>
                            <p className="text-2xl font-bold">
                                {auth.isPlatformUser
                                    ? 'Platform User'
                                    : 'Business User'}
                            </p>
                            {auth.isPlatformUser && (
                                <p className="text-sm text-muted-foreground">
                                    Global Roles: {auth.globalRoles.join(', ')}
                                </p>
                            )}
                        </div>

                        {/* Business Context Card */}
                        <div className="space-y-2 rounded-lg border p-4">
                            <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <Building2 className="h-4 w-4" />
                                Business Context
                            </div>
                            {currentBusiness ? (
                                <>
                                    <p className="text-2xl font-bold">
                                        {currentBusiness.name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Business ID: {currentBusiness.id}
                                    </p>
                                </>
                            ) : (
                                <p className="text-lg text-muted-foreground">
                                    No business selected
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Guard Info */}
                    <div className="mt-4 rounded-lg border border-primary/20 bg-primary/5 p-4">
                        <h3 className="mb-2 flex items-center gap-2 font-semibold">
                            <Shield className="h-4 w-4" />
                            Authentication Guard
                        </h3>
                        <div className="space-y-1 text-sm">
                            <p>
                                ✅ <strong>Guard:</strong> web (session-based)
                            </p>
                            <p>
                                ✅ <strong>Layout:</strong>{' '}
                                {auth.isPlatformUser
                                    ? 'Platform (no business switcher)'
                                    : 'Business (with business switcher)'}
                            </p>
                            <p>
                                ✅ <strong>Role Scoping:</strong>{' '}
                                {auth.isPlatformUser
                                    ? 'Global (business_id = NULL)'
                                    : currentBusiness
                                      ? `Scoped to Business ${currentBusiness.id}`
                                      : 'No business context'}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </AppLayout>
    );
}
