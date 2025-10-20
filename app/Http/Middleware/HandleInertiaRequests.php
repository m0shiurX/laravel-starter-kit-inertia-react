<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TenantResolver;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $quote = Inspiring::quotes()->random();
        assert(is_string($quote));

        [$message, $author] = str($quote)->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => mb_trim((string) $message), 'author' => mb_trim((string) $author)],
            'auth' => [
                'user' => $request->user(),
                'isPlatformUser' => fn () => $request->user()?->isPlatformUser() ?? false,
                'globalRoles' => fn () => $request->user()?->globalRoles() ?? [],
            ],
            'currentBusiness' => fn (): ?\App\Models\Business => TenantResolver::getCurrentBusiness(),
            'businesses' => fn () => $request->user()?->businesses()->get(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
