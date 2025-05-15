<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use function Sentry\configureScope;

use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\Response;

class AddContextToSentry
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->bound('sentry')) {
            return $next($request);
        }

        if (auth()->check()) {
            configureScope(function (Scope $scope) {
                /** @var \App\Models\User $user */
                $user = auth()->user();

                $scope->setUser([
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                ]);
            });
        }

        configureScope(function (Scope $scope) use ($request) {
            $scope->setContext('Tracing', [
                'Trace ID' => session()->get('trace_id'),
                'Request ID' => session()->get('request_id'),
            ]);

            $scope->setTag('page.path', '/' . $request->path());
            $scope->setTag('page.url', $request->fullUrl());
            $scope->setTag('request_id', session()->get('request_id'));
            $scope->setTag('trace_id', session()->get('trace_id'));
        });

        return $next($request);
    }
}
