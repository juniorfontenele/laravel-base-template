# Laravel Exceptions - Especificação Detalhada

## Visão Geral

Pacote para gerenciar exceções de forma estruturada com contexto rico, persistência em banco e interface unificada. Funciona standalone com contexto mínimo e se enriquece automaticamente quando `laravel-app-context` ou `laravel-tracing` estão instalados.

**Princípios:**
- ✅ Funciona independentemente
- ✅ Contexto base sempre presente
- ✅ Enriquecimento progressivo com outros pacotes
- ✅ Zero configuração obrigatória

---

## Instalação

```bash
composer require juniorfontenele/laravel-exceptions
```

---

## Configuração

```php
// config/exceptions.php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    */
    'enabled' => env('EXCEPTIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Database Logging
    |--------------------------------------------------------------------------
    | Se deve salvar exceptions no banco de dados
    */
    'database' => [
        'enabled' => env('EXCEPTIONS_DATABASE_ENABLED', true),
        'table' => env('EXCEPTIONS_TABLE', 'sys_exceptions'),
        'connection' => env('EXCEPTIONS_DB_CONNECTION', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Context
    |--------------------------------------------------------------------------
    | Configuração de contexto base
    */
    'context' => [
        'include_trace' => env('EXCEPTIONS_INCLUDE_TRACE', true),
        'include_previous' => env('EXCEPTIONS_INCLUDE_PREVIOUS', true),
        'include_request' => env('EXCEPTIONS_INCLUDE_REQUEST', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    | Views para renderização de erros
    */
    'views' => [
        'app_exception' => 'errors.app',
        'http_exception' => 'errors.http',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting
    |--------------------------------------------------------------------------
    | Configuração de report
    */
    'reporting' => [
        'skip_validation_exceptions' => true,
        'skip_authentication_exceptions' => true,
    ],
];
```

---

## Estrutura do Pacote

```
src/
├── ExceptionsServiceProvider.php
├── Exceptions/
│   ├── AppException.php
│   ├── HttpException.php
│   └── Http/
│       ├── NotFoundHttpException.php
│       ├── UnauthorizedHttpException.php
│       ├── ForbiddenHttpException.php
│       └── ... (outras HTTP exceptions)
├── Services/
│   ├── ExceptionHandler.php
│   └── ExceptionContextBuilder.php
├── Models/
│   └── ExceptionLog.php
├── Facades/
│   └── AppException.php
└── Contracts/
    └── ContextEnricher.php
```

---

## Implementação

### 1. Context Builder (Contexto Base)

```php
// src/Services/ExceptionContextBuilder.php
namespace JuniorFontenele\LaravelExceptions\Services;

class ExceptionContextBuilder
{
    /**
     * Constrói contexto base da exception
     */
    public function build(\Throwable $exception): array
    {
        $context = [
            'timestamp' => now()->toIso8601ZuluString(),
            'exception' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
            ],
        ];

        // Adiciona informações básicas da app
        $context['app'] = $this->buildAppContext();

        // Adiciona informações do request
        if (! app()->runningInConsole() && request()) {
            $context['request'] = $this->buildRequestContext();
        }

        // Adiciona user se autenticado
        if (auth()->check()) {
            $context['user'] = $this->buildUserContext();
        }

        // Adiciona trace se configurado
        if (config('exceptions.context.include_trace', true)) {
            $context['exception']['trace'] = $exception->getTraceAsString();
        }

        // Adiciona previous exception se houver
        if ($exception->getPrevious() && config('exceptions.context.include_previous', true)) {
            $context['previous'] = $this->buildPreviousContext($exception->getPrevious());
        }

        // Enriquece com providers externos
        $context = $this->enrichWithProviders($context);

        return $context;
    }

    /**
     * Contexto básico da aplicação
     */
    protected function buildAppContext(): array
    {
        return [
            'name' => config('app.name'),
            'env' => app()->environment(),
            'version' => config('app.version', 'unknown'),
            'debug' => config('app.debug'),
        ];
    }

    /**
     * Contexto do request
     */
    protected function buildRequestContext(): array
    {
        if (! config('exceptions.context.include_request', true)) {
            return [];
        }

        return [
            'method' => request()->method(),
            'url' => request()->fullUrl(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
    }

    /**
     * Contexto do usuário
     */
    protected function buildUserContext(): array
    {
        $user = auth()->user();

        return [
            'id' => $user->getKey(),
            'name' => $user->name ?? null,
            'email' => $user->email ?? null,
        ];
    }

    /**
     * Contexto da exception anterior
     */
    protected function buildPreviousContext(\Throwable $previous): array
    {
        return [
            'class' => get_class($previous),
            'message' => $previous->getMessage(),
            'file' => $previous->getFile(),
            'line' => $previous->getLine(),
            'code' => $previous->getCode(),
        ];
    }

    /**
     * Enriquece contexto com providers externos (laravel-app-context, laravel-tracing)
     */
    protected function enrichWithProviders(array $context): array
    {
        // Se laravel-app-context está instalado
        if (class_exists(\JuniorFontenele\LaravelAppContext\Facades\AppContext::class)) {
            $appContext = \JuniorFontenele\LaravelAppContext\Facades\AppContext::all();
            
            // Merge sem sobrescrever o que já existe
            $context = array_merge($appContext, $context);
        }

        // Se laravel-tracing está instalado
        if (class_exists(\JuniorFontenele\LaravelTracing\Facades\Trace::class)) {
            $tracingContext = \JuniorFontenele\LaravelTracing\Facades\Trace::all();
            $context = array_merge($context, $tracingContext);
        }

        return $context;
    }
}
```

