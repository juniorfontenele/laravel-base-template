<?php

declare(strict_types = 1);

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('toUserDate')) {
    function toUserDate(string|CarbonInterface $date, ?User $user = null, string $timezone = 'UTC'): CarbonInterface
    {
        if ($user instanceof User) {
            $timezone = $user->timezone;
        }

        if (is_string($date)) {
            return Carbon::parse($date, 'UTC')->setTimezone($timezone);
        }

        return $date->setTimezone($timezone);
    }
}

if (! function_exists('fromUserDate')) {
    function fromUserDate(string|CarbonInterface $date, ?User $user = null, ?string $timezone = null): CarbonInterface
    {
        if ($user instanceof User) {
            $timezone = $user->timezone;
        }

        if (is_string($date)) {
            return Carbon::parse($date, $timezone)->setTimezone('UTC');
        }

        return $date->setTimezone('UTC');
    }
}
