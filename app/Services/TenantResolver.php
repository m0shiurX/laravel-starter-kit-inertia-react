<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

final class TenantResolver implements PermissionsTeamResolver
{
    /**
     * Get the current business model instance.
     */
    public static function getCurrentBusiness(): ?Business
    {
        $businessId = session('current_business_id');

        if (! is_int($businessId)) {
            return null;
        }

        // Try to get from app container first (cached)
        if (app()->bound('tenant')) {
            $tenant = app('tenant');

            return $tenant instanceof Business ? $tenant : null;
        }

        // Load and cache in container
        $business = Business::query()->find($businessId);

        if ($business instanceof Business) {
            app()->instance('tenant', $business);
        }

        return $business;
    }

    /**
     * Set the current business in session and app container.
     */
    public static function setCurrentBusiness(?Business $business): void
    {
        if (! $business instanceof Business) {
            session()->forget('current_business_id');
            app()->forgetInstance('tenant');

            return;
        }

        session(['current_business_id' => $business->id]);
        app()->instance('tenant', $business);
    }

    /**
     * Check if there's an active business context.
     */
    public static function hasCurrentBusiness(): bool
    {
        return session()->has('current_business_id');
    }

    /**
     * Get the current team/business ID for permission scoping.
     *
     * Returns the business_id from session if set, otherwise null for global scope.
     */
    public function getPermissionsTeamId(): ?string
    {
        // Get current business from session
        $businessId = session('current_business_id');

        // Return as string for Spatie Permission compatibility
        return is_int($businessId) ? (string) $businessId : null;
    }

    /**
     * Set the team/business ID for permission scoping.
     *
     * @param  int|string|Model|null  $id
     */
    public function setPermissionsTeamId($id): void
    {
        if ($id === null) {
            session()->forget('current_business_id');

            return;
        }

        // Extract ID if Model instance
        $businessId = $id instanceof Model ? $id->getKey() : $id;

        session(['current_business_id' => $businessId]);
    }
}
