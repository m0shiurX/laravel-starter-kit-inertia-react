<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Business;
use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureBusinessAccess
{
    /**
     * Ensure the authenticated user has access to the current business context.
     *
     * This middleware adds an extra security layer by verifying that:
     * 1. A business context exists (if required)
     * 2. The user is a member of the current business
     * 3. Unauthorized access attempts are blocked
     */
    public function handle(Request $request, Closure $next): Response
    {
        $business = TenantResolver::getCurrentBusiness();

        // If no business context, allow (for routes that don't require it)
        if (! $business instanceof Business) {
            return $next($request);
        }

        $user = $request->user();

        // If not authenticated, let auth middleware handle it
        if ($user === null) {
            return $next($request);
        }

        // Verify user has access to this business
        if (! $this->userHasAccessToBusiness($user, $business)) {
            // Clear invalid business context
            TenantResolver::setCurrentBusiness(null);

            return to_route('dashboard')
                ->with('error', 'You do not have access to the selected business.');
        }

        return $next($request);
    }

    /**
     * Check if user has access to the business.
     */
    private function userHasAccessToBusiness($user, Business $business): bool
    {
        // Platform admins have access to all businesses
        if ($user->hasGlobalRole('super-admin')) {
            return true;
        }

        // Check if user is a member of the business
        return $user->isMemberOf($business);
    }
}
