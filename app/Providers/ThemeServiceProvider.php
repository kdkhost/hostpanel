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
        // Protegido contra erros de DB durante instalação
        $this->app->booted(function () {
            try {
                $this->app->make(ThemeManager::class)->boot();
            } catch (\Throwable $e) {
                // Silenciar erros durante instalação (sem banco de dados)
            }
        });
    }
}
