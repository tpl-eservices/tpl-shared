<?php

namespace Tpl\Shared;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Tpl\Shared\Auth\BiblioGuard;
use Tpl\Shared\Auth\BiblioUserProvider;
use Tpl\Shared\Console\Commands\ClearBiblioCommonsCache;
use Tpl\Shared\Console\Commands\DiagnoseBiblioCommons;
use Tpl\Shared\Console\Commands\InstallTplShared;
use Tpl\Shared\Console\Commands\TplSharedBuild;
use Tpl\Shared\Console\Commands\UninstallTplShared;
use Tpl\Shared\Services\BiblioCommonsService;
use Tpl\Shared\Services\BiblioCommonsTemplateService;
use Tpl\Shared\Services\BiblioSsoService;
use Tpl\Shared\Services\DXServicesService;
use Tpl\Shared\Services\FakeBiblioSsoService;
use Tpl\Shared\View\Composers\BiblioCommonsComposer;

class SharedServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config if present
        $this->mergeConfigFrom(__DIR__.'/../config/shared.php', 'shared');

        // Register BiblioCommons API service as singleton
        $this->app->singleton(BiblioCommonsService::class);

        // Register BiblioCommons service as singleton
        $this->app->singleton(BiblioCommonsTemplateService::class);

        // Register BiblioCommons SSO service as singleton
        // Use fake service in mock mode for local development/testing
        $this->app->singleton(BiblioSsoService::class, function ($app) {
            if ($this->shouldUseMockBiblioCommons()) {
                Log::warning('[TPL-SHARED] BiblioCommons mock mode enabled - using FakeBiblioSsoService');

                return new FakeBiblioSsoService;
            }

            return new BiblioSsoService;
        });

        // Register DXServices service as singleton
        $this->app->singleton(DXServicesService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register BiblioCommons authentication provider
        Auth::provider('biblio', fn ($app, array $config) => new BiblioUserProvider(
            $app->make(BiblioSsoService::class),
            $config['model']
        ));

        // Register BiblioCommons authentication guard
        Auth::extend('biblio', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider']);

            if ($provider === null) {
                throw new \RuntimeException("Unable to create user provider [{$config['provider']}] for BiblioCommons guard.");
            }

            return new BiblioGuard(
                $provider,
                $app->make('request'),
                $app->make(BiblioSsoService::class)
            );
        });

        // Routes, views, migrations
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tpl-shared');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register Blade components with namespace prefix
        Blade::componentNamespace('Tpl\\Shared\\View\\Components', 'tpl-shared');

        // Register BiblioCommons view composer for layout components
        View::composer([
            'tpl-shared::components.layout',
            'tpl-shared::components.static-layout',
            'tpl-shared::components.inertia-layout',
        ], BiblioCommonsComposer::class);

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ClearBiblioCommonsCache::class,
                DiagnoseBiblioCommons::class,
                InstallTplShared::class,
                TplSharedBuild::class,
                UninstallTplShared::class,
            ]);
        }

        // Publish frontend assets (JS/CSS)
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/tpl-shared'),
            __DIR__.'/../resources/js' => resource_path('vendor/tpl-shared/js'),
            __DIR__.'/../resources/css' => resource_path('vendor/tpl-shared/css'),
        ]);

        $this->publishes([
            __DIR__.'/../public/build' => public_path('vendor/tpl-shared/build'),
        ], 'tpl-shared-assets');

        // Publish migrations
        //    $this->publishesMigrations([
        //      __DIR__.'/../database/migrations' => database_path('migrations'),
        //    ]);
    }

    /**
     * Determine if BiblioCommons mock mode should be used.
     *
     * Security: Requires ALL conditions to be met:
     * 1. Explicitly enabled via config (BIBLIOCOMMONS_MOCK_ENABLED=true)
     * 2. Environment is local, testing, or staging
     */
    protected function shouldUseMockBiblioCommons(): bool
    {
        // Layer 1: Explicit opt-in required
        if (! config('services.bibliocommons.mock_enabled', false)) {
            return false;
        }

        // Layer 2: Environment check - only allow in local/testing/staging
        if (! $this->app->environment(['local', 'testing', 'staging'])) {
            Log::critical('[TPL-SHARED SECURITY] BiblioCommons mock mode attempted in non-dev environment - BLOCKED');

            return false;
        }

        return true;
    }
}