### 2. App Exception Base

```php
// src/Exceptions/AppException.php
namespace JuniorFontenele\LaravelExceptions\Exceptions;

use Exception;
use Illuminate\Support\Str;
use JuniorFontenele\LaravelExceptions\Services\ExceptionContextBuilder;
use Throwable;

class AppException extends Exception
{
    public string $errorId;
    public int $statusCode = 500;
    public string $userMessage = 'Ocorreu um erro na aplicação.';
    protected array $customContext = [];
    protected ?array $builtContext = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        $this->errorId = (string) Str::uuid();
        $this->customContext = $context;

        parent::__construct($message ?: $this->userMessage, $code, $previous);
    }

    /**
     * Adiciona contexto customizado
     */
    public function withContext(array $context): self
    {
        $this->customContext = array_merge($this->customContext, $context);
        $this->builtContext = null; // Invalida cache

        return $this;
    }

    /**
     * Retorna contexto completo
     */
    public function context(): array
    {
        if ($this->builtContext !== null) {
            return $this->builtContext;
        }

        // Constrói contexto base
        $builder = app(ExceptionContextBuilder::class);
        $context = $builder->build($this);

        // Adiciona informações específicas desta exception
        $context['exception']['error_id'] = $this->errorId;
        $context['exception']['status_code'] = $this->statusCode;
        $context['exception']['user_message'] = $this->userMessage;

        // Merge com contexto customizado
        $this->builtContext = array_merge($context, $this->customContext);

        return $this->builtContext;
    }

    /**
     * Mensagem para o usuário final
     */
    public function userMessage(): string
    {
        return $this->userMessage . " (ID: {$this->errorId})";
    }

    /**
     * Se a operação pode ser tentada novamente
     */
    public function isRetryable(): bool
    {
        return false;
    }

    /**
     * Report da exception
     */
    public function report(): bool
    {
        // Salva no banco se configurado
        if (config('exceptions.database.enabled', true)) {
            $this->saveToDatabase();
        }

        // Retorna false para continuar o report padrão do Laravel
        return false;
    }

    /**
     * Salva exception no banco
     */
    protected function saveToDatabase(): void
    {
        try {
            $context = $this->context();

            \JuniorFontenele\LaravelExceptions\Models\ExceptionLog::create([
                'error_id' => $this->errorId,
                'exception_class' => get_class($this),
                'message' => $this->getMessage(),
                'user_message' => $this->userMessage,
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'code' => $this->getCode(),
                'status_code' => $this->statusCode,
                'is_retryable' => $this->isRetryable(),
                
                // Context data
                'app_version' => $context['app']['version'] ?? null,
                'app_env' => $context['app']['env'] ?? null,
                'correlation_id' => $context['correlation_id'] ?? null,
                'request_id' => $context['request_id'] ?? null,
                'user_id' => $context['user']['id'] ?? null,
                
                // JSON fields
                'context' => $context,
                'stack_trace' => $this->getTraceAsString(),
                'previous_exception' => $this->getPrevious() ? [
                    'class' => get_class($this->getPrevious()),
                    'message' => $this->getPrevious()->getMessage(),
                ] : null,
            ]);
        } catch (Throwable $e) {
            // Falha silenciosa para não quebrar a aplicação
            logger()->error('Failed to save exception to database', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Render da exception
     */
    public function render()
    {
        $view = config('exceptions.views.app_exception', 'errors.app');

        return response()->view($view, [
            'code' => $this->errorId,
            'message' => $this->userMessage,
            'status' => $this->statusCode,
        ], $this->statusCode);
    }
}
```

### 3. HTTP Exceptions

