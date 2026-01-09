<?php

declare(strict_types = 1);

namespace App\Exceptions\System\Http;

use App\Exceptions\System\HttpException;
use Throwable;

class ServiceUnavailableHttpException extends HttpException
{
    public function __construct(string $message = '', public ?string $resource = null, ?Throwable $previous = null)
    {
        $this->userMessage = "O serviço está temporariamente indisponível. Tente novamente mais tarde.";

        $message = $message ?: "Serviço indisponível";

        parent::__construct(503, $message, $resource, $previous);
    }
}
