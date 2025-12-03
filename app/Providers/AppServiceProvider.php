<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register any application services.
    }

    public function boot(): void
    {
        // Boot any application services.
        // No global helper functions should be defined here.
    }
}
