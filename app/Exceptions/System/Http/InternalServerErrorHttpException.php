<?php

declare(strict_types = 1);

namespace App\Exceptions\System\Http;

use App\Exceptions\System\HttpException;
use Throwable;

class InternalServerErrorHttpException extends HttpException
{
    public function __construct(string $message = '', public ?string $resource = null, ?Throwable $previous = null)
    {
        $this->userMessage = "Ocorreu um erro interno no servidor. Tente novamente mais tarde.";

        $message = $message ?: "Erro interno no servidor";

        parent::__construct(500, $message, $resource, $previous);
    }
}
