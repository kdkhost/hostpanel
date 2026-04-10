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