<?php

namespace Tpl\Shared;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Tpl\Shared\Services\BiblioCommonsTemplateService;
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

        // Register BiblioCommons service as singleton
        $this->app->singleton(BiblioCommonsTemplateService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Routes, views, migrations
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tpl-shared');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register BiblioCommons view composer for layout components
        View::composer([
            'tpl-shared::components.layout',
            'tpl-shared::components.static-layout',
        ], BiblioCommonsComposer::class);

        // Publish config
        $this->publishes([
            __DIR__.'/../config/shared.php' => config_path('shared.php'),
        ], ['tpl-shared-config', 'tpl-shared']);

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/tpl-shared'),
        ], ['tpl-shared-views', 'tpl-shared']);

        // Publish frontend assets (JS/CSS)
        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('vendor/tpl-shared/js'),
            __DIR__.'/../resources/css' => resource_path('vendor/tpl-shared/css'),
        ], ['tpl-shared-assets', 'tpl-shared']);

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['tpl-shared-migrations', 'tpl-shared']);

        // Publish public assets (images, fonts, etc.)
        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/tpl-shared'),
        ], ['tpl-shared-public', 'tpl-shared']);
    }
}