```php
// src/Exceptions/HttpException.php
namespace JuniorFontenele\LaravelExceptions\Exceptions;

class HttpException extends AppException
{
    public function __construct(
        int $statusCode = 500,
        string $message = '',
        string $userMessage = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->userMessage = $userMessage ?: "Erro HTTP {$statusCode}";

        parent::__construct($message, $code, $previous);
    }
}
```

```php
// src/Exceptions/Http/NotFoundHttpException.php
namespace JuniorFontenele\LaravelExceptions\Exceptions\Http;

use JuniorFontenele\LaravelExceptions\Exceptions\HttpException;

class NotFoundHttpException extends HttpException
{
    public function __construct(
        string $message = 'Recurso não encontrado.',
        string $userMessage = 'O recurso solicitado não foi encontrado.',
        ?\Throwable $previous = null
    ) {
        parent::__construct(404, $message, $userMessage, 0, $previous);
    }
}
```

```php
// src/Exceptions/Http/UnauthorizedHttpException.php
namespace JuniorFontenele\LaravelExceptions\Exceptions\Http;

use JuniorFontenele\LaravelExceptions\Exceptions\HttpException;

class UnauthorizedHttpException extends HttpException
{
    public function __construct(
        string $message = 'Não autorizado.',
        string $userMessage = 'Você precisa estar autenticado para acessar este recurso.',
        ?\Throwable $previous = null
    ) {
        parent::__construct(401, $message, $userMessage, 0, $previous);
    }
}
```

```php
// src/Exceptions/Http/ForbiddenHttpException.php
namespace JuniorFontenele\LaravelExceptions\Exceptions\Http;

use JuniorFontenele\LaravelExceptions\Exceptions\HttpException;

class ForbiddenHttpException extends HttpException
{
    public function __construct(
        string $message = 'Acesso negado.',
        string $userMessage = 'Você não tem permissão para acessar este recurso.',
        ?\Throwable $previous = null
    ) {
        parent::__construct(403, $message, $userMessage, 0, $previous);
    }
}
```

### 4. Exception Handler

```php
// src/Services/ExceptionHandler.php
namespace JuniorFontenele\LaravelExceptions\Services;

use Illuminate\Foundation\Configuration\Exceptions;
use JuniorFontenele\LaravelExceptions\Exceptions\AppException;
use JuniorFontenele\LaravelExceptions\Exceptions\Http\*;
use Symfony\Component\HttpKernel\Exception\HttpException as SymfonyHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;

class ExceptionHandler
{
    /**
     * Registra handlers customizados
     */
    public static function register(Exceptions $exceptions): void
    {
        // Converte Symfony HTTP Exceptions para nossas exceptions
        $exceptions->render(function (SymfonyHttpException $e) {
            return match ($e->getStatusCode()) {
                404 => throw new NotFoundHttpException(previous: $e),
                401 => throw new UnauthorizedHttpException(previous: $e),
                403 => throw new ForbiddenHttpException(previous: $e),
                500 => throw new InternalServerErrorHttpException(previous: $e),
                503 => throw new ServiceUnavailableHttpException(previous: $e),
                default => null, // Deixa Laravel tratar
            };
        });

        // Captura exceptions não tratadas e converte para AppException
        $exceptions->render(function (\Throwable $e) {
            // Pula exceptions que não devemos converter
            if (static::shouldSkip($e)) {
                return null;
            }

            // Se já é nossa exception, deixa passar
            if ($e instanceof AppException) {
                return null;
            }

            // Converte para AppException
            throw new AppException(
                message: $e->getMessage(),
                code: (int) $e->getCode(),
                previous: $e
            );
        });
    }

    /**
     * Verifica se deve pular a exception
     */
    protected static function shouldSkip(\Throwable $e): bool
    {
        $config = config('exceptions.reporting', []);

        if ($config['skip_validation_exceptions'] ?? true) {
            if ($e instanceof ValidationException) {
                return true;
            }
        }

        if ($config['skip_authentication_exceptions'] ?? true) {
            if ($e instanceof AuthenticationException) {
                return true;
            }
        }

        return false;
    }
}
```

### 5. Model

```php
// src/Models/ExceptionLog.php
namespace JuniorFontenele\LaravelExceptions\Models;

use Illuminate\Database\Eloquent\Model;

class ExceptionLog extends Model
{
    protected $table = 'sys_exceptions';

    public function getTable(): string
    {
        return config('exceptions.database.table', 'sys_exceptions');
    }

    public function getConnectionName()
    {
        return config('exceptions.database.connection') ?? parent::getConnectionName();
    }

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'previous_exception' => 'array',
            'is_retryable' => 'boolean',
        ];
    }
}
```

