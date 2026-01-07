<?php

declare(strict_types = 1);

namespace App\Providers;

use Carbon\CarbonImmutable;
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

use function Sentry\configureScope;

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
        $this->setupHttpRequestScheme();
        $this->setupModelsSettings();
        $this->setupDefaultLogContext();
        $this->setupDatabaseSettings();
        $this->setupCommandsSettings();
        $this->setupDatesSettings();
        $this->setupPasswordRequirements();
    }

    private function setupHttpRequestScheme(): void
    {
        if (config('app.force_https', !app()->isLocal())) {
            URL::forceScheme('https');

            return;
        }

        if (request()->isSecure()) {
            URL::forceScheme('https');
        }
    }

    private function setupModelsSettings(): void
    {
        Model::unguard();
        Model::automaticallyEagerLoadRelationships();
    }

    private function setupDefaultLogContext(): void
    {
        Log::shareContext([
            'timestamp' => now()->toIso8601ZuluString(),
            'app' => [
                'name' => config('app.name'),
                'role' => config('app.role', 'app'),
                'env' => config('app.env'),
                'debug' => config('app.debug'),
                'version' => config('app.version'),
                'commit' => config('app.commit'),
                'build_date' => config('app.build_date'),
                'locale' => app()->getLocale(),
                'timezone' => config('app.timezone'),
                'type' => app()->runningInConsole() ? 'console' : 'http',
            ],
            'host' => [
                'name' => gethostname() ?: null,
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

    private function setupDatabaseSettings(): void
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

    private function setupCommandsSettings(): void
    {
        DB::prohibitDestructiveCommands(app()->isProduction());
    }

    private function setupDatesSettings(): void
    {
        Date::use(CarbonImmutable::class);
    }

    private function setupPasswordRequirements(): void
    {
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

    private function setupSentryContext(): void
    {
        configureScope(function (Scope $scope) {
            $scope->setContext('Application', [
                'Timestamp' => now()->toIso8601ZuluString(),
                'Name' => config('app.name'),
                'Role' => config('app.role', 'app'),
                'Environment' => config('app.env'),
                'Debug' => config('app.debug'),
                'Version' => config('app.version'),
                'Commit' => config('app.commit'),
                'Build Date' => config('app.build_date'),
                'Locale' => app()->getLocale(),
                'Timezone' => config('app.timezone'),
                'Type' => app()->runningInConsole() ? 'console' : 'http',
            ]);

            $scope->setContext('Host', [
                'Name' => gethostname() ?: null,
                'IP' => gethostname() ? gethostbyname(gethostname()) : null,
            ]);

            $scope->setTag('app.version', config('app.version'));
            $scope->setTag('app.commit', config('app.commit'));
            $scope->setTag('app.build_date', config('app.build_date'));
            $scope->setTag('app.role', config('app.role', 'app'));
            $scope->setTag('type', app()->runningInConsole() ? 'console' : 'http');
        });
    }

    private function registerCustomClasses(): void
    {
        //
    }
}
