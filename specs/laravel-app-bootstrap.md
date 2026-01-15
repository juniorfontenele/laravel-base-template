# Laravel App Bootstrap - Especificação Detalhada

## Visão Geral

Pacote para centralizar configurações comuns de bootstrap que todos os projetos Laravel precisam. Evita repetir código de configuração em AppServiceProvider.

**Princípios:**
- ✅ Configurações sensatas por padrão
- ✅ Fácil de customizar
- ✅ Sem mágica - apenas convenção
- ✅ Documentado e explícito

---

## Instalação

```bash
composer require juniorfontenele/laravel-app-bootstrap
```

---

## Configuração

```php
// config/app-bootstrap.php

return [
    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    | Configurações para Eloquent Models
    */
    'models' => [
        'unguard' => env('MODELS_UNGUARD', true),
        'eager_loading' => env('MODELS_EAGER_LOADING', true),
        'strict_mode' => env('MODELS_STRICT_MODE', 'production'), // production, always, never
        'sentry_violations' => env('MODELS_SENTRY_VIOLATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    | Configurações para banco de dados
    */
    'database' => [
        'default_string_length' => env('DB_DEFAULT_STRING_LENGTH', 255),
        'default_charset' => env('DB_DEFAULT_CHARSET', 'utf8mb4'),
        'default_collation' => env('DB_DEFAULT_COLLATION', 'utf8mb4_0900_ai_ci'),
        'prohibit_destructive_commands' => env('DB_PROHIBIT_DESTRUCTIVE', 'production'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dates
    |--------------------------------------------------------------------------
    | Configurações para datas
    */
    'dates' => [
        'use_immutable' => env('DATES_USE_IMMUTABLE', true),
        'default_timezone' => env('APP_TIMEZONE', 'UTC'),
        'users_timezone' => env('APP_USERS_TIMEZONE', 'America/Fortaleza'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    | Configurações para HTTP
    */
    'http' => [
        // null = auto detect (força em prod, detecta em dev)
        // true = sempre força
        // false = nunca força
        'force_https' => env('APP_FORCE_HTTPS', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Passwords
    |--------------------------------------------------------------------------
    | Configurações para validação de senhas
    */
    'passwords' => [
        'enabled' => env('PASSWORD_VALIDATION_ENABLED', true),
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_mixed_case' => env('PASSWORD_REQUIRE_MIXED_CASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
        'require_uncompromised' => env('PASSWORD_REQUIRE_UNCOMPROMISED', true),
        'environments' => ['production', 'staging'],
    ],
];
```

---

## Estrutura do Pacote

```
src/
├── AppBootstrapServiceProvider.php
├── Configurators/
│   ├── ModelConfigurator.php
│   ├── DatabaseConfigurator.php
│   ├── DateConfigurator.php
│   ├── HttpConfigurator.php
│   └── PasswordConfigurator.php
└── Macros/
    └── BlueprintMacros.php
```

---

## Implementação

### 1. Configurators

```php
// src/Configurators/ModelConfigurator.php
namespace JuniorFontenele\LaravelAppBootstrap\Configurators;

use Illuminate\Database\Eloquent\Model;
use Sentry\Laravel\Integration;

class ModelConfigurator
{
    public function __construct(
        protected array $config
    ) {}

    public function configure(): void
    {
        // Unguard
        if ($this->config['unguard'] ?? true) {
            Model::unguard();
        }

        // Eager Loading
        if ($this->config['eager_loading'] ?? true) {
            Model::automaticallyEagerLoadRelationships();
        }

        // Strict Mode
        $this->configureStrictMode();

        // Sentry Violations
        if ($this->config['sentry_violations'] ?? true) {
            $this->configureSentryViolations();
        }
    }

    protected function configureStrictMode(): void
    {
        $mode = $this->config['strict_mode'] ?? 'production';

        $shouldBeStrict = match ($mode) {
            'always' => true,
            'never' => false,
            'production' => app()->isProduction(),
            default => app()->isProduction(),
        };

        if (! $shouldBeStrict) {
            return;
        }

        Model::shouldBeStrict();
    }

    protected function configureSentryViolations(): void
    {
        if (! app()->isProduction() || ! class_exists(Integration::class)) {
            return;
        }

        Model::handleLazyLoadingViolationUsing(
            Integration::lazyLoadingViolationReporter()
        );

        Model::handleDiscardedAttributeViolationUsing(
            Integration::discardedAttributeViolationReporter()
        );

        Model::handleMissingAttributeViolationUsing(
            Integration::missingAttributeViolationReporter()
        );
    }
}
```

