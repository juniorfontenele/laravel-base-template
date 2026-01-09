<?php

declare(strict_types = 1);

namespace App\Facades\System;

use App\Services\System\ExceptionService;
use Illuminate\Support\Facades\Facade;

class AppException extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ExceptionService::class;
    }
}
