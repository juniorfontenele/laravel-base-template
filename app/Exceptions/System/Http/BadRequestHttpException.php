<?php

declare(strict_types = 1);

namespace App\Exceptions\System\Http;

use App\Exceptions\System\HttpException;
use Throwable;

class BadRequestHttpException extends HttpException
{
    public function __construct(string $message = '', public ?string $resource = null, ?Throwable $previous = null)
    {
        $this->userMessage = "Requisição inválida. Verifique os dados enviados e tente novamente.";

        $message = $message ?: "Requisição inválida";

        parent::__construct(400, $message, $resource, $previous);
    }
}
