<?php

declare(strict_types = 1);

namespace App\Exceptions\System\Http;

use App\Exceptions\System\HttpException;
use Throwable;

class GatewayTimeoutHttpException extends HttpException
{
    public function __construct(string $message = '', public ?string $resource = null, ?Throwable $previous = null)
    {
        $this->userMessage = "O serviço está demorando muito para responder. Tente novamente mais tarde.";

        $message = $message ?: "Gateway Timeout";

        parent::__construct(504, $message, $resource, $previous);
    }
}
