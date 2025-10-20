import PlatformLayout from '@/layouts/platform-layout';
import { Head } from '@inertiajs/react';
import { Building2, Shield, Users } from 'lucide-react';

interface PlatformDashboardProps {
    stats: {
        totalUsers: number;
        totalBusinesses: number;
        totalRoles: number;
        platformUsers: number;
        tenantUsers: number;
    };
    recentUsers: Array<{
        id: number;
        name: string;
        email: string;
        created_at: string;
    }>;
    recentBusinesses: Array<{
        id: number;
        name: string;
        owner: { name: string };
        created_at: string;
    }>;
}

export default function PlatformDashboard({
    stats,
    recentUsers,
    recentBusinesses,
}: PlatformDashboardProps) {
    return (
        <PlatformLayout>
            <Head title="Platform Admin Dashboard" />

            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Platform Administration
                    </h1>
                    <p className="text-muted-foreground">
                        Manage users, businesses, and platform settings
                    </p>
                </div>

                {/* Stats Grid */}
                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-lg border bg-card p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">
                                    Total Users
                                </p>
                                <p className="text-3xl font-bold">
                                    {stats.totalUsers}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {stats.platformUsers} platform ·{' '}
                                    {stats.tenantUsers} tenant
                                </p>
                            </div>
                            <Users className="h-8 w-8 text-primary" />
                        </div>
                    </div>

                    <div className="rounded-lg border bg-card p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">
                                    Total Businesses
                                </p>
                                <p className="text-3xl font-bold">
                                    {stats.totalBusinesses}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Active tenants
                                </p>
                            </div>
                            <Building2 className="h-8 w-8 text-primary" />
                        </div>
                    </div>

                    <div className="rounded-lg border bg-card p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">
                                    Total Roles
                                </p>
                                <p className="text-3xl font-bold">
                                    {stats.totalRoles}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Global & scoped
                                </p>
                            </div>
                            <Shield className="h-8 w-8 text-primary" />
                        </div>
                    </div>
                </div>

                {/* Recent Activity */}
                <div className="grid gap-6 md:grid-cols-2">
                    <div className="rounded-lg border bg-card">
                        <div className="border-b p-4">
                            <h2 className="font-semibold">Recent Users</h2>
                        </div>
                        <div className="divide-y">
                            {recentUsers.map((user) => (
                                <div key={user.id} className="p-4">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <p className="font-medium">
                                                {user.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {user.email}
                                            </p>
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            {new Date(
                                                user.created_at,
                                            ).toLocaleDateString()}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-lg border bg-card">
                        <div className="border-b p-4">
                            <h2 className="font-semibold">Recent Businesses</h2>
                        </div>
                        <div className="divide-y">
                            {recentBusinesses.map((business) => (
                                <div key={business.id} className="p-4">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <p className="font-medium">
                                                {business.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                Owner: {business.owner.name}
                                            </p>
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            {new Date(
                                                business.created_at,
                                            ).toLocaleDateString()}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Guard & Scoping Info */}
                <div className="rounded-lg border border-primary/20 bg-primary/5 p-6">
                    <h3 className="mb-2 flex items-center gap-2 font-semibold">
                        <Shield className="h-5 w-5" />
                        Security Architecture
                    </h3>
                    <div className="space-y-2 text-sm">
                        <p>
                            ✅ <strong>Single Guard (web)</strong> - All users
                            authenticated via session
                        </p>
                        <p>
                            ✅ <strong>Role Scoping</strong> - Platform roles
                            (business_id = NULL), Tenant roles (business_id =
                            {'{business_id}'})
                        </p>
                        <p>
                            ✅ <strong>Business Context</strong> - Tenant users
                            switch between businesses, Platform users see all
                        </p>
                        <p>
                            ✅ <strong>Policy Authorization</strong> -
                            Membership and role verification on every operation
                        </p>
                    </div>
                </div>
            </div>
        </PlatformLayout>
    );
}
