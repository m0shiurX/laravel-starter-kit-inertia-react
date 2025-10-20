<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final class DashboardController extends Controller
{
    /**
     * Display the platform admin dashboard.
     */
    public function __invoke(): Response
    {
        // Only platform admins can access
        $this->authorize('viewPlatformDashboard', User::class);

        $stats = [
            'totalUsers' => User::query()->count(),
            'totalBusinesses' => Business::query()->count(),
            'totalRoles' => Role::query()->count(),
            'platformUsers' => User::query()->whereHas('roles', function ($query): void {
                $query->whereNull('business_id');
            })->count(),
            'tenantUsers' => User::query()->whereHas('roles', function ($query): void {
                $query->whereNotNull('business_id');
            })->count(),
        ];

        $recentUsers = User::query()->latest()
            ->take(5)
            ->get(['id', 'name', 'email', 'created_at']);

        $recentBusinesses = Business::with('owner:id,name')
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'owner_id', 'created_at']);

        return Inertia::render('admin/dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentBusinesses' => $recentBusinesses,
        ]);
    }
}
