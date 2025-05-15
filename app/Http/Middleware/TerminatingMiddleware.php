<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use App\Events\Http\HttpResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateHttpResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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

        $response->getStatusCode() >= 400
            ? $this->hitErrorRateLimiter((string) $request->ip())
            : $this->hitRequestRateLimiter((string) $request->ip());

        event(new HttpResponse($request, $response));
    }

    protected function hitRequestRateLimiter(string $ip): void
    {
        if (! config('rate-limiting.requests.enabled')) {
            return;
        }

        $key = config('rate-limiting.requests.key') . ':' . $ip;
        $decaySeconds = config('rate-limiting.requests.decay_seconds');

        RateLimiter::increment($key, $decaySeconds);
    }

    protected function hitErrorRateLimiter(string $ip): void
    {
        if (! config('rate-limiting.errors.enabled')) {
            return;
        }

        $key = config('rate-limiting.errors.key') . ':' . $ip;
        $decaySeconds = config('rate-limiting.errors.decay_seconds');

        RateLimiter::increment($key, $decaySeconds);
    }
}
