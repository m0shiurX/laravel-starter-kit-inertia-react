<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Business;
use App\Models\User;
use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Skip if not authenticated
        if ($user === null) {
            return $next($request);
        }

        // Get current business from session
        $businessId = session('current_business_id');

        // If no business set, try to set one
        if ($businessId === null) {
            $this->setDefaultBusiness($user);
        } elseif (! $this->verifyBusinessAccess($user, $businessId)) {
            // Verify user still has access to this business
            // User lost access, clear and try to set another
            session()->forget('current_business_id');
            $this->setDefaultBusiness($user);
        } else {
            // Load and cache the business
            $business = Business::query()->find($businessId);

            if ($business !== null) {
                app()->instance('tenant', $business);
            }
        }

        return $next($request);
    }

    /**
     * Set the default business for the user.
     *
     * Priority:
     * 1. User's first owned business
     * 2. User's first member business
     * 3. Redirect to business creation if none
     */
    private function setDefaultBusiness(User $user): void
    {
        // Try to get first owned business
        $business = $user->ownedBusinesses()->first();

        // If no owned business, get first member business
        if ($business === null) {
            $business = $user->businesses()->first();
        }

        // If still no business and not on business creation route, redirect
        if ($business === null) {
            // Skip for platform users (users with global roles)
            if ($user->isPlatformUser()) {
                return;
            }

            // Don't redirect if already on business routes
            if (! request()->is('businesses/create', 'businesses')) {
                // Store intended URL to redirect after business creation
                session()->put('url.intended', url()->current());
                to_route('business.create')->send();
            }

            return;
        }

        // Set the business context
        TenantResolver::setCurrentBusiness($business);
    }

    /**
     * Verify that the user still has access to the business.
     */
    private function verifyBusinessAccess(User $user, int $businessId): bool
    {
        // Super admin has access to all businesses
        if ($user->hasGlobalRole('super-admin')) {
            return true;
        }
        // Check if user is owner or member
        if ($user->owns($businessId)) {
            return true;
        }

        return $user->isMemberOf($businessId);
    }
}
