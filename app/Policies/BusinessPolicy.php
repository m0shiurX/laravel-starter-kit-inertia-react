<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

final class BusinessPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Business $business): bool
    {
        if ($user->isMemberOf($business)) {
            return true;
        }

        return $user->hasGlobalRole('super-admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Business $business): bool
    {
        if ($business->isOwner($user)) {
            return true;
        }
        if ($user->hasBusinessRole('admin', $business)) {
            return true;
        }

        return $user->hasGlobalRole('super-admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Business $business): bool
    {
        if ($business->isOwner($user)) {
            return true;
        }

        return $user->hasGlobalRole('super-admin');
    }
}
