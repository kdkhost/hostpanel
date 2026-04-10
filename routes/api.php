<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1;

/*
|--------------------------------------------------------------------------
| API v1 — Autenticação por token (Sanctum/api_tokens customizado)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->name('api.v1.')->group(function () {

    // Auth pública
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login',    [V1\AuthController::class, 'login'])->name('login');
        Route::post('/register', [V1\AuthController::class, 'register'])->name('register');
    });

    // Endpoints protegidos
    Route::middleware('api.token')->group(function () {

        Route::post('/auth/logout', [V1\AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me',      [V1\AuthController::class, 'me'])->name('auth.me');

        // Clientes
        Route::apiResource('clients',  V1\ClientController::class);

        // Serviços
        Route::apiResource('services', V1\ServiceController::class)->only(['index','show']);
        Route::post('services/{service}/suspend',    [V1\ServiceController::class, 'suspend']);
        Route::post('services/{service}/reactivate', [V1\ServiceController::class, 'reactivate']);
        Route::post('services/{service}/terminate',  [V1\ServiceController::class, 'terminate']);

        // Pedidos
        Route::apiResource('orders', V1\OrderController::class)->only(['index','show','store']);

        // Faturas
        Route::apiResource('invoices', V1\InvoiceController::class)->only(['index','show']);
        Route::post('invoices/{invoice}/pay',    [V1\InvoiceController::class, 'pay']);
        Route::post('invoices/{invoice}/cancel', [V1\InvoiceController::class, 'cancel']);

        // Tickets
        Route::apiResource('tickets', V1\TicketController::class)->only(['index','show','store']);
        Route::post('tickets/{ticket}/reply', [V1\TicketController::class, 'reply']);
        Route::post('tickets/{ticket}/close', [V1\TicketController::class, 'close']);

        // Servidores (Desativado: Controller inexistente)
        // Route::apiResource('servers', V1\ServerController::class)->only(['index','show']);
        // Route::get('servers/{server}/health', [V1\ServerController::class, 'health']);

        // Notificações (Desativado: Controller inexistente)
        // Route::get('notifications',            [V1\NotificationController::class, 'index']);
        // Route::post('notifications/{id}/read', [V1\NotificationController::class, 'markRead']);

        // Domínios (Desativado: Controller inexistente)
        // Route::apiResource('domains', V1\DomainController::class)->only(['index','show']);

        // Produtos (Desativado: Controller inexistente)
        // Route::get('products',      [V1\ProductController::class, 'index']);
        // Route::get('products/{id}', [V1\ProductController::class, 'show']);

        // Webhooks
        Route::get('webhooks',  [V1\WebhookController::class, 'index']);
        Route::post('webhooks', [V1\WebhookController::class, 'store']);
        Route::delete('webhooks/{webhook}', [V1\WebhookController::class, 'destroy']);
    });
});
