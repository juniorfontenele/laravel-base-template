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

use Sentry\Laravel\Integration;

use Sentry\State\Scope;

class AppServiceProvider extends ServiceProvider
{
    private array $context = [];

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
        $this->setupContext();

        $this->setupHttpRequestScheme();
        $this->setupModelsSettings();
        $this->setupDatabaseSettings();
        $this->setupCommandsSettings();
        $this->setupDatesSettings();
        $this->setupPasswordRequirements();
    }

    private function setupHttpRequestScheme(): void
    {
        if (config('app.force_https', ! app()->isLocal())) {
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

        if (app()->isProduction()) {
            Model::handleLazyLoadingViolationUsing(Integration::lazyLoadingViolationReporter());
            Model::handleDiscardedAttributeViolationUsing(Integration::discardedAttributeViolationReporter());
            Model::handleMissingAttributeViolationUsing(Integration::missingAttributeViolationReporter());
        }
    }

    private function setupContext(): void
    {
        $this->context = [
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
        ];

        if (! app()->runningInConsole()) {
            $this->context['request'] = [
                'ip' => request()->ip(),
                'method' => request()->method(),
                'url' => request()->fullUrl(),
                'host' => request()->getHost(),
                'scheme' => request()->getScheme(),
                'locale' => request()->getLocale(),
                'referer' => request()->header('referer'),
                'user_agent' => request()->userAgent(),
                'accept_language' => request()->header('accept-language'),
            ];

            if (Auth::check()) {
                $this->context['user'] = [
                    'id' => Auth::id(),
                    'name' => Auth::user()?->name,
                    'email' => Auth::user()?->email,
                ];
            }
        }

        $this->setupDefaultLogContext();
        $this->setupSentryContext();
    }

    private function setupDefaultLogContext(): void
    {
        Log::shareContext([
            'timestamp' => $this->context['timestamp'],
            'app' => [
                'name' => $this->context['app']['name'],
                'role' => $this->context['app']['role'],
                'env' => $this->context['app']['env'],
                'debug' => $this->context['app']['debug'],
                'version' => $this->context['app']['version'],
                'commit' => $this->context['app']['commit'],
                'build_date' => $this->context['app']['build_date'],
                'locale' => $this->context['app']['locale'],
                'timezone' => $this->context['app']['timezone'],
                'type' => $this->context['app']['type'],
            ],
            'host' => [
                'name' => $this->context['host']['name'],
                'ip' => $this->context['host']['ip'],
            ],
        ]);

        if (! app()->runningInConsole()) {
            Log::shareContext([
                'request' => [
                    'ip' => $this->context['request']['ip'],
                    'method' => $this->context['request']['method'],
                    'url' => $this->context['request']['url'],
                    'host' => $this->context['request']['host'],
                    'scheme' => $this->context['request']['scheme'],
                    'locale' => $this->context['request']['locale'],
                    'referer' => $this->context['request']['referer'],
                    'user_agent' => $this->context['request']['user_agent'],
                    'accept_language' => $this->context['request']['accept_language'],
                ],
            ]);

            if (Auth::check()) {
                Log::shareContext([
                    'user' => [
                        'id' => $this->context['user']['id'],
                        'name' => $this->context['user']['name'],
                        'email' => $this->context['user']['email'],
                    ],
                ]);
            }
        }
    }

    private function setupSentryContext(): void
    {
        configureScope(function (Scope $scope) {
            $scope->setContext('Application', [
                'Timestamp' => $this->context['timestamp'],
                'Name' => $this->context['app']['name'],
                'Role' => $this->context['app']['role'],
                'Environment' => $this->context['app']['env'],
                'Debug' => $this->context['app']['debug'],
                'Version' => $this->context['app']['version'],
                'Commit' => $this->context['app']['commit'],
                'Build Date' => $this->context['app']['build_date'],
                'Locale' => $this->context['app']['locale'],
                'Timezone' => $this->context['app']['timezone'],
                'Type' => $this->context['app']['type'],
            ]);

            $scope->setContext('Host', [
                'Name' => $this->context['host']['name'],
                'IP' => $this->context['host']['ip'],
            ]);

            $scope->setTag('app.version', $this->context['app']['version']);
            $scope->setTag('app.commit', $this->context['app']['commit']);
            $scope->setTag('app.build_date', $this->context['app']['build_date']);
            $scope->setTag('app.role', $this->context['app']['role']);
            $scope->setTag('type', $this->context['app']['type']);
        });
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

    private function registerCustomClasses(): void
    {
        //
    }
}