```php
// src/Configurators/DatabaseConfigurator.php
namespace JuniorFontenele\LaravelAppBootstrap\Configurators;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class DatabaseConfigurator
{
    public function __construct(
        protected array $config
    ) {}

    public function configure(): void
    {
        // Default string length
        if ($length = $this->config['default_string_length'] ?? 255) {
            Schema::defaultStringLength($length);
        }

        // Blueprint Macro para charset/collation padrão
        $this->registerBlueprintMacro();

        // Prohibit destructive commands
        $this->configureDestructiveCommands();
    }

    protected function registerBlueprintMacro(): void
    {
        $charset = $this->config['default_charset'] ?? 'utf8mb4';
        $collation = $this->config['default_collation'] ?? 'utf8mb4_0900_ai_ci';

        Blueprint::macro('defaultCharset', function () use ($charset, $collation) {
            /** @var Blueprint $this */
            $this->charset = $charset;
            $this->collation = $collation;

            return $this;
        });
    }

    protected function configureDestructiveCommands(): void
    {
        $mode = $this->config['prohibit_destructive_commands'] ?? 'production';

        $shouldProhibit = match ($mode) {
            'always' => true,
            'never' => false,
            'production' => app()->isProduction(),
            default => app()->isProduction(),
        };

        if ($shouldProhibit) {
            DB::prohibitDestructiveCommands();
        }
    }
}
```

```php
// src/Configurators/DateConfigurator.php
namespace JuniorFontenele\LaravelAppBootstrap\Configurators;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

class DateConfigurator
{
    public function __construct(
        protected array $config
    ) {}

    public function configure(): void
    {
        // Use immutable dates
        if ($this->config['use_immutable'] ?? true) {
            Date::use(CarbonImmutable::class);
        }

        // Default timezone é configurado no config/app.php do Laravel
        // Users timezone é usado pelo app quando necessário
    }
}
```

```php
// src/Configurators/HttpConfigurator.php
namespace JuniorFontenele\LaravelAppBootstrap\Configurators;

use Illuminate\Support\Facades\URL;

class HttpConfigurator
{
    public function __construct(
        protected array $config
    ) {}

    public function configure(): void
    {
        $forceHttps = $this->config['force_https'] ?? null;

        $shouldForce = match ($forceHttps) {
            true => true,
            false => false,
            null => $this->autoDetect(),
            default => $this->autoDetect(),
        };

        if ($shouldForce) {
            URL::forceScheme('https');
        }
    }

    protected function autoDetect(): bool
    {
        // Em produção, sempre força
        if (app()->isProduction()) {
            return true;
        }

        // Em outros ambientes, detecta do request
        if (request() && request()->isSecure()) {
            return true;
        }

        return false;
    }
}
```

```php
// src/Configurators/PasswordConfigurator.php
namespace JuniorFontenele\LaravelAppBootstrap\Configurators;

use Illuminate\Validation\Rules\Password;

class PasswordConfigurator
{
    public function __construct(
        protected array $config
    ) {}

    public function configure(): void
    {
        if (! ($this->config['enabled'] ?? true)) {
            return;
        }

        Password::defaults(function () {
            // Sem requisitos em ambientes de desenvolvimento
            if (! $this->shouldEnforce()) {
                return null;
            }

            $password = Password::min($this->config['min_length'] ?? 8);

            if ($this->config['require_mixed_case'] ?? true) {
                $password->mixedCase();
            }

            if ($this->config['require_numbers'] ?? true) {
                $password->numbers();
            }

            if ($this->config['require_symbols'] ?? true) {
                $password->symbols();
            }

            if ($this->config['require_uncompromised'] ?? true) {
                $password->uncompromised();
            }

            return $password;
        });
    }

    protected function shouldEnforce(): bool
    {
        $environments = $this->config['environments'] ?? ['production', 'staging'];

        return in_array(app()->environment(), $environments);
    }
}
```

### 2. Service Provider

```php
// src/AppBootstrapServiceProvider.php
namespace JuniorFontenele\LaravelAppBootstrap;

use Illuminate\Support\ServiceProvider;
use JuniorFontenele\LaravelAppBootstrap\Configurators\{
    ModelConfigurator,
    DatabaseConfigurator,
    DateConfigurator,
    HttpConfigurator,
    PasswordConfigurator
};

class AppBootstrapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/app-bootstrap.php',
            'app-bootstrap'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/app-bootstrap.php' => config_path('app-bootstrap.php'),
            ], 'app-bootstrap-config');
        }

        $this->configureApp();
    }

    protected function configureApp(): void
    {
        $config = config('app-bootstrap', []);

        // Models
        (new ModelConfigurator($config['models'] ?? []))->configure();

        // Database
        (new DatabaseConfigurator($config['database'] ?? []))->configure();

        // Dates
        (new DateConfigurator($config['dates'] ?? []))->configure();

        // HTTP
        (new HttpConfigurator($config['http'] ?? []))->configure();

        // Passwords
        (new PasswordConfigurator($config['passwords'] ?? []))->configure();
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
        "juniorfontenele/laravel-app-bootstrap": "^1.0"
    }
}
```

