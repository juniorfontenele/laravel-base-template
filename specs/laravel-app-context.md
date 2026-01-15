# Laravel App Context - Especificação Detalhada

## Visão Geral

Pacote para gerenciar contexto da aplicação de forma centralizada e consistente. Fornece informações sobre app, host, request e user que podem ser usadas em logs, Sentry, headers HTTP e outros pacotes.

**Princípios:**
- ✅ Simples e direto
- ✅ Contexto enriquecido progressivamente
- ✅ Sem dependências complexas
- ✅ Fácil de estender

---

## Instalação

```bash
composer require juniorfontenele/laravel-app-context
```

O pacote se auto-registra via auto-discovery.

---

## Configuração

```php
// config/app-context.php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    | Desabilita completamente o contexto se false
    */
    'enabled' => env('APP_CONTEXT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    | Providers que coletam informações de contexto
    | Execute na ordem listada
    */
    'providers' => [
        \JuniorFontenele\LaravelAppContext\Providers\TimestampProvider::class,
        \JuniorFontenele\LaravelAppContext\Providers\AppProvider::class,
        \JuniorFontenele\LaravelAppContext\Providers\HostProvider::class,
        \JuniorFontenele\LaravelAppContext\Providers\RequestProvider::class,
        \JuniorFontenele\LaravelAppContext\Providers\UserProvider::class,
        
        // Adicione providers customizados aqui
    ],

    /*
    |--------------------------------------------------------------------------
    | Channels
    |--------------------------------------------------------------------------
    | Onde o contexto será enviado automaticamente
    */
    'channels' => [
        'log' => env('APP_CONTEXT_LOG', true),
        'sentry' => env('APP_CONTEXT_SENTRY', true),
        'headers' => env('APP_CONTEXT_HEADERS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Headers
    |--------------------------------------------------------------------------
    | Headers HTTP que serão adicionados automaticamente
    */
    'headers' => [
        'X-App-Version' => 'app.version',
        'X-App-Commit' => 'app.commit',
        // key => path no contexto usando dot notation
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    | Cache do contexto durante o request para melhor performance
    */
    'cache' => [
        'enabled' => true,
        'ttl' => null, // null = durante o request apenas
    ],
];
```

---

## Estrutura do Pacote

```
src/
├── AppContextServiceProvider.php
├── Facades/
│   └── AppContext.php
├── Services/
│   └── ContextManager.php
├── Providers/
│   ├── AbstractContextProvider.php
│   ├── TimestampProvider.php
│   ├── AppProvider.php
│   ├── HostProvider.php
│   ├── RequestProvider.php
│   └── UserProvider.php
├── Channels/
│   ├── LogChannel.php
│   ├── SentryChannel.php
│   └── HeaderChannel.php
├── Middleware/
│   └── AddContextToResponse.php
└── Contracts/
    ├── ContextProvider.php
    └── ContextChannel.php
```

---

## Implementação

### 1. Contracts

```php
// src/Contracts/ContextProvider.php
namespace JuniorFontenele\LaravelAppContext\Contracts;

interface ContextProvider
{
    /**
     * Retorna array com dados de contexto
     */
    public function provide(): array;
    
    /**
     * Determina se o provider deve ser executado
     */
    public function shouldRun(): bool;
}
```

```php
// src/Contracts/ContextChannel.php
namespace JuniorFontenele\LaravelAppContext\Contracts;

interface ContextChannel
{
    /**
     * Envia o contexto para o canal
     */
    public function send(array $context): void;
}
```

### 2. Context Manager (Core)

