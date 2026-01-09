<?php

declare(strict_types = 1);

namespace App\Exceptions\System\Http;

use App\Exceptions\System\HttpException;
use Throwable;

class UnprocessableEntityHttpException extends HttpException
{
    public function __construct(string $message = '', public ?string $resource = null, ?Throwable $previous = null)
    {
        $this->userMessage = "Não foi possível processar a requisição. Verifique os dados enviados e tente novamente.";

        $message = $message ?: "Entidade não processável";

        parent::__construct(422, $message, $resource, $previous);
    }
}