### 6. Service Provider

```php
// src/ExceptionsServiceProvider.php
namespace JuniorFontenele\LaravelExceptions;

use Illuminate\Support\ServiceProvider;
use JuniorFontenele\LaravelExceptions\Services\ExceptionContextBuilder;

class ExceptionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/exceptions.php', 'exceptions');

        // Registra o context builder
        $this->app->singleton(ExceptionContextBuilder::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publica config
            $this->publishes([
                __DIR__.'/../config/exceptions.php' => config_path('exceptions.php'),
            ], 'exceptions-config');

            // Publica migration
            $this->publishes([
                __DIR__.'/../database/migrations/create_exceptions_table.php' => database_path('migrations/'.date('Y_m_d_His').'_create_exceptions_table.php'),
            ], 'exceptions-migration');

            // Publica views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/errors'),
            ], 'exceptions-views');
        }

        // Carrega views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'exceptions');
    }
}
```

---

## Como o Template Usa

### 1. Instalação

```json
// composer.json
{
    "require": {
        "juniorfontenele/laravel-exceptions": "^2.0"
    }
}
```

### 2. Registro no Bootstrap

```php
// bootstrap/app.php
use JuniorFontenele\LaravelExceptions\Services\ExceptionHandler;

return Application::configure(basePath: dirname(__DIR__))
    ->withExceptions(function (Exceptions $exceptions): void {
        ExceptionHandler::register($exceptions);
    })
    ->create();
```

### 3. Publicar Assets

```bash
php artisan vendor:publish --tag=exceptions-migration
php artisan vendor:publish --tag=exceptions-views
php artisan migrate
```

### 4. Uso na Aplicação

```php
use JuniorFontenele\LaravelExceptions\Exceptions\AppException;
use JuniorFontenele\LaravelExceptions\Exceptions\Http\NotFoundHttpException;

// Exception genérica
throw new AppException('Something went wrong');

// HTTP Exception
throw new NotFoundHttpException('User not found');

// Com contexto customizado
throw new AppException('Payment failed')
    ->withContext([
        'payment_id' => $payment->id,
        'amount' => $payment->amount,
    ]);
```

---

## Exemplos de Contexto

### Standalone (sem outros pacotes)

```php
throw new AppException('Error');

// Contexto gerado:
// {
//     "timestamp": "2026-01-15T10:00:00Z",
//     "exception": {
//         "class": "AppException",
//         "message": "Error",
//         "error_id": "abc-123",
//         "status_code": 500
//     },
//     "app": {
//         "name": "MyApp",
//         "env": "production",
//         "version": "1.0.0",
//         "debug": false
//     },
//     "request": {
//         "method": "GET",
//         "url": "https://example.com"
//     }
// }
```

### Com laravel-app-context instalado

```php
throw new AppException('Error');

// Contexto enriquecido:
// {
//     "timestamp": "2026-01-15T10:00:00Z",
//     "app": {
//         "name": "MyApp",
//         "version": "1.0.0",
//         "commit": "abc123",        // ← Adicionado
//         "build_date": "20260115",  // ← Adicionado
//         "role": "api"              // ← Adicionado
//     },
//     "host": {                      // ← Adicionado
//         "name": "server-01",
//         "ip": "10.0.0.1"
//     },
//     "exception": { ... }
// }
```

### Com laravel-tracing instalado

```php
throw new AppException('Error');

// Contexto enriquecido:
// {
//     "timestamp": "2026-01-15T10:00:00Z",
//     "correlation_id": "xyz-789",  // ← Adicionado
//     "request_id": "req-456",      // ← Adicionado
//     "app": { ... },
//     "exception": { ... }
// }
```

---

## Testes

```php
use JuniorFontenele\LaravelExceptions\Exceptions\AppException;

it('creates exception with error id', function () {
    $exception = new AppException('Test error');
    
    expect($exception->errorId)->not->toBeNull();
});

it('builds context with app info', function () {
    config(['app.name' => 'TestApp']);
    
    $exception = new AppException('Test');
    $context = $exception->context();
    
    expect($context)
        ->toHaveKey('app')
        ->and($context['app']['name'])->toBe('TestApp');
});

it('enriches with custom context', function () {
    $exception = new AppException('Test');
    $exception->withContext(['custom' => 'data']);
    
    $context = $exception->context();
    
    expect($context)->toHaveKey('custom');
});
```

---

## Métricas de Sucesso

- ✅ Funciona standalone sem dependências
- ✅ Enriquecimento automático quando outros pacotes instalados
- ✅ Contexto sempre presente
- ✅ Persistência em banco opcional
- ✅ Zero configuração obrigatória