```php
// src/Services/ContextManager.php
namespace JuniorFontenele\LaravelAppContext\Services;

use JuniorFontenele\LaravelAppContext\Contracts\ContextProvider;
use JuniorFontenele\LaravelAppContext\Contracts\ContextChannel;
use Illuminate\Support\Arr;

class ContextManager
{
    protected array $context = [];
    protected array $providers = [];
    protected array $channels = [];
    protected bool $built = false;

    public function __construct(
        protected array $config
    ) {}

    /**
     * Registra um provider
     */
    public function addProvider(ContextProvider $provider): self
    {
        $this->providers[] = $provider;
        $this->built = false;
        
        return $this;
    }

    /**
     * Registra um channel
     */
    public function addChannel(string $name, ContextChannel $channel): self
    {
        $this->channels[$name] = $channel;
        
        return $this;
    }

    /**
     * Constrói o contexto executando todos os providers
     */
    public function build(): array
    {
        if ($this->built) {
            return $this->context;
        }

        $this->context = [];

        foreach ($this->providers as $provider) {
            if ($provider->shouldRun()) {
                $this->context = array_merge(
                    $this->context,
                    $provider->provide()
                );
            }
        }

        $this->built = true;
        $this->dispatch();

        return $this->context;
    }

    /**
     * Despacha contexto para todos os channels
     */
    protected function dispatch(): void
    {
        foreach ($this->channels as $name => $channel) {
            if ($this->isChannelEnabled($name)) {
                $channel->send($this->context);
            }
        }
    }

    /**
     * Verifica se channel está habilitado
     */
    protected function isChannelEnabled(string $name): bool
    {
        return $this->config['channels'][$name] ?? false;
    }

    /**
     * Retorna todo o contexto
     */
    public function all(): array
    {
        if (! $this->built) {
            $this->build();
        }

        return $this->context;
    }

    /**
     * Retorna valor específico do contexto
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    /**
     * Adiciona valor ao contexto manualmente
     */
    public function set(string $key, mixed $value): self
    {
        Arr::set($this->context, $key, $value);
        
        return $this;
    }

    /**
     * Limpa o cache do contexto
     */
    public function refresh(): self
    {
        $this->built = false;
        $this->context = [];
        
        return $this;
    }
}
```

### 3. Providers (Coletores de Contexto)

```php
// src/Providers/AbstractContextProvider.php
namespace JuniorFontenele\LaravelAppContext\Providers;

use JuniorFontenele\LaravelAppContext\Contracts\ContextProvider;

abstract class AbstractContextProvider implements ContextProvider
{
    public function shouldRun(): bool
    {
        return true;
    }
}
```

```php
// src/Providers/TimestampProvider.php
namespace JuniorFontenele\LaravelAppContext\Providers;

class TimestampProvider extends AbstractContextProvider
{
    public function provide(): array
    {
        return [
            'timestamp' => now()->toIso8601ZuluString(),
        ];
    }
}
```

```php
// src/Providers/AppProvider.php
namespace JuniorFontenele\LaravelAppContext\Providers;

class AppProvider extends AbstractContextProvider
{
    public function provide(): array
    {
        return [
            'app' => [
                'name' => config('app.name'),
                'env' => app()->environment(),
                'debug' => config('app.debug'),
                'version' => config('app.version', 'unknown'),
                'commit' => config('app.commit'),
                'build_date' => config('app.build_date'),
                'role' => config('app.role'),
                'locale' => app()->getLocale(),
                'timezone' => config('app.timezone'),
                'type' => app()->runningInConsole() ? 'console' : 'http',
            ],
        ];
    }
}
```

```php
// src/Providers/HostProvider.php
namespace JuniorFontenele\LaravelAppContext\Providers;

class HostProvider extends AbstractContextProvider
{
    public function provide(): array
    {
        $hostname = gethostname();
        
        return [
            'host' => [
                'name' => $hostname ?: null,
                'ip' => $hostname ? gethostbyname($hostname) : null,
            ],
        ];
    }
}
```

```php
// src/Providers/RequestProvider.php
namespace JuniorFontenele\LaravelAppContext\Providers;

class RequestProvider extends AbstractContextProvider
{
    public function shouldRun(): bool
    {
        return ! app()->runningInConsole() && request() !== null;
    }

    public function provide(): array
    {
        return [
            'request' => [
                'method' => request()->method(),
                'url' => request()->fullUrl(),
                'path' => request()->path(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referer' => request()->header('referer'),
            ],
        ];
    }
}
```

```php
// src/Providers/UserProvider.php
namespace JuniorFontenele\LaravelAppContext\Providers;

use Illuminate\Support\Facades\Auth;

class UserProvider extends AbstractContextProvider
{
    public function shouldRun(): bool
    {
        return Auth::check();
    }

    public function provide(): array
    {
        $user = Auth::user();
        
        return [
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
            ],
        ];
    }
}
```

### 4. Channels (Destinos do Contexto)

