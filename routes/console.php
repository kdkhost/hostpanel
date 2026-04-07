<?php

use App\Jobs\GenerateInvoicesJob;
use App\Jobs\ServerHealthCheckJob;
use App\Jobs\SuspendOverdueServicesJob;
use App\Services\BillingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| HostPanel Scheduled Tasks
|--------------------------------------------------------------------------
*/
Schedule::job(new GenerateInvoicesJob)->dailyAt('08:00')->name('generate-invoices')->withoutOverlapping();
Schedule::job(new SuspendOverdueServicesJob)->dailyAt('09:00')->name('suspend-overdue')->withoutOverlapping();
Schedule::job(new ServerHealthCheckJob)->everyFiveMinutes()->name('server-health-check')->withoutOverlapping();

// Aplicar multas e juros em faturas vencidas
Schedule::call(function () {
    \App\Models\Invoice::where('status', 'pending')
        ->where('date_due', '<', now()->toDateString())
        ->update(['status' => 'overdue']);

    \App\Models\Invoice::where('status', 'overdue')->each(function ($invoice) {
        app(BillingService::class)->applyLateFees($invoice);
    });
})->dailyAt('00:30')->name('apply-late-fees');

// Limpar logs antigos (> 90 dias)
Schedule::call(function () {
    \App\Models\LoginLog::where('created_at', '<', now()->subDays(90))->delete();
    \App\Models\NotificationLog::where('created_at', '<', now()->subDays(90))->delete();
    \App\Models\GatewayLog::where('created_at', '<', now()->subDays(90))->delete();
})->weekly()->name('clean-old-logs');

// Purgar tokens de auto login expirados (> 30 dias após expiração)
Schedule::call(function () {
    app(\App\Services\AutoLoginService::class)->purgeExpired();
})->dailyAt('03:00')->name('purge-autologin-tokens');

// Alertas de domínios/serviços expirando em 30 dias
Schedule::call(function () {
    \App\Models\Service::with('client')
        ->where('status', 'active')
        ->whereDate('next_due_date', now()->addDays(7)->toDateString())
        ->get()
        ->each(fn($service) =>
            app(\App\Services\NotificationService::class)->send($service->client, 'service_expiring', [
                'name'       => $service->client->name,
                'product'    => $service->product_name,
                'due_date'   => $service->next_due_date->format('d/m/Y'),
                'action_url' => url('/cliente/servicos/' . $service->id),
                'message'    => "Seu serviço {$service->product_name} vence em 7 dias.",
            ])
        );
})->dailyAt('10:00')->name('service-expiry-alerts');
