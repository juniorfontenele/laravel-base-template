<?php

declare(strict_types = 1);

namespace App\Exceptions\System\Http;

use App\Exceptions\System\HttpException;
use Throwable;

class MethodNotAllowedHttpException extends HttpException
{
    public function __construct(string $message = '', public ?string $resource = null, ?Throwable $previous = null)
    {
        $this->userMessage = "Método não permitido. Verifique o método HTTP utilizado e tente novamente.";

        $message = $message ?: "Método não permitido";

        parent::__construct(405, $message, $resource, $previous);
    }
}
