<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use App\Events\Http\MaxRequestErrorsLimit;
use App\Events\Http\MaxRequestsLimit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimiting
{
    protected bool $requestsEnabled;

    protected bool $errorsEnabled;

    protected string $requestsKey;

    protected string $errorsKey;

    protected int $requestsMaxEvents;

    protected int $errorsMaxEvents;

    protected int $requestsDecaySeconds;

    protected int $errorsDecaySeconds;

    protected int $requestsReturnCode;

    protected string $requestsReturnMessage;

    protected int $errorsReturnCode;

    protected string $errorsReturnMessage;

    protected bool $eventsLimitEnabled;

    protected int $eventsDecaySeconds;

    protected string $requestsEventKey;

    protected string $errorsEventKey;

    protected string $ip;

    public function __construct()
    {
        $this->requestsEnabled = config('rate-limiting.requests.enabled', true);
        $this->errorsEnabled = config('rate-limiting.errors.enabled', true);
        $this->requestsMaxEvents = config('rate-limiting.requests.max_events', 60);
        $this->errorsMaxEvents = config('rate-limiting.errors.max_events', 10);
        $this->requestsDecaySeconds = config('rate-limiting.requests.decay_seconds', 60);
        $this->errorsDecaySeconds = config('rate-limiting.errors.decay_seconds', 60);
        $this->requestsReturnCode = config('rate-limiting.requests.return_code', 404);
        $this->requestsReturnMessage = config('rate-limiting.requests.return_message', '');
        $this->errorsReturnCode = config('rate-limiting.errors.return_code', 404);
        $this->errorsReturnMessage = config('rate-limiting.errors.return_message', '');
        $this->eventsLimitEnabled = config('rate-limiting.events.enabled', true);
        $this->eventsDecaySeconds = config('rate-limiting.events.decay_seconds', 120);
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->ip = (string) $request->ip();
        $this->requestsKey = config('rate-limiting.requests.key', 'rate-limiting-requests') . ':' . $this->ip;
        $this->errorsKey = config('rate-limiting.errors.key', 'rate-limiting-errors') . ':' . $this->ip;
        $this->requestsEventKey = config('rate-limiting.requests.key', 'rate-limiting-requests') . ':events:' . $this->ip;
        $this->errorsEventKey = config('rate-limiting.errors.key', 'rate-limiting-errors') . ':events:' . $this->ip;

        $this->checkRequestRateLimiting();

        $this->checkErrorRateLimiting();

        return $next($request);
    }

    protected function checkRequestRateLimiting(): void
    {
        if (! $this->requestsEnabled) {
            return;
        }

        if (RateLimiter::tooManyAttempts($this->requestsKey, $this->requestsMaxEvents)) {
            if ($this->shouldSendMaxRequestsLimitEvent()) {
                $this->sendMaxRequestsLimitEvent();
            }

            abort($this->requestsReturnCode, $this->requestsReturnMessage);
        }

        Cache::forget($this->requestsEventKey);
    }

    protected function sendMaxRequestsLimitEvent(): void
    {
        event(new MaxRequestsLimit(
            ip: $this->ip,
            maxEvents: $this->requestsMaxEvents,
            attempts: RateLimiter::attempts($this->requestsKey),
            decaySeconds: $this->requestsDecaySeconds,
            availableIn: RateLimiter::availableIn($this->requestsKey),
            returnCode: $this->requestsReturnCode,
            returnMessage: $this->requestsReturnMessage,
        ));

        if ($this->eventsLimitEnabled) {
            Cache::put($this->requestsEventKey, true, $this->eventsDecaySeconds);
        }
    }

    protected function shouldSendMaxRequestsLimitEvent(): bool
    {
        if (! $this->eventsLimitEnabled) {
            return true;
        }

        return ! Cache::has($this->requestsEventKey);
    }

    protected function checkErrorRateLimiting(): void
    {
        if (! $this->errorsEnabled) {
            return;
        }

        if (RateLimiter::tooManyAttempts($this->errorsKey, $this->errorsMaxEvents)) {
            if ($this->shouldSendMaxErrorsLimitEvent()) {
                $this->sendMaxErrorsLimitEvent();
            }

            abort($this->errorsReturnCode, $this->errorsReturnMessage);
        }

        Cache::forget($this->errorsEventKey);
    }

    protected function shouldSendMaxErrorsLimitEvent(): bool
    {
        if (! $this->eventsLimitEnabled) {
            return true;
        }

        return ! Cache::has($this->errorsEventKey);
    }

    protected function sendMaxErrorsLimitEvent(): void
    {
        event(new MaxRequestErrorsLimit(
            ip: $this->ip,
            maxEvents: $this->errorsMaxEvents,
            attempts: RateLimiter::attempts($this->errorsKey),
            decaySeconds: $this->errorsDecaySeconds,
            availableIn: RateLimiter::availableIn($this->errorsKey),
            returnCode: $this->errorsReturnCode,
            returnMessage: $this->errorsReturnMessage,
        ));

        if ($this->eventsLimitEnabled) {
            Cache::put($this->errorsEventKey, true, $this->eventsDecaySeconds);
        }
    }
}
