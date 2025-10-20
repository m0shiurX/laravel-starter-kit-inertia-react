<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SwitchBusinessAction;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class BusinessSwitchController extends Controller
{
    /**
     * Switch the user's active business context.
     *
     * Intelligently redirects based on the current page context:
     * - If on a business-specific page (with business ID in URL), redirect to dashboard
     * - Otherwise, reload the current page with new business context
     */
    public function switch(Business $business, SwitchBusinessAction $action, Request $request): RedirectResponse
    {
        $action->handle(Auth::user(), $business);

        // Get the previous URL to determine redirect strategy
        $previousUrl = $request->headers->get('referer') ?? route('dashboard');
        $previousPath = parse_url($previousUrl, PHP_URL_PATH);

        // Check if the previous page contains a business ID in the URL
        // Pattern: /businesses/{id}/edit or any route with {business} parameter
        if ($this->isBusinessSpecificRoute($previousPath)) {
            // Redirect to dashboard to avoid showing wrong business data
            return to_route('dashboard')
                ->with('success', "Switched to {$business->name}");
        }

        // For non-business-specific pages, reload the current page
        return back()
            ->with('success', "Switched to {$business->name}");
    }

    /**
     * Determine if a route path is business-specific (contains business ID parameter).
     */
    private function isBusinessSpecificRoute(string $path): bool
    {
        // List of route patterns that are business-specific
        $businessSpecificPatterns = [
            '#/businesses/\d+/edit#',      // Business settings/edit page
            '#/businesses/\d+#',            // Any business-specific page
            // Add more patterns here as you create business-scoped pages
            // Examples:
            // '#/businesses/\d+/members#',
            // '#/businesses/\d+/settings#',
            // '#/projects/\d+#',  // if projects belong to businesses
        ];

        return array_any($businessSpecificPatterns, fn ($pattern): int|false => preg_match($pattern, $path));
    }
}
