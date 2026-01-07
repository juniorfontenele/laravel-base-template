<?php

declare(strict_types = 1);

namespace App\Http\Middleware\System;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddTracingInformation
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = session()->get('correlation_id', (string) Str::uuid());
        session()->put('correlation_id', $correlationId);

        $requestId = (string) Str::uuid();
        session()->put('request_id', $requestId);

        $response = $next($request);

        $response->headers->set('X-Correlation-ID', $correlationId);
        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-App-Version', config('app.version'));

        if ($request->user()) {
            $response->headers->set('X-ID', (string) $request->user()->getKey());
        }

        Log::shareContext([
            'correlation_id' => $correlationId,
            'request_id' => $requestId,
        ]);

        return $response;
    }
}
