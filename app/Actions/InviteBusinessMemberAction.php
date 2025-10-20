<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class InviteBusinessMemberAction
{
    /**
     * Invite a user to a business with a specific role.
     * For now, this assumes the user already exists.
     * TODO: Implement email invitation system for non-existing users.
     */
    public function handle(Business $business, User $user, string $roleName = 'manager'): void
    {
        DB::transaction(function () use ($business, $user, $roleName): void {
            // Attach user to business if not already attached
            if (! $business->hasMember($user)) {
                $business->users()->attach($user);
            }

            // Ensure role exists for this business
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'business_id' => $business->id,
            ]);

            // Set team context and assign role to user
            app(PermissionRegistrar::class)->setPermissionsTeamId($business->id);
            $user->assignRole($role);
        });
    }
}
