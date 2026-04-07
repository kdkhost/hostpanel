<?php

namespace App\Providers;

use App\Services\ThemeManager;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class, fn() => new ThemeManager());
    }

    public function boot(): void
    {
        // Ativa o tema após todas as views serem carregadas
        $this->app->booted(function () {
            $this->app->make(ThemeManager::class)->boot();
        });
    }
}
