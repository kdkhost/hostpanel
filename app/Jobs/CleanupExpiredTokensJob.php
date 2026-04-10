<?php

namespace App\Jobs;

use App\Models\AutoLoginToken;
use App\Models\ApiToken;
use App\Models\CartItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupExpiredTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $cleaned = 0;

        // Limpa tokens de auto login expirados
        $expiredAutoLogin = AutoLoginToken::where('expires_at', '<', now())->count();
        AutoLoginToken::where('expires_at', '<', now())->delete();
        $cleaned += $expiredAutoLogin;

        // Limpa tokens de API expirados (se tiver campo expires_at)
        if (\Schema::hasColumn('api_tokens', 'expires_at')) {
            $expiredApiTokens = ApiToken::where('expires_at', '<', now())->count();
            ApiToken::where('expires_at', '<', now())->delete();
            $cleaned += $expiredApiTokens;
        }

        // Limpa itens do carrinho antigos (mais de 7 dias)
        $expiredCartItems = CartItem::where('created_at', '<', now()->subDays(7))->count();
        CartItem::where('created_at', '<', now()->subDays(7))->delete();
        $cleaned += $expiredCartItems;

        // Limpa logs antigos (mais de 90 dias)
        if (\Schema::hasTable('gateway_logs')) {
            $oldGatewayLogs = \DB::table('gateway_logs')->where('created_at', '<', now()->subDays(90))->count();
            \DB::table('gateway_logs')->where('created_at', '<', now()->subDays(90))->delete();
            $cleaned += $oldGatewayLogs;
        }

        if (\Schema::hasTable('login_logs')) {
            $oldLoginLogs = \DB::table('login_logs')->where('created_at', '<', now()->subDays(90))->count();
            \DB::table('login_logs')->where('created_at', '<', now()->subDays(90))->delete();
            $cleaned += $oldLoginLogs;
        }

        if (\Schema::hasTable('notification_logs')) {
            $oldNotificationLogs = \DB::table('notification_logs')->where('created_at', '<', now()->subDays(30))->count();
            \DB::table('notification_logs')->where('created_at', '<', now()->subDays(30))->delete();
            $cleaned += $oldNotificationLogs;
        }

        Log::info("CleanupExpiredTokensJob: {$cleaned} record(s) cleaned up.");
    }
}