<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Business;
use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureBusinessContextMatch
{
    /**
     * Ensure that route business parameters match the current business context.
     *
     * When a user has a business context set and navigates to a route with a {business}
     * parameter, this middleware ensures they match. If they don't match, it automatically
     * switches to the business in the URL or redirects to prevent data confusion.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get business from route parameter (if exists)
        $routeBusiness = $request->route('business');

        // If no business in route, continue (not a business-specific route)
        if (! $routeBusiness instanceof Business) {
            return $next($request);
        }

        // Get current business context
        $currentBusiness = TenantResolver::getCurrentBusiness();

        // If no current business context, set it to the route business
        if (! $currentBusiness instanceof Business) {
            $user = $request->user();

            // Verify user has access to this business
            if ($user && ($user->isMemberOf($routeBusiness) || $user->hasGlobalRole('super-admin'))) {
                TenantResolver::setCurrentBusiness($routeBusiness);

                return $next($request);
            }

            // User doesn't have access, redirect with error
            return to_route('dashboard')
                ->with('error', 'You do not have access to this business.');
        }

        // If current business doesn't match route business, redirect to dashboard
        if ($currentBusiness->id !== $routeBusiness->id) {
            return to_route('dashboard')
                ->with('warning', 'Business context mismatch. Switched to your current business context.');
        }

        return $next($request);
    }
}
