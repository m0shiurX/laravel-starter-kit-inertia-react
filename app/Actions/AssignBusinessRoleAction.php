<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Business;
use App\Models\User;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class AssignBusinessRoleAction
{
    /**
     * Assign a role to a user within a business.
     * Cannot change the owner role.
     */
    public function handle(Business $business, User $user, string $roleName): void
    {
        // Verify user is a member
        throw_unless($business->hasMember($user), InvalidArgumentException::class, 'User is not a member of this business.');

        // Prevent changing owner role
        throw_if($business->isOwner($user) || $roleName === 'owner', InvalidArgumentException::class, 'Cannot change owner role.');

        // Set team context
        app(PermissionRegistrar::class)->setPermissionsTeamId($business->id);

        // Remove existing business-scoped roles (except owner)
        $businessRoles = $user->roles()
            ->where('roles.business_id', $business->id)
            ->where('roles.name', '!=', 'owner')
            ->get();

        foreach ($businessRoles as $existingRole) {
            $user->removeRole($existingRole);
        }

        // Ensure new role exists for this business
        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
            'business_id' => $business->id,
        ]);

        // Assign new role
        $user->assignRole($role);
    }
}