```php
// src/Channels/LogChannel.php
namespace JuniorFontenele\LaravelAppContext\Channels;

use JuniorFontenele\LaravelAppContext\Contracts\ContextChannel;
use Illuminate\Support\Facades\Log;

class LogChannel implements ContextChannel
{
    public function send(array $context): void
    {
        Log::shareContext($context);
    }
}
```

```php
// src/Channels/SentryChannel.php
namespace JuniorFontenele\LaravelAppContext\Channels;

use JuniorFontenele\LaravelAppContext\Contracts\ContextChannel;
use function Sentry\configureScope;
use Sentry\State\Scope;

class SentryChannel implements ContextChannel
{
    public function send(array $context): void
    {
        if (! app()->bound('sentry')) {
            return;
        }

        configureScope(function (Scope $scope) use ($context) {
            // Tags (para filtros)
            if (isset($context['app'])) {
                $scope->setTag('app.version', $context['app']['version'] ?? null);
                $scope->setTag('app.env', $context['app']['env'] ?? null);
                $scope->setTag('app.role', $context['app']['role'] ?? null);
            }

            // Contextos (para debugging)
            foreach (['app', 'host', 'request', 'user'] as $key) {
                if (isset($context[$key])) {
                    $scope->setContext(ucfirst($key), $context[$key]);
                }
            }

            // User
            if (isset($context['user'])) {
                $scope->setUser($context['user']);
            }
        });
    }
}
```

```php
// src/Channels/HeaderChannel.php
namespace JuniorFontenele\LaravelAppContext\Channels;

use JuniorFontenele\LaravelAppContext\Contracts\ContextChannel;
use Illuminate\Support\Arr;

class HeaderChannel implements ContextChannel
{
    public function __construct(
        protected array $config
    ) {}

    public function send(array $context): void
    {
        // Headers serão adicionados no middleware
        // Este channel apenas registra o contexto no container
        app()->instance('app-context.headers', $this->prepareHeaders($context));
    }

    protected function prepareHeaders(array $context): array
    {
        $headers = [];
        $headerConfig = $this->config['headers'] ?? [];

        foreach ($headerConfig as $headerName => $contextPath) {
            $value = Arr::get($context, $contextPath);
            
            if ($value !== null) {
                $headers[$headerName] = (string) $value;
            }
        }

        return $headers;
    }
}
```

### 5. Middleware

```php
// src/Middleware/AddContextToResponse.php
namespace JuniorFontenele\LaravelAppContext\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddContextToResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Adiciona headers se configurado
        if ($headers = app('app-context.headers', [])) {
            foreach ($headers as $name => $value) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
```

### 6. Service Provider

```php
// src/AppContextServiceProvider.php
namespace JuniorFontenele\LaravelAppContext;

use Illuminate\Support\ServiceProvider;
use JuniorFontenele\LaravelAppContext\Services\ContextManager;
use JuniorFontenele\LaravelAppContext\Channels\{LogChannel, SentryChannel, HeaderChannel};

class AppContextServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/app-context.php', 'app-context');

        // Registra o ContextManager como singleton
        $this->app->singleton(ContextManager::class, function ($app) {
            $config = config('app-context');
            $manager = new ContextManager($config);

            // Registra providers
            foreach ($config['providers'] as $providerClass) {
                $manager->addProvider($app->make($providerClass));
            }

            // Registra channels
            $manager->addChannel('log', new LogChannel());
            $manager->addChannel('sentry', new SentryChannel());
            $manager->addChannel('headers', new HeaderChannel($config));

            return $manager;
        });

        // Alias para facilitar injeção de dependência
        $this->app->alias(ContextManager::class, 'app-context');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/app-context.php' => config_path('app-context.php'),
            ], 'app-context-config');
        }

        // Constrói o contexto no boot se habilitado
        if (config('app-context.enabled', true)) {
            $this->app->make(ContextManager::class)->build();
        }
    }
}
```

### 7. Facade

