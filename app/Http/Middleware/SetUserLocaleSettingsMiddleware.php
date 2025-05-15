<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocaleSettingsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            try {
                /** @var \App\Models\User $user */
                $user = Auth::user();
                $locale = $user->locale;
                app()->setLocale($locale);
                Carbon::setLocale($locale);
            } catch (\Exception $e) {
                app()->setLocale(config('app.locale'));
                Carbon::setLocale(config('app.locale'));
            }
        } else {
            $languages = $request->getLanguages();
            $locale = $languages[0] ?? config('app.locale');
            app()->setLocale($locale);
            Carbon::setLocale($locale);
        }

        return $next($request);
    }
}
