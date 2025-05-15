<?php

declare(strict_types = 1);

return [
    'requests' => [
        'enabled' => env('RATE_LIMITING_REQUESTS_ENABLED', true),
        'max_events' => env('RATE_LIMITING_REQUESTS_MAX_EVENTS', 60),
        'decay_seconds' => env('RATE_LIMITING_REQUESTS_DECAY_SECONDS', 60),
        'key' => env('RATE_LIMITING_REQUESTS_KEY', 'rate-limiting-requests'),
        'return_code' => env('RATE_LIMITING_REQUESTS_RETURN_CODE', 404),
        'return_message' => env('RATE_LIMITING_REQUESTS_RETURN_MESSAGE', ''),
    ],

    'errors' => [
        'enabled' => env('RATE_LIMITING_ERRORS_ENABLED', true),
        'max_events' => env('RATE_LIMITING_ERRORS_MAX_EVENTS', 10),
        'decay_seconds' => env('RATE_LIMITING_ERRORS_DECAY_SECONDS', 60),
        'key' => env('RATE_LIMITING_ERRORS_KEY', 'rate-limiting-errors'),
        'return_code' => env('RATE_LIMITING_ERRORS_RETURN_CODE', 404),
        'return_message' => env('RATE_LIMITING_ERRORS_RETURN_MESSAGE', ''),
    ],

    'events' => [
        'enabled' => env('RATE_LIMITING_EVENTS_ENABLED', true),
        'decay_seconds' => env('RATE_LIMITING_EVENTS_DECAY_SECONDS', 120),
    ],
];
