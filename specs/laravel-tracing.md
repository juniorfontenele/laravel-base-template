# Laravel Tracing - Especificação Detalhada

## Visão Geral

Pacote simples para gerenciar tracing distribuído através de `correlation_id` e `request_id`. Propaga IDs entre requests HTTP e adiciona aos logs automaticamente.

**Princípios:**
- ✅ Extremamente simples - foco no essencial
- ✅ Sem dependencies pesadas (não é OpenTelemetry)
- ✅ Propagação automática
- ✅ Integração fácil com outros pacotes

---

## Instalação

```bash
composer require juniorfontenele/laravel-tracing
```

---

## Configuração

```php
// config/tracing.php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    */
    'enabled' => env('TRACING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | ID Generators
    |--------------------------------------------------------------------------
    | Classes responsáveis por gerar IDs
    */
    'generators' => [
        'correlation_id' => \JuniorFontenele\LaravelTracing\Generators\UuidGenerator::class,
        'request_id' => \JuniorFontenele\LaravelTracing\Generators\UuidGenerator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    | Como os IDs são armazenados durante o request
    | Opções: 'session', 'context'
    */
    'storage' => env('TRACING_STORAGE', 'session'),

    /*
    |--------------------------------------------------------------------------
    | Headers
    |--------------------------------------------------------------------------
    | Nome dos headers HTTP
    */
    'headers' => [
        'correlation_id' => env('TRACING_HEADER_CORRELATION_ID', 'X-Correlation-ID'),
        'request_id' => env('TRACING_HEADER_REQUEST_ID', 'X-Request-ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Propagation
    |--------------------------------------------------------------------------
    | Se deve aceitar correlation_id de requests externos
    */
    'accept_external_correlation_id' => env('TRACING_ACCEPT_EXTERNAL_CORRELATION_ID', true),

    /*
    |--------------------------------------------------------------------------
    | Integration
    |--------------------------------------------------------------------------
    | Integrações automáticas
    */
    'integration' => [
        'log' => env('TRACING_LOG_INTEGRATION', true),
        'http_client' => env('TRACING_HTTP_CLIENT_INTEGRATION', true),
    ],
];
```

---

## Estrutura do Pacote

```
src/
├── TracingServiceProvider.php
├── Facades/
│   └── Trace.php
├── Services/
│   └── TraceManager.php
├── Middleware/
│   └── TraceRequests.php
├── Generators/
│   ├── UuidGenerator.php
│   └── UlidGenerator.php
├── Storage/
│   ├── SessionStorage.php
│   └── ContextStorage.php
└── Contracts/
    ├── IdGenerator.php
    └── TraceStorage.php
```

---

## Implementação

### 1. Contracts

```php
// src/Contracts/IdGenerator.php
namespace JuniorFontenele\LaravelTracing\Contracts;

interface IdGenerator
{
    public function generate(): string;
}
```

```php
// src/Contracts/TraceStorage.php
namespace JuniorFontenele\LaravelTracing\Contracts;

interface TraceStorage
{
    public function set(string $key, string $value): void;
    public function get(string $key): ?string;
    public function has(string $key): bool;
}
```

### 2. ID Generators

```php
// src/Generators/UuidGenerator.php
namespace JuniorFontenele\LaravelTracing\Generators;

use JuniorFontenele\LaravelTracing\Contracts\IdGenerator;
use Illuminate\Support\Str;

class UuidGenerator implements IdGenerator
{
    public function generate(): string
    {
        return (string) Str::uuid();
    }
}
```

```php
// src/Generators/UlidGenerator.php
namespace JuniorFontenele\LaravelTracing\Generators;

use JuniorFontenele\LaravelTracing\Contracts\IdGenerator;
use Illuminate\Support\Str;

class UlidGenerator implements IdGenerator
{
    public function generate(): string
    {
        return (string) Str::ulid();
    }
}
```

### 3. Storage

```php
// src/Storage/SessionStorage.php
namespace JuniorFontenele\LaravelTracing\Storage;

use JuniorFontenele\LaravelTracing\Contracts\TraceStorage;

class SessionStorage implements TraceStorage
{
    public function set(string $key, string $value): void
    {
        session()->put("tracing.{$key}", $value);
    }

    public function get(string $key): ?string
    {
        return session()->get("tracing.{$key}");
    }

    public function has(string $key): bool
    {
        return session()->has("tracing.{$key}");
    }
}
```

```php
// src/Storage/ContextStorage.php
namespace JuniorFontenele\LaravelTracing\Storage;

use JuniorFontenele\LaravelTracing\Contracts\TraceStorage;

class ContextStorage implements TraceStorage
{
    protected array $storage = [];

    public function set(string $key, string $value): void
    {
        $this->storage[$key] = $value;
    }

    public function get(string $key): ?string
    {
        return $this->storage[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }
}
```

### 4. Trace Manager

