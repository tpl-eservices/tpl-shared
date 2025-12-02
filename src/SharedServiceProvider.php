<?php

namespace Tpl\Shared;

use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config if present
        $this->mergeConfigFrom(__DIR__ . '/../config/shared.php', 'shared');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Routes, views, migrations
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tpl-shared');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Publish config and assets
        $this->publishes([
            __DIR__ . '/../config/shared.php' => config_path('shared.php'),
        ], 'tpl-shared-config');

        $this->publishes([
            __DIR__ . '/../resources/js' => resource_path('vendor/tpl-shared/js'),
            __DIR__ . '/../resources/css' => resource_path('vendor/tpl-shared/css'),
        ], 'tpl-shared-assets');
    }
}

