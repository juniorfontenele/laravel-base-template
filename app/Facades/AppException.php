<?php

declare(strict_types = 1);

namespace App\Facades;

use App\Services\ExceptionService;
use Illuminate\Support\Facades\Facade;

class AppException extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ExceptionService::class;
    }
}
