<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Comando mestre que executa todos os crons configurados no painel
        $schedule->command('cron:master')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Limpeza de tokens expirados (backup caso o cron master falhe)
        $schedule->command('model:prune', ['--model' => [\App\Models\AutoLoginToken::class]])
                 ->daily()
                 ->at('04:00');

        // Limpeza de logs antigos
        $schedule->command('queue:prune-batches', ['--hours' => 48])
                 ->daily()
                 ->at('05:00');

        // Limpeza de jobs falhados antigos
        $schedule->command('queue:prune-failed', ['--hours' => 168]) // 7 dias
                 ->weekly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}