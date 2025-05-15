<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

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
        $traceId = session()->get('trace_id', (string) Str::uuid());
        session()->put('trace_id', $traceId);

        $requestId = (string) Str::uuid();
        session()->put('request_id', $requestId);

        $response = $next($request);

        $response->headers->set('X-Trace-ID', $traceId);
        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-App-Version', config('app.version'));

        if ($request->user()) {
            $response->headers->set('X-ID', $request->user()->getKey());
        }

        Log::shareContext([
            'trace_id' => $traceId,
            'request_id' => $requestId,
        ]);

        return $response;
    }
}
