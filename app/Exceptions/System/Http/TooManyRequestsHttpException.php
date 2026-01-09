<?php

declare(strict_types = 1);

namespace App\Exceptions\System\Http;

use App\Exceptions\System\HttpException;
use Throwable;

class TooManyRequestsHttpException extends HttpException
{
    public function __construct(string $message = '', public ?string $resource = null, ?Throwable $previous = null)
    {
        $this->userMessage = "Muitas requisições. Por favor, aguarde um momento antes de tentar novamente.";

        $message = $message ?: "Muitas requisições";

        parent::__construct(429, $message, $resource, $previous);
    }
}
