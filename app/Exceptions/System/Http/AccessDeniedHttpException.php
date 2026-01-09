<?php

declare(strict_types = 1);

namespace App\Exceptions\System\Http;

use App\Exceptions\System\HttpException;
use Throwable;

class AccessDeniedHttpException extends HttpException
{
    public function __construct(string $message = '', public ?string $resource = null, ?Throwable $previous = null)
    {
        $this->userMessage = (new NotFoundHttpException())->userMessage;

        $message = $message ?: "Acesso negado";

        parent::__construct(404, $message, $resource, $previous);
    }
}
