<?php

declare(strict_types = 1);

use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2023-06-15 12:00:00');
});

test('toUserDate converts UTC date to user timezone with User object', function () {
    $user = new User();
    $user->timezone = 'America/Sao_Paulo';

    $utcDate = '2023-06-15 12:00:00';
    $userDate = toUserDate($utcDate, $user);

    expect($userDate->timezone->getName())->toBe('America/Sao_Paulo')
        ->and($userDate->format('Y-m-d H:i:s'))->toBe('2023-06-15 09:00:00');
});

test('toUserDate converts UTC date to specific timezone', function () {
    $utcDate = '2023-06-15 12:00:00';
    $userDate = toUserDate($utcDate, null, 'Asia/Tokyo');

    expect($userDate->timezone->getName())->toBe('Asia/Tokyo')
        ->and($userDate->format('Y-m-d H:i:s'))->toBe('2023-06-15 21:00:00');
});

test('toUserDate handles Carbon instance', function () {
    $user = new User();
    $user->timezone = 'Europe/London';

    $utcDate = Carbon::parse('2023-06-15 12:00:00');
    $userDate = toUserDate($utcDate, $user);

    expect($userDate->timezone->getName())->toBe('Europe/London')
        ->and($userDate->format('Y-m-d H:i:s'))->toBe('2023-06-15 13:00:00');
});

test('fromUserDate converts user timezone to UTC with User object', function () {
    $user = new User();
    $user->timezone = 'America/Sao_Paulo';

    $localDate = '2023-06-15 09:00:00';
    $utcDate = fromUserDate($localDate, $user);

    expect($utcDate->timezone->getName())->toBe('UTC')
        ->and($utcDate->format('Y-m-d H:i:s'))->toBe('2023-06-15 12:00:00');
});

test('fromUserDate converts specific timezone to UTC', function () {
    $localDate = '2023-06-15 21:00:00';
    $utcDate = fromUserDate($localDate, null, 'Asia/Tokyo');

    expect($utcDate->timezone->getName())->toBe('UTC')
        ->and($utcDate->format('Y-m-d H:i:s'))->toBe('2023-06-15 12:00:00');
});

test('fromUserDate handles Carbon instance', function () {
    $user = new User();
    $user->timezone = 'Europe/London';

    $localDate = Carbon::parse('2023-06-15 13:00:00', 'Europe/London');
    $utcDate = fromUserDate($localDate, $user);

    expect($utcDate->timezone->getName())->toBe('UTC')
        ->and($utcDate->format('Y-m-d H:i:s'))->toBe('2023-06-15 12:00:00');
});