```php
// src/Services/TraceManager.php
namespace JuniorFontenele\LaravelTracing\Services;

use JuniorFontenele\LaravelTracing\Contracts\{IdGenerator, TraceStorage};

class TraceManager
{
    public function __construct(
        protected array $config,
        protected TraceStorage $storage,
        protected array $generators
    ) {}

    /**
     * Inicia o tracing para o request atual
     */
    public function start(?string $externalCorrelationId = null): void
    {
        // Correlation ID: reutiliza se vier de fora, senão cria novo
        if ($externalCorrelationId && $this->config['accept_external_correlation_id']) {
            $this->storage->set('correlation_id', $externalCorrelationId);
        } elseif (! $this->storage->has('correlation_id')) {
            $correlationId = $this->generators['correlation_id']->generate();
            $this->storage->set('correlation_id', $correlationId);
        }

        // Request ID: sempre novo para cada request
        $requestId = $this->generators['request_id']->generate();
        $this->storage->set('request_id', $requestId);
    }

    /**
     * Retorna o correlation ID
     */
    public function correlationId(): ?string
    {
        return $this->storage->get('correlation_id');
    }

    /**
     * Retorna o request ID
     */
    public function requestId(): ?string
    {
        return $this->storage->get('request_id');
    }

    /**
     * Retorna todos os IDs
     */
    public function all(): array
    {
        return [
            'correlation_id' => $this->correlationId(),
            'request_id' => $this->requestId(),
        ];
    }

    /**
     * Headers para propagação HTTP
     */
    public function propagationHeaders(): array
    {
        $headers = [];
        
        if ($correlationId = $this->correlationId()) {
            $headerName = $this->config['headers']['correlation_id'];
            $headers[$headerName] = $correlationId;
        }

        return $headers;
    }

    /**
     * Provider de contexto para laravel-app-context
     */
    public function contextProvider(): array
    {
        return $this->all();
    }
}
```

### 5. Middleware

```php
// src/Middleware/TraceRequests.php
namespace JuniorFontenele\LaravelTracing\Middleware;

use Closure;
use Illuminate\Http\Request;
use JuniorFontenele\LaravelTracing\Services\TraceManager;
use Symfony\Component\HttpFoundation\Response;

class TraceRequests
{
    public function __construct(
        protected TraceManager $traceManager,
        protected array $config
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Extrai correlation_id do header se presente
        $headerName = $this->config['headers']['correlation_id'];
        $externalCorrelationId = $request->header($headerName);

        // Inicia o tracing
        $this->traceManager->start($externalCorrelationId);

        // Adiciona ao log se configurado
        if ($this->config['integration']['log'] ?? false) {
            \Log::shareContext($this->traceManager->all());
        }

        // Processa o request
        $response = $next($request);

        // Adiciona headers na resposta
        $response->headers->set(
            $this->config['headers']['correlation_id'],
            $this->traceManager->correlationId()
        );

        $response->headers->set(
            $this->config['headers']['request_id'],
            $this->traceManager->requestId()
        );

        return $response;
    }
}
```

### 6. Service Provider

```php
// src/TracingServiceProvider.php
namespace JuniorFontenele\LaravelTracing;

use Illuminate\Support\ServiceProvider;
use JuniorFontenele\LaravelTracing\Services\TraceManager;
use JuniorFontenele\LaravelTracing\Storage\{SessionStorage, ContextStorage};
use Illuminate\Support\Facades\Http;

class TracingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tracing.php', 'tracing');

        // Registra storage
        $this->app->singleton('tracing.storage', function ($app) {
            $storageType = config('tracing.storage', 'session');
            
            return match ($storageType) {
                'context' => new ContextStorage(),
                default => new SessionStorage(),
            };
        });

        // Registra generators
        $this->app->singleton('tracing.generators', function ($app) {
            $generatorConfig = config('tracing.generators');
            
            return [
                'correlation_id' => $app->make($generatorConfig['correlation_id']),
                'request_id' => $app->make($generatorConfig['request_id']),
            ];
        });

        // Registra TraceManager
        $this->app->singleton(TraceManager::class, function ($app) {
            return new TraceManager(
                config('tracing'),
                $app->make('tracing.storage'),
                $app->make('tracing.generators')
            );
        });

        $this->app->alias(TraceManager::class, 'tracing');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/tracing.php' => config_path('tracing.php'),
            ], 'tracing-config');
        }

        // Integração com HTTP Client
        if (config('tracing.integration.http_client', true)) {
            $this->registerHttpClientMacro();
        }

        // Se laravel-app-context está instalado, registra provider
        if (class_exists(\JuniorFontenele\LaravelAppContext\Facades\AppContext::class)) {
            $this->registerAppContextProvider();
        }
    }

    protected function registerHttpClientMacro(): void
    {
        Http::macro('withTracing', function () {
            /** @var \Illuminate\Http\Client\PendingRequest $this */
            $traceManager = app(TraceManager::class);
            
            return $this->withHeaders($traceManager->propagationHeaders());
        });
    }

    protected function registerAppContextProvider(): void
    {
        // Registra provider de tracing no app-context
        $this->app->booted(function ($app) {
            if ($app->bound(\JuniorFontenele\LaravelAppContext\Services\ContextManager::class)) {
                $contextManager = $app->make(\JuniorFontenele\LaravelAppContext\Services\ContextManager::class);
                $contextManager->addProvider(new \JuniorFontenele\LaravelTracing\Integration\TracingContextProvider());
            }
        });
    }
}
```

