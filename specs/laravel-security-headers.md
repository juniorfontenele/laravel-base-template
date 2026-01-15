# Laravel Security Headers - Especificação Detalhada

## Visão Geral

Pacote simples para adicionar headers de segurança HTTP nas responses. Configuração flexível com presets prontos.

**Princípios:**
- ✅ Extremamente simples
- ✅ Presets para diferentes níveis de segurança
- ✅ Fácil customização
- ✅ Zero overhead

---

## Instalação

```bash
composer require juniorfontenele/laravel-security-headers
```

---

## Configuração

```php
// config/security-headers.php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    */
    'enabled' => env('SECURITY_HEADERS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Preset
    |--------------------------------------------------------------------------
    | Preset de segurança: 'basic', 'moderate', 'strict', 'custom'
    */
    'preset' => env('SECURITY_HEADERS_PRESET', 'moderate'),

    /*
    |--------------------------------------------------------------------------
    | Custom Headers
    |--------------------------------------------------------------------------
    | Headers customizados (usado quando preset = 'custom')
    */
    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    | Configuração do CSP (opcional)
    */
    'csp' => [
        'enabled' => env('SECURITY_HEADERS_CSP_ENABLED', false),
        'report_only' => env('SECURITY_HEADERS_CSP_REPORT_ONLY', false),
        
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'data:', 'https:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HSTS
    |--------------------------------------------------------------------------
    | HTTP Strict Transport Security
    */
    'hsts' => [
        'enabled' => env('SECURITY_HEADERS_HSTS_ENABLED', true),
        'max_age' => env('SECURITY_HEADERS_HSTS_MAX_AGE', 31536000), // 1 ano
        'include_sub_domains' => env('SECURITY_HEADERS_HSTS_SUBDOMAINS', true),
        'preload' => env('SECURITY_HEADERS_HSTS_PRELOAD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Environments
    |--------------------------------------------------------------------------
    | Ambientes onde os headers serão aplicados
    */
    'environments' => ['production', 'staging'],
];
```

---

## Estrutura do Pacote

```
src/
├── SecurityHeadersServiceProvider.php
├── Middleware/
│   └── AddSecurityHeaders.php
├── Presets/
│   ├── PresetInterface.php
│   ├── BasicPreset.php
│   ├── ModeratePreset.php
│   └── StrictPreset.php
└── Builders/
    ├── CspBuilder.php
    └── HstsBuilder.php
```

---

## Implementação

### 1. Presets

```php
// src/Presets/PresetInterface.php
namespace JuniorFontenele\LaravelSecurityHeaders\Presets;

interface PresetInterface
{
    public function headers(): array;
}
```

```php
// src/Presets/BasicPreset.php
namespace JuniorFontenele\LaravelSecurityHeaders\Presets;

class BasicPreset implements PresetInterface
{
    public function headers(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ];
    }
}
```

```php
// src/Presets/ModeratePreset.php
namespace JuniorFontenele\LaravelSecurityHeaders\Presets;

class ModeratePreset implements PresetInterface
{
    public function headers(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];
    }
}
```

```php
// src/Presets/StrictPreset.php
namespace JuniorFontenele\LaravelSecurityHeaders\Presets;

class StrictPreset implements PresetInterface
{
    public function headers(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'no-referrer',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=()',
        ];
    }
}
```

### 2. CSP Builder

```php
// src/Builders/CspBuilder.php
namespace JuniorFontenele\LaravelSecurityHeaders\Builders;

class CspBuilder
{
    public function __construct(
        protected array $directives = [],
        protected bool $reportOnly = false
    ) {}

    /**
     * Adiciona diretiva
     */
    public function addDirective(string $directive, array $sources): self
    {
        $this->directives[$directive] = $sources;
        
        return $this;
    }

    /**
     * Define como report-only
     */
    public function reportOnly(bool $reportOnly = true): self
    {
        $this->reportOnly = $reportOnly;
        
        return $this;
    }

    /**
     * Constrói o header CSP
     */
    public function build(): array
    {
        if (empty($this->directives)) {
            return [];
        }

        $policy = $this->buildPolicyString();
        $headerName = $this->reportOnly 
            ? 'Content-Security-Policy-Report-Only' 
            : 'Content-Security-Policy';

        return [$headerName => $policy];
    }

    /**
     * Constrói string da policy
     */
    protected function buildPolicyString(): string
    {
        $parts = [];

        foreach ($this->directives as $directive => $sources) {
            $parts[] = $directive . ' ' . implode(' ', $sources);
        }

        return implode('; ', $parts);
    }

    /**
     * Factory method a partir de config
     */
    public static function fromConfig(array $config): self
    {
        $builder = new self(
            $config['directives'] ?? [],
            $config['report_only'] ?? false
        );

        return $builder;
    }
}
```

