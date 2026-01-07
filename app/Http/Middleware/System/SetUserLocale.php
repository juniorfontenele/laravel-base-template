<?php

declare(strict_types = 1);

namespace App\Http\Middleware\System;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $locale = getUserLocale();
            app()->setLocale($locale);
            Carbon::setLocale($locale);
        } else {
            $languages = $request->getLanguages();
            $locale = $languages[0] ?? config('app.locale');
            app()->setLocale($locale);
            Carbon::setLocale($locale);
        }

        return $next($request);
    }
}