### 7. Integração com App Context

```php
// src/Integration/TracingContextProvider.php
namespace JuniorFontenele\LaravelTracing\Integration;

use JuniorFontenele\LaravelAppContext\Contracts\ContextProvider;
use JuniorFontenele\LaravelTracing\Facades\Trace;

class TracingContextProvider implements ContextProvider
{
    public function provide(): array
    {
        return Trace::all();
    }

    public function shouldRun(): bool
    {
        return true;
    }
}
```

### 8. Facade

```php
// src/Facades/Trace.php
namespace JuniorFontenele\LaravelTracing\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void start(?string $externalCorrelationId = null)
 * @method static string|null correlationId()
 * @method static string|null requestId()
 * @method static array all()
 * @method static array propagationHeaders()
 */
class Trace extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JuniorFontenele\LaravelTracing\Services\TraceManager::class;
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
        "juniorfontenele/laravel-tracing": "^1.0"
    }
}
```

### 2. Registro no Bootstrap

```php
// bootstrap/app.php
use JuniorFontenele\LaravelTracing\Middleware\TraceRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            TraceRequests::class,
        ]);
    })
    ->create();
```

### 3. Uso na Aplicação

```php
use JuniorFontenele\LaravelTracing\Facades\Trace;

// Obter IDs
$correlationId = Trace::correlationId();
$requestId = Trace::requestId();

// Fazer requisição HTTP com propagação
Http::withTracing()
    ->get('https://api.example.com/data');

// Headers propagados automaticamente:
// X-Correlation-ID: abc-123
```

---

## Exemplos de Uso

### 1. Propagação entre Serviços

```php
// Serviço A
Http::withTracing()->post('https://service-b.com/api', $data);
// Envia: X-Correlation-ID: abc-123

// Serviço B recebe o mesmo correlation_id
// Tracing automático continua a cadeia
```

### 2. Logs com Tracing

```php
// Todos os logs terão automaticamente:
Log::info('Processing order');

// {
//     "correlation_id": "abc-123",
//     "request_id": "xyz-789",
//     "message": "Processing order"
// }
```

### 3. Debugging de Requisições

```php
// Em qualquer ponto do código
logger()->info('Debug point', [
    'correlation_id' => Trace::correlationId(),
    'request_id' => Trace::requestId(),
]);

// Permite rastrear toda a cadeia de requisições
```

---

## Testes

```php
use JuniorFontenele\LaravelTracing\Services\TraceManager;
use JuniorFontenele\LaravelTracing\Storage\ContextStorage;
use JuniorFontenele\LaravelTracing\Generators\UuidGenerator;

it('generates new correlation and request ids', function () {
    $storage = new ContextStorage();
    $generators = [
        'correlation_id' => new UuidGenerator(),
        'request_id' => new UuidGenerator(),
    ];
    
    $manager = new TraceManager(['accept_external_correlation_id' => false], $storage, $generators);
    $manager->start();
    
    expect($manager->correlationId())->not->toBeNull();
    expect($manager->requestId())->not->toBeNull();
});

it('accepts external correlation id', function () {
    $storage = new ContextStorage();
    $generators = [
        'correlation_id' => new UuidGenerator(),
        'request_id' => new UuidGenerator(),
    ];
    
    $manager = new TraceManager(['accept_external_correlation_id' => true], $storage, $generators);
    $manager->start('external-123');
    
    expect($manager->correlationId())->toBe('external-123');
});

it('provides propagation headers', function () {
    $storage = new ContextStorage();
    $generators = [
        'correlation_id' => new UuidGenerator(),
        'request_id' => new UuidGenerator(),
    ];
    
    $config = [
        'accept_external_correlation_id' => false,
        'headers' => ['correlation_id' => 'X-Correlation-ID'],
    ];
    
    $manager = new TraceManager($config, $storage, $generators);
    $manager->start();
    
    $headers = $manager->propagationHeaders();
    
    expect($headers)->toHaveKey('X-Correlation-ID');
});
```

---

## Métricas de Sucesso

- ✅ Propagação automática entre serviços
- ✅ Zero configuração manual
- ✅ Logs rastreáveis
- ✅ Debugging facilitado
- ✅ Performance (operações simples)
