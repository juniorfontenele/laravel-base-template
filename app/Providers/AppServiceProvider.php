<?php

declare(strict_types = 1);

namespace App\Providers;

use App\Services\VersionService;
use Carbon\CarbonImmutable;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Opcodes\LogViewer\Facades\LogViewer;

use function Sentry\configureScope;

use Sentry\Laravel\Integration;

use Sentry\State\Scope;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerCustomClasses();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->setupLogviewer();
        $this->setupDatabaseSettings();
        $this->setupModelsSettings();
        $this->setupCommandsSettings();
        $this->setupUrlsSettings();
        $this->setupDatesSettings();
        $this->setupPasswordRequirements();
        $this->setupApplicationVersion();
        $this->setupDefaultLogContext();
        $this->setupSentryContext();
    }

    protected function setupDefaultLogContext(): void
    {
        Log::shareContext([
            'timestamp' => now()->toIso8601ZuluString(),
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'debug' => config('app.debug'),
                'version' => config('app.version'),
                'locale' => app()->getLocale(),
                'timezone' => config('app.timezone'),
                'type' => app()->runningInConsole() ? 'console' : 'http',
            ],
            'container' => [
                'role' => config('container.role', 'app'),
                'host' => gethostname() ?: null,
                'ip' => gethostname() ? gethostbyname(gethostname()) : null,
            ],
        ]);

        if (! app()->runningInConsole()) {
            Log::shareContext([
                'request' => [
                    'ip' => request()->ip(),
                    'method' => request()->method(),
                    'url' => request()->fullUrl(),
                    'host' => request()->getHost(),
                    'scheme' => request()->getScheme(),
                    'locale' => request()->getLocale(),
                    'referer' => request()->header('referer'),
                    'user_agent' => request()->userAgent(),
                    'accept_language' => request()->header('accept-language'),
                ],
            ]);

            if (Auth::check()) {
                Log::shareContext([
                    'user' => [
                        'id' => Auth::id(),
                        'name' => Auth::user()?->name,
                        'email' => Auth::user()?->email,
                    ],
                ]);
            }
        }
    }

    protected function setupLogviewer(): void
    {
        LogViewer::auth(function () {
            return app()->environment('local');
        });
    }

    protected function setupDatabaseSettings(): void
    {
        Schema::defaultStringLength(255);

        Blueprint::macro('defaultCharset', function () {
            /**
             * @var Blueprint $this
             */
            $this->charset = 'utf8mb4';
            $this->collation = 'utf8mb4_0900_ai_ci';

            return $this;
        });
    }

    protected function setupModelsSettings(): void
    {
        Model::automaticallyEagerLoadRelationships();

        if (app()->isProduction()) {
            Model::handleLazyLoadingViolationUsing(Integration::lazyLoadingViolationReporter());
            Model::handleDiscardedAttributeViolationUsing(Integration::discardedAttributeViolationReporter());
            Model::handleMissingAttributeViolationUsing(Integration::missingAttributeViolationReporter());
        }
    }

    protected function setupCommandsSettings(): void
    {
        DB::prohibitDestructiveCommands(app()->isProduction());
    }

    protected function setupUrlsSettings(): void
    {
        if (! app()->isLocal()) {
            URL::forceScheme('https');
        }
    }

    protected function setupDatesSettings(): void
    {
        Date::use(CarbonImmutable::class);
    }

    protected function setupPasswordRequirements(): void
    {
        Password::defaults(function () {
            if (! app()->isProduction()) {
                return null;
            }

            return Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });
    }

    protected function setupApplicationVersion(): void
    {
        $version = VersionService::version();
        $config = app()->make(Repository::class);
        $config->set('app.version', $version);
    }

    protected function setupSentryContext(): void
    {
        configureScope(function (Scope $scope) {
            $scope->setContext('Application', [
                'Timestamp' => now()->toIso8601ZuluString(),
                'Name' => config('app.name'),
                'Environment' => config('app.env'),
                'Debug' => config('app.debug'),
                'Version' => config('app.version'),
                'Locale' => app()->getLocale(),
                'Timezone' => config('app.timezone'),
                'Type' => app()->runningInConsole() ? 'console' : 'http',
            ]);

            $scope->setContext('Container', [
                'Role' => config('container.role', 'app'),
                'Host' => gethostname() ?: null,
                'IP' => gethostname() ? gethostbyname(gethostname()) : null,
            ]);

            $scope->setTag('app.version', config('app.version'));
            $scope->setTag('type', app()->runningInConsole() ? 'console' : 'http');
        });
    }

    protected function registerCustomClasses(): void
    {
        // Register custom classes binding
    }
}
