<?php

namespace App\Providers;

use App\Services\ThemeManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Rate Limiter — Email
         * Máximo 60 emails por minuto (evita blacklist de IP/domínio).
         * Backoff automático quando o limite é atingido.
         */
        RateLimiter::for('email', fn() => Limit::perMinute(60));

        /*
         * Rate Limiter — WhatsApp
         * Máximo 20 mensagens por hora para evitar banimento do número.
         * O SendWhatsAppJob ainda aplica delay aleatório adicional entre envios.
         */
        RateLimiter::for('whatsapp', fn() => Limit::perHour(20));

        /**
         * @themeAsset('css/theme.css')
         * Gera a URL correta para um asset do tema ativo.
         * Se o tema for 'default', usa asset() normal.
         */
        Blade::directive('themeAsset', function (string $expression) {
            return "<?php echo app(\App\Services\ThemeManager::class)->assetUrl({$expression}); ?>";
        });

        /**
         * @themeColor('primary')
         * Retorna a cor configurada no theme.json para a chave informada.
         */
        Blade::directive('themeColor', function (string $expression) {
            return "<?php echo data_get(app(\App\Services\ThemeManager::class)->getManifest(app(\App\Services\ThemeManager::class)->getActive()), 'colors.' . {$expression}, '#4f46e5'); ?>";
        });

        /**
         * @themeVar('fonts.heading', 'Inter')
         * Retorna qualquer variável do theme.json com fallback.
         */
        Blade::directive('themeVar', function (string $expression) {
            $parts = explode(',', $expression, 2);
            $key   = trim($parts[0]);
            $fallback = isset($parts[1]) ? trim($parts[1]) : "''";
            return "<?php echo data_get(app(\App\Services\ThemeManager::class)->getManifest(app(\App\Services\ThemeManager::class)->getActive()), {$key}, {$fallback}); ?>";
        });
    }
}