### 3. HSTS Builder

```php
// src/Builders/HstsBuilder.php
namespace JuniorFontenele\LaravelSecurityHeaders\Builders;

class HstsBuilder
{
    public function __construct(
        protected int $maxAge = 31536000,
        protected bool $includeSubDomains = true,
        protected bool $preload = false
    ) {}

    /**
     * Define max-age
     */
    public function maxAge(int $seconds): self
    {
        $this->maxAge = $seconds;
        
        return $this;
    }

    /**
     * Inclui subdomínios
     */
    public function includeSubDomains(bool $include = true): self
    {
        $this->includeSubDomains = $include;
        
        return $this;
    }

    /**
     * Habilita preload
     */
    public function preload(bool $preload = true): self
    {
        $this->preload = $preload;
        
        return $this;
    }

    /**
     * Constrói o header HSTS
     */
    public function build(): array
    {
        $value = "max-age={$this->maxAge}";

        if ($this->includeSubDomains) {
            $value .= '; includeSubDomains';
        }

        if ($this->preload) {
            $value .= '; preload';
        }

        return ['Strict-Transport-Security' => $value];
    }

    /**
     * Factory method a partir de config
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            $config['max_age'] ?? 31536000,
            $config['include_sub_domains'] ?? true,
            $config['preload'] ?? false
        );
    }
}
```

### 4. Middleware

```php
// src/Middleware/AddSecurityHeaders.php
namespace JuniorFontenele\LaravelSecurityHeaders\Middleware;

use Closure;
use Illuminate\Http\Request;
use JuniorFontenele\LaravelSecurityHeaders\Builders\{CspBuilder, HstsBuilder};
use JuniorFontenele\LaravelSecurityHeaders\Presets\{BasicPreset, ModeratePreset, StrictPreset};
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('security-headers', []);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Verifica se está habilitado
        if (! $this->shouldAddHeaders()) {
            return $response;
        }

        // Adiciona headers do preset ou custom
        $headers = $this->getHeaders();
        
        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        // Adiciona CSP se habilitado
        if ($this->config['csp']['enabled'] ?? false) {
            $cspHeaders = CspBuilder::fromConfig($this->config['csp'])->build();
            
            foreach ($cspHeaders as $name => $value) {
                $response->headers->set($name, $value);
            }
        }

        // Adiciona HSTS se habilitado e HTTPS
        if ($request->secure() && ($this->config['hsts']['enabled'] ?? false)) {
            $hstsHeaders = HstsBuilder::fromConfig($this->config['hsts'])->build();
            
            foreach ($hstsHeaders as $name => $value) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }

    /**
     * Determina se deve adicionar headers
     */
    protected function shouldAddHeaders(): bool
    {
        if (! ($this->config['enabled'] ?? true)) {
            return false;
        }

        $environments = $this->config['environments'] ?? [];
        
        if (empty($environments)) {
            return true;
        }

        return in_array(app()->environment(), $environments);
    }

    /**
     * Retorna headers baseado no preset
     */
    protected function getHeaders(): array
    {
        $preset = $this->config['preset'] ?? 'moderate';

        return match ($preset) {
            'basic' => (new BasicPreset())->headers(),
            'moderate' => (new ModeratePreset())->headers(),
            'strict' => (new StrictPreset())->headers(),
            'custom' => $this->config['headers'] ?? [],
            default => (new ModeratePreset())->headers(),
        };
    }
}
```

### 5. Service Provider

