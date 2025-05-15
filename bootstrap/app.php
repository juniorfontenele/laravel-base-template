<?php

declare(strict_types = 1);

use App\Http\Middleware\AddContextToSentry;
use App\Http\Middleware\AddSecurityHeadersToResponse;
use App\Http\Middleware\AddTracingInformation;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RateLimiting;
use App\Http\Middleware\SetUserLocaleSettingsMiddleware;
use App\Http\Middleware\TerminatingMiddleware;
use App\System\Proxy\Services\CloudflareProxyService;
use App\System\Proxy\Services\InternalProxyService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $trustedProxies = array_merge(
            InternalProxyService::getTrustedProxies(),
            CloudflareProxyService::getTrustedProxies(),
        );

        $middleware->trustProxies($trustedProxies);

        $middleware->append([
            TerminatingMiddleware::class,
        ]);

        $middleware->web(append: [
            SetUserLocaleSettingsMiddleware::class,
            AddTracingInformation::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            RateLimiting::class,
            AddContextToSentry::class,
            AddSecurityHeadersToResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();
