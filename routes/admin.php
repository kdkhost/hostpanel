<?php

use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

// Rotas de configurações
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('index');
    Route::post('/update', [SettingController::class, 'update'])->name('update');
    
    // Cron management
    Route::get('/cron', [SettingController::class, 'cron'])->name('cron');
    Route::post('/cron/update', [SettingController::class, 'cronUpdate'])->name('cron.update');
    Route::post('/cron/run', [SettingController::class, 'cronRunTask'])->name('cron.run');
});

// Webhook routes (fora do middleware de auth)
Route::prefix('webhook')->name('webhook.')->middleware(['api', 'throttle:60,1'])->group(function () {
    Route::post('/gateway/{gateway}', [App\Http\Controllers\Webhook\GatewayWebhookController::class, 'handle'])
          ->middleware(['validate.gateway.webhook'])
          ->name('gateway');
});