<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureBusinessContextMatch;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use App\Services\TenantResolver;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            SetTenantContext::class,  // Must run before HandleInertiaRequests
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Register middleware aliases for route-level usage
        $middleware->alias([
            'business.context' => EnsureBusinessContextMatch::class,
        ]);
    })
    ->withBindings([
        'tenant' => fn() => TenantResolver::getCurrentBusiness(),
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
