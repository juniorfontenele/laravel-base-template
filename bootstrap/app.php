<?php

declare(strict_types = 1);

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\System\AddContextToSentry;
use App\Http\Middleware\System\AddSecurityHeaders;
use App\Http\Middleware\System\AddTracingInformation;
use App\Http\Middleware\System\SetUserLocale;
use App\Http\Middleware\System\TerminatingMiddleware;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->append([
            TerminatingMiddleware::class,
        ]);

        $middleware->web(append: [
            SetUserLocale::class,
            AddTracingInformation::class,
            AddContextToSentry::class,
            AddSecurityHeaders::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
    })->create();