### 2. Uso Automático

O pacote se auto-registra e configura tudo automaticamente. **Não precisa de nenhuma configuração adicional.**

### 3. AppServiceProvider Simplificado

```php
// app/Providers/AppServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Suas configurações específicas aqui
    }

    public function boot(): void
    {
        // Bootstrap já configurado pelo pacote!
        // Adicione apenas customizações específicas do projeto
    }
}
```

### 4. Customização (opcional)

```bash
php artisan vendor:publish --tag=app-bootstrap-config
```

Editar `config/app-bootstrap.php` conforme necessidade.

---

## Exemplos de Uso

### 1. Usando Defaults

```php
// Sem configuração, já funciona com valores sensatos:
// - Models unguarded
// - Eager loading automático
// - Strict mode em produção
// - String length 255
// - HTTPS forçado em produção
// - Passwords fortes em produção
```

### 2. Customizando Models

```php
// config/app-bootstrap.php
return [
    'models' => [
        'unguard' => false, // Mantém guard ativo
        'strict_mode' => 'always', // Sempre strict
    ],
];
```

### 3. Customizando Database

```php
// config/app-bootstrap.php
return [
    'database' => [
        'default_string_length' => 191, // Para MySQL antigo
        'default_charset' => 'utf8mb4',
        'default_collation' => 'utf8mb4_unicode_ci',
    ],
];

// Uso em migrations:
Schema::create('users', function (Blueprint $table) {
    $table->defaultCharset(); // Aplica charset/collation configurados
    $table->id();
    // ...
});
```

### 4. Customizando Passwords

```php
// config/app-bootstrap.php
return [
    'passwords' => [
        'min_length' => 12,
        'require_symbols' => false,
        'environments' => ['production'], // Apenas em produção
    ],
];
```

### 5. Desabilitando Features

```php
// .env
MODELS_UNGUARD=false
MODELS_STRICT_MODE=never
PASSWORD_VALIDATION_ENABLED=false
```

---

## Testes

```php
use JuniorFontenele\LaravelAppBootstrap\Configurators\ModelConfigurator;
use Illuminate\Database\Eloquent\Model;

it('unguards models when configured', function () {
    $configurator = new ModelConfigurator(['unguard' => true]);
    $configurator->configure();
    
    expect(Model::isUnguarded())->toBeTrue();
});

it('enables strict mode in production', function () {
    app()->detectEnvironment(fn() => 'production');
    
    $configurator = new ModelConfigurator(['strict_mode' => 'production']);
    $configurator->configure();
    
    // Teste que strict mode está ativo
});
```

---

## Antes vs Depois

### Antes (Manual no AppServiceProvider)

```php
// app/Providers/AppServiceProvider.php
class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 50+ linhas de configuração
        Model::unguard();
        Model::automaticallyEagerLoadRelationships();
        
        if (app()->isProduction()) {
            Model::handleLazyLoadingViolationUsing(...);
            Model::handleDiscardedAttributeViolationUsing(...);
            Model::handleMissingAttributeViolationUsing(...);
        }
        
        Schema::defaultStringLength(255);
        
        Blueprint::macro('defaultCharset', function () {
            $this->charset = 'utf8mb4';
            $this->collation = 'utf8mb4_0900_ai_ci';
            return $this;
        });
        
        DB::prohibitDestructiveCommands(app()->isProduction());
        
        Date::use(CarbonImmutable::class);
        
        if (config('app.force_https', ! app()->isLocal())) {
            URL::forceScheme('https');
        }
        
        Password::defaults(function () {
            if (! app()->isProduction()) {
                return null;
            }
            
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });
    }
}
```

### Depois (Com o Pacote)

```php
// app/Providers/AppServiceProvider.php
class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Pacote cuida de tudo! 🎉
        // Adicione apenas customizações específicas do projeto
    }
}
```

---

## Métricas de Sucesso

- ✅ Reduz AppServiceProvider de 50+ linhas para ~5 linhas
- ✅ Zero configuração necessária (funciona out of the box)
- ✅ Fácil customização quando necessário
- ✅ Configurações sensatas por padrão
- ✅ Documentação clara do que cada config faz
