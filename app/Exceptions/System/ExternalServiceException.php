<?php

declare(strict_types = 1);

namespace App\Exceptions\System;

use Throwable;

class ExternalServiceException extends AppException
{
    protected string $service;

    public int $statusCode = 503;

    public string $userMessage = 'Falha ao se comunicar com serviço externo.';

    public function __construct(string $service, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $this->service = $service;
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return array_merge(parent::context(), [
            'service' => $this->service,
        ]);
    }
}
