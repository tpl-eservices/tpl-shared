<?php

namespace Tpl\Shared;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Tpl\Shared\Services\BiblioCommonsTemplateService;
use Tpl\Shared\View\Components\Layout;
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

        // Register Blade components with namespace prefix
        Blade::componentNamespace('Tpl\\Shared\\View\\Components', 'tpl-shared');

        // Register BiblioCommons view composer for layout components
        View::composer([
            'tpl-shared::components.layout',
            'tpl-shared::components.static-layout',
            'tpl-shared::components.inertia-layout',
        ], BiblioCommonsComposer::class);

        // Publish frontend assets (JS/CSS)
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/tpl-shared'),
            __DIR__.'/../resources/js' => resource_path('vendor/tpl-shared/js'),
            __DIR__.'/../resources/css' => resource_path('vendor/tpl-shared/css'),
        ]);

        $this->publishes([
            __DIR__.'/public/build' => public_path('vendor/tpl-shared/build'),
        ], 'tpl-shared-assets');

        // Publish migrations
        //    $this->publishesMigrations([
        //      __DIR__.'/../database/migrations' => database_path('migrations'),
        //    ]);
    }
}
