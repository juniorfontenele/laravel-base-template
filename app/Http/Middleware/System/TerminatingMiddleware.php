<?php

declare(strict_types = 1);

namespace App\Http\Middleware\System;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TerminatingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $responseType = match (true) {
            $response instanceof JsonResponse => 'json',
            $response instanceof IlluminateHttpResponse => 'html',
            default => 'unknown',
        };

        Log::shareContext([
            'response' => [
                'content-type' => $response->headers->get('Content-Type'),
                'type' => $responseType,
                'status' => $response->getStatusCode(),
                'size' => strlen($response->getContent() ?: ''),
            ],
        ]);
    }
}