```php
// src/Facades/AppContext.php
namespace JuniorFontenele\LaravelAppContext\Facades;

use Illuminate\Support\Facades\Facade;
use JuniorFontenele\LaravelAppContext\Services\ContextManager;

/**
 * @method static array all()
 * @method static mixed get(string $key, mixed $default = null)
 * @method static self set(string $key, mixed $value)
 * @method static array build()
 * @method static self refresh()
 * @method static self addProvider(\JuniorFontenele\LaravelAppContext\Contracts\ContextProvider $provider)
 * @method static self addChannel(string $name, \JuniorFontenele\LaravelAppContext\Contracts\ContextChannel $channel)
 */
class AppContext extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ContextManager::class;
    }
}
```

---

## Como o Template Usa

### 1. Instalação no Template

```json
// composer.json
{
    "require": {
        "juniorfontenele/laravel-app-context": "^1.0"
    }
}
```

### 2. Registro no Bootstrap

```php
// bootstrap/app.php
use JuniorFontenele\LaravelAppContext\Middleware\AddContextToResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            AddContextToResponse::class,
        ]);
    })
    ->create();
```

### 3. Uso na Aplicação

```php
// Em qualquer lugar
use JuniorFontenele\LaravelAppContext\Facades\AppContext;

// Obter todo o contexto
$context = AppContext::all();

// Obter valor específico
$version = AppContext::get('app.version');
$userId = AppContext::get('user.id');

// Adicionar contexto customizado
AppContext::set('custom.data', 'value');

// Forçar rebuild
AppContext::refresh()->build();
```

### 4. Providers Customizados no Template

```php
// app/Context/TenantProvider.php
namespace App\Context;

use JuniorFontenele\LaravelAppContext\Providers\AbstractContextProvider;

class TenantProvider extends AbstractContextProvider
{
    public function shouldRun(): bool
    {
        return tenant() !== null;
    }

    public function provide(): array
    {
        return [
            'tenant' => [
                'id' => tenant()->id,
                'name' => tenant()->name,
            ],
        ];
    }
}
```

```php
// config/app-context.php (publicado)
return [
    'providers' => [
        // ... providers padrão
        \App\Context\TenantProvider::class,
    ],
];
```

---

## Exemplos de Uso

### 1. Log com Contexto Rico

```php
// Automaticamente todos os logs terão o contexto
Log::info('User action', ['action' => 'login']);

// Resultado:
// {
//     "timestamp": "2026-01-15T10:30:00Z",
//     "app": {"name": "MyApp", "version": "1.0.0", ...},
//     "host": {"name": "server-01", ...},
//     "request": {"method": "POST", "url": "...", ...},
//     "user": {"id": 1, "email": "user@example.com"},
//     "message": "User action",
//     "context": {"action": "login"}
// }
```

### 2. Uso em Exceptions

```php
// No laravel-exceptions
use JuniorFontenele\LaravelAppContext\Facades\AppContext;

throw new AppException(
    message: 'Something went wrong',
    context: AppContext::all() // Contexto rico automático
);
```

### 3. Headers HTTP

```php
// Resposta HTTP terá automaticamente:
// X-App-Version: 1.0.0
// X-App-Commit: abc123
```

---

## Testes

```php
// tests/Unit/ContextManagerTest.php
use JuniorFontenele\LaravelAppContext\Services\ContextManager;
use JuniorFontenele\LaravelAppContext\Providers\TimestampProvider;

it('builds context from providers', function () {
    $config = ['channels' => []];
    $manager = new ContextManager($config);
    
    $manager->addProvider(new TimestampProvider());
    
    $context = $manager->build();
    
    expect($context)->toHaveKey('timestamp');
});

it('caches context after build', function () {
    $config = ['channels' => []];
    $manager = new ContextManager($config);
    
    $manager->addProvider(new TimestampProvider());
    
    $first = $manager->build();
    $second = $manager->build();
    
    expect($first)->toBe($second);
});

it('refreshes context when requested', function () {
    $config = ['channels' => []];
    $manager = new ContextManager($config);
    
    $manager->addProvider(new TimestampProvider());
    
    $first = $manager->build();
    sleep(1);
    $second = $manager->refresh()->build();
    
    expect($first['timestamp'])->not->toBe($second['timestamp']);
});
```

---

## Métricas de Sucesso

- ✅ Contexto consistente em toda a aplicação
- ✅ Fácil adicionar novos providers
- ✅ Zero configuração necessária (funciona out of the box)
- ✅ Performance (contexto cacheado durante request)
- ✅ Extensível e testável
