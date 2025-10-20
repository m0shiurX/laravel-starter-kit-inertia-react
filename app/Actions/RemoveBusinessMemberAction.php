<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\PermissionRegistrar;

final readonly class RemoveBusinessMemberAction
{
    /**
     * Remove a user from a business.
     * Cannot remove the business owner.
     */
    public function handle(Business $business, User $user): void
    {
        // Prevent removing the owner
        throw_if($business->isOwner($user), InvalidArgumentException::class, 'Cannot remove the business owner.');

        DB::transaction(function () use ($business, $user): void {
            // Set team context
            app(PermissionRegistrar::class)->setPermissionsTeamId($business->id);

            // Remove all business-scoped roles
            $businessRoles = $user->roles()
                ->where('roles.business_id', $business->id)
                ->get();

            foreach ($businessRoles as $role) {
                $user->removeRole($role);
            }

            // Detach user from business
            $business->users()->detach($user);
        });
    }
}
