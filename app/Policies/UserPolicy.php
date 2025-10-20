<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    /**
     * Determine whether the user can view the platform dashboard.
     */
    public function viewPlatformDashboard(User $user): bool
    {
        if ($user->hasGlobalRole('super-admin')) {
            return true;
        }
        if ($user->hasGlobalRole('admin')) {
            return true;
        }

        return $user->hasGlobalRole('manager');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasGlobalRole('super-admin')) {
            return true;
        }

        return $user->hasGlobalRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }
        if ($user->hasGlobalRole('super-admin')) {
            return true;
        }

        return $user->hasGlobalRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasGlobalRole('super-admin')) {
            return true;
        }

        return $user->hasGlobalRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }
        if ($user->hasGlobalRole('super-admin')) {
            return true;
        }

        return $user->hasGlobalRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id
            && ($user->hasGlobalRole('super-admin') || $user->hasGlobalRole('admin'));
    }
}
