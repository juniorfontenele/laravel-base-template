<?php

declare(strict_types = 1);

namespace App\Services;

use App\Exceptions\System\AppException;
use App\Exceptions\System\Http\AccessDeniedHttpException;
use App\Exceptions\System\Http\BadRequestHttpException;
use App\Exceptions\System\Http\GatewayTimeoutHttpException;
use App\Exceptions\System\Http\InternalServerErrorHttpException;
use App\Exceptions\System\Http\MethodNotAllowedHttpException;
use App\Exceptions\System\Http\NotFoundHttpException;
use App\Exceptions\System\Http\ServiceUnavailableHttpException;
use App\Exceptions\System\Http\SessionExpiredHttpException;
use App\Exceptions\System\Http\TooManyRequestsHttpException;
use App\Exceptions\System\Http\UnauthorizedHttpException;
use App\Exceptions\System\Http\UnprocessableEntityHttpException;
use App\Exceptions\System\HttpException;
use Illuminate\Foundation\Configuration\Exceptions;
use Symfony\Component\HttpKernel\Exception\HttpException as LaravelHttpException;
use Throwable;

class ExceptionService
{
    public function handles(Exceptions $exceptions): void
    {
        $exceptions->render(function (LaravelHttpException $e) {
            match ($e->getStatusCode()) {
                404 => throw new NotFoundHttpException(
                    previous: $e,
                ),
                403 => throw new AccessDeniedHttpException(
                    previous: $e,
                ),
                401 => throw new UnauthorizedHttpException(
                    previous: $e,
                ),
                419 => throw new SessionExpiredHttpException(
                    previous: $e,
                ),
                500 => throw new InternalServerErrorHttpException(
                    previous: $e,
                ),
                503 => throw new ServiceUnavailableHttpException(
                    previous: $e,
                ),
                504 => throw new GatewayTimeoutHttpException(
                    previous: $e,
                ),
                400 => throw new BadRequestHttpException(
                    previous: $e,
                ),
                405 => throw new MethodNotAllowedHttpException(
                    previous: $e,
                ),
                422 => throw new UnprocessableEntityHttpException(
                    previous: $e,
                ),
                429 => throw new TooManyRequestsHttpException(
                    previous: $e,
                ),
                default => throw new HttpException(
                    statusCode: $e->getStatusCode(),
                    previous: $e,
                ),
            };
        });

        $exceptions->render(function (Throwable $e) {
            return match (true) {
                $e instanceof AuthenticationException => false,
                $e instanceof ValidationException => false,
                default => null,
            };

            if (! $e instanceof AppException) {
                throw new AppException(
                    $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        });
    }
}