```php
// src/SecurityHeadersServiceProvider.php
namespace JuniorFontenele\LaravelSecurityHeaders;

use Illuminate\Support\ServiceProvider;

class SecurityHeadersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/security-headers.php',
            'security-headers'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/security-headers.php' => config_path('security-headers.php'),
            ], 'security-headers-config');
        }
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
        "juniorfontenele/laravel-security-headers": "^1.0"
    }
}
```

### 2. Registro no Bootstrap

```php
// bootstrap/app.php
use JuniorFontenele\LaravelSecurityHeaders\Middleware\AddSecurityHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            AddSecurityHeaders::class,
        ]);
    })
    ->create();
```

### 3. Configuração (opcional)

```bash
php artisan vendor:publish --tag=security-headers-config
```

Editar `config/security-headers.php` conforme necessidade.

---

## Exemplos de Uso

### 1. Usando Preset Padrão (Moderate)

```php
// Sem configuração, usa moderate automaticamente
// Headers adicionados:
// X-Content-Type-Options: nosniff
// X-Frame-Options: SAMEORIGIN
// X-XSS-Protection: 1; mode=block
// Referrer-Policy: strict-origin-when-cross-origin
```

### 2. Mudando para Strict

```php
// .env
SECURITY_HEADERS_PRESET=strict

// Headers adicionados:
// X-Content-Type-Options: nosniff
// X-Frame-Options: DENY
// X-XSS-Protection: 1; mode=block
// Referrer-Policy: no-referrer
// Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()
```

### 3. Headers Customizados

```php
// config/security-headers.php
return [
    'preset' => 'custom',
    'headers' => [
        'X-Custom-Header' => 'value',
        'X-Frame-Options' => 'DENY',
    ],
];
```

### 4. Habilitando CSP

```php
// config/security-headers.php
return [
    'csp' => [
        'enabled' => true,
        'report_only' => false, // true para testar sem bloquear
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", 'cdn.example.com'],
            'style-src' => ["'self'", "'unsafe-inline'"],
        ],
    ],
];
```

### 5. Configurando HSTS

```php
// config/security-headers.php
return [
    'hsts' => [
        'enabled' => true,
        'max_age' => 63072000, // 2 anos
        'include_sub_domains' => true,
        'preload' => true, // Para submeter ao HSTS Preload List
    ],
];
```

---

## Testes

```php
use JuniorFontenele\LaravelSecurityHeaders\Middleware\AddSecurityHeaders;
use Illuminate\Http\Request;

it('adds security headers to response', function () {
    config(['security-headers.preset' => 'moderate']);
    
    $request = Request::create('/');
    $middleware = new AddSecurityHeaders();
    
    $response = $middleware->handle($request, fn() => response('OK'));
    
    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('does not add headers when disabled', function () {
    config(['security-headers.enabled' => false]);
    
    $request = Request::create('/');
    $middleware = new AddSecurityHeaders();
    
    $response = $middleware->handle($request, fn() => response('OK'));
    
    expect($response->headers->has('X-Frame-Options'))->toBeFalse();
});

it('adds HSTS only on HTTPS', function () {
    config([
        'security-headers.hsts.enabled' => true,
    ]);
    
    $request = Request::create('https://example.com');
    $middleware = new AddSecurityHeaders();
    
    $response = $middleware->handle($request, fn() => response('OK'));
    
    expect($response->headers->has('Strict-Transport-Security'))->toBeTrue();
});
```

---

## Presets Detalhados

### Basic
- Mínimo necessário
- Sem impacto em apps legadas
- Headers: `X-Content-Type-Options`, `X-Frame-Options`

### Moderate (Recomendado)
- Balanceado entre segurança e compatibilidade
- Adequado para maioria das aplicações
- Headers: Basic + `X-XSS-Protection`, `Referrer-Policy`

### Strict
- Máxima segurança
- Pode quebrar funcionalidades legadas
- Headers: Moderate + `Permissions-Policy` restritivo

---

## Métricas de Sucesso

- ✅ Setup em 1 linha de código
- ✅ Presets prontos para uso
- ✅ Zero configuração para começar
- ✅ Fácil customização quando necessário
- ✅ Performance (headers são strings simples)
