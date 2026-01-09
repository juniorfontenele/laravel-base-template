<?php

declare(strict_types = 1);

namespace App\Exceptions\System;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class AppException extends Exception
{
    public string $errorId;

    // HTTP status code to be returned
    public int $statusCode = 500;

    // Message to be shown to the end user
    public string $userMessage = 'Ocorreu um erro de aplicação. Tente novamente.';

    public function __construct(string $message = '', $code = 0, ?Throwable $previous = null)
    {
        $this->errorId = Str::uuid()->toString();

        parent::__construct($message, $code, $previous);
    }

    public function userMessage(): string
    {
        return $this->userMessage . " (Erro: {$this->errorId})";
    }

    public function context(): array
    {
        $user = Auth::user()?->only(['id', 'name', 'email']);
        $roles = Auth::user()?->roles?->pluck('name')?->toArray();

        return [
            'resource' => request()?->getRequestUri(),
            'status_code' => $this->statusCode,
            'error_id' => $this->errorId,
            'correlation_id' => session()->get('correlation_id'),
            'request_id' => session()->get('request_id'),
            'user' => [
                'id' => $user['id'] ?? null,
                'name' => $user['name'] ?? null,
                'email' => $user['email'] ?? null,
                'roles' => $roles,
            ],
            'actual_exception' => [
                'class' => get_class($this),
                'message' => $this->getMessage(),
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'code' => $this->getCode(),
            ],
            'previous_exception' => $this->getPrevious() instanceof Throwable ? [
                'class' => get_class($this->getPrevious()),
                'message' => $this->getPrevious()?->getMessage(),
                'file' => $this->getPrevious()?->getFile(),
                'line' => $this->getPrevious()?->getLine(),
                'code' => $this->getPrevious()?->getCode(),
            ] : null,
        ];
    }

    public function isRetryable(): bool
    {
        return false;
    }

    public function render()
    {
        return response()->view('errors.app', [
            'code' => $this->errorId,
            'message' => $this->userMessage,
        ], $this->statusCode);
    }

    public function report(AppException $exception): bool
    {
        try {
            \App\Models\System\Exception::create([
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'user_message' => $exception->userMessage,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
                'status_code' => $exception->statusCode,
                'error_id' => $exception->errorId,
                'correlation_id' => session()->get('correlation_id'),
                'request_id' => session()->get('request_id'),
                'app_version' => config('app.version'),
                'app_commit' => config('app.commit'),
                'app_build_date' => config('app.build_date'),
                'app_role' => config('app.role'),
                'user_id' => Auth::id(),
                'is_retryable' => $exception->isRetryable(),
                'stack_trace' => $exception->getTraceAsString(),
                'context' => $exception->context(),
                'previous_exception_class' => $exception->getPrevious() ? get_class($exception->getPrevious()) : null,
                'previous_message' => $exception->getPrevious()?->getMessage(),
                'previous_file' => $exception->getPrevious()?->getFile(),
                'previous_line' => $exception->getPrevious()?->getLine(),
                'previous_code' => $exception->getPrevious()?->getCode(),
                'previous_stack_trace' => $exception->getPrevious()?->getTraceAsString(),
            ]);
        } catch (Throwable) {
            // Do nothing
        }

        return false;
    }
}
