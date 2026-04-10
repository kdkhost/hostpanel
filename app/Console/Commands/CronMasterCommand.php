<?php

namespace App\Console\Commands;

use App\Jobs\GenerateInvoicesJob;
use App\Jobs\SuspendOverdueServicesJob;
use App\Jobs\ServerHealthCheckJob;
use App\Jobs\ApplyLateFeesJob;
use App\Jobs\ProcessAffiliateCommissionsJob;
use App\Jobs\CleanupExpiredTokensJob;
use App\Jobs\SendDueRemindersJob;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CronMasterCommand extends Command
{
    protected $signature = 'cron:master';
    protected $description = 'Executa todas as tarefas cron configuradas no painel administrativo';

    public function handle(): int
    {
        // Atualiza heartbeat
        Setting::set('cron.last_heartbeat', now()->timestamp);
        
        $this->info('=== CRON MASTER INICIADO ===');
        $this->info('Timestamp: ' . now()->format('Y-m-d H:i:s'));
        
        // Carrega configurações de cron
        $cronConfig = $this->loadCronConfig();
        
        foreach ($cronConfig as $taskKey => $task) {
            if (!$task['enabled']) {
                continue;
            }
            
            if ($this->shouldRunTask($taskKey, $task)) {
                $this->runTask($taskKey, $task);
            }
        }
        
        $this->info('=== CRON MASTER FINALIZADO ===');
        return 0;
    }
    
    private function loadCronConfig(): array
    {
        return [
            'generate_invoices' => [
                'name' => 'Gerar Faturas',
                'job' => GenerateInvoicesJob::class,
                'schedule' => Setting::get('cron.generate_invoices.schedule', '0 8 * * *'),
                'enabled' => Setting::get('cron.generate_invoices.enabled', true),
                'timeout' => 300,
            ],
            'suspend_overdue' => [
                'name' => 'Suspender Vencidos',
                'job' => SuspendOverdueServicesJob::class,
                'schedule' => Setting::get('cron.suspend_overdue.schedule', '0 9 * * *'),
                'enabled' => Setting::get('cron.suspend_overdue.enabled', true),
                'timeout' => 300,
            ],
            'server_health' => [
                'name' => 'Saúde dos Servidores',
                'job' => ServerHealthCheckJob::class,
                'schedule' => Setting::get('cron.server_health.schedule', '*/5 * * * *'),
                'enabled' => Setting::get('cron.server_health.enabled', true),
                'timeout' => 120,
            ],
            'late_fees' => [
                'name' => 'Multas e Juros',
                'job' => ApplyLateFeesJob::class,
                'schedule' => Setting::get('cron.late_fees.schedule', '0 0 * * *'),
                'enabled' => Setting::get('cron.late_fees.enabled', true),
                'timeout' => 300,
            ],
            'affiliate_commissions' => [
                'name' => 'Processar Comissões',
                'job' => ProcessAffiliateCommissionsJob::class,
                'schedule' => Setting::get('cron.affiliate_commissions.schedule', '0 2 * * *'),
                'enabled' => Setting::get('cron.affiliate_commissions.enabled', true),
                'timeout' => 300,
            ],
            'cleanup_tokens' => [
                'name' => 'Limpar Tokens Expirados',
                'job' => CleanupExpiredTokensJob::class,
                'schedule' => Setting::get('cron.cleanup_tokens.schedule', '0 3 * * *'),
                'enabled' => Setting::get('cron.cleanup_tokens.enabled', true),
                'timeout' => 120,
            ],
            'due_reminders' => [
                'name' => 'Lembretes de Vencimento',
                'job' => SendDueRemindersJob::class,
                'schedule' => Setting::get('cron.due_reminders.schedule', '0 10 * * *'),
                'enabled' => Setting::get('cron.due_reminders.enabled', true),
                'timeout' => 300,
            ],
        ];
    }
    
    private function shouldRunTask(string $taskKey, array $task): bool
    {
        $lastRun = Setting::get("cron.{$taskKey}.last_run");
        $schedule = $task['schedule'];
        
        // Se nunca executou, deve executar
        if (!$lastRun) {
            return true;
        }
        
        $lastRunTime = Carbon::createFromTimestamp($lastRun);
        $now = now();
        
        // Verifica se deve executar baseado no cron expression
        return $this->cronExpressionMatches($schedule, $now, $lastRunTime);
    }
    
    private function cronExpressionMatches(string $cronExpr, Carbon $now, Carbon $lastRun): bool
    {
        // Parse básico de cron expression (minuto hora dia mês dia_semana)
        $parts = explode(' ', $cronExpr);
        if (count($parts) !== 5) {
            return false;
        }
        
        [$minute, $hour, $day, $month, $dayOfWeek] = $parts;
        
        // Verifica se já passou tempo suficiente desde a última execução
        $diffMinutes = $now->diffInMinutes($lastRun);
        
        // Para */5 (a cada 5 minutos)
        if ($minute === '*/5') {
            return $diffMinutes >= 5;
        }
        
        // Para */10 (a cada 10 minutos)
        if ($minute === '*/10') {
            return $diffMinutes >= 10;
        }
        
        // Para horário específico (ex: 0 8 * * * = 08:00 diariamente)
        if (is_numeric($minute) && is_numeric($hour)) {
            $targetTime = $now->copy()->setTime((int)$hour, (int)$minute, 0);
            
            // Se o horário alvo já passou hoje e não executou hoje
            if ($now->gte($targetTime) && $lastRun->lt($targetTime)) {
                return true;
            }
            
            // Se o horário alvo é amanhã e não executou hoje
            if ($now->lt($targetTime) && $lastRun->lt($now->copy()->startOfDay())) {
                return false;
            }
        }
        
        return false;
    }
    
    private function runTask(string $taskKey, array $task): void
    {
        $this->info("Executando: {$task['name']}");
        
        try {
            $startTime = microtime(true);
            
            // Dispara o job
            $jobClass = $task['job'];
            $job = new $jobClass();
            
            // Executa com timeout
            $this->executeWithTimeout($job, $task['timeout']);
            
            $duration = round((microtime(true) - $startTime) * 1000);
            
            // Registra execução bem-sucedida
            Setting::set("cron.{$taskKey}.last_run", now()->timestamp);
            Setting::set("cron.{$taskKey}.last_duration", $duration);
            Setting::set("cron.{$taskKey}.last_status", 'success');
            Setting::set("cron.{$taskKey}.last_error", null);
            
            $this->info("✓ {$task['name']} executado com sucesso ({$duration}ms)");
            
            Log::info("Cron task executed successfully", [
                'task' => $taskKey,
                'duration_ms' => $duration,
            ]);
            
        } catch (\Exception $e) {
            // Registra erro
            Setting::set("cron.{$taskKey}.last_run", now()->timestamp);
            Setting::set("cron.{$taskKey}.last_status", 'error');
            Setting::set("cron.{$taskKey}.last_error", $e->getMessage());
            
            $this->error("✗ Erro em {$task['name']}: {$e->getMessage()}");
            
            Log::error("Cron task failed", [
                'task' => $taskKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    
    private function executeWithTimeout($job, int $timeout): void
    {
        // Para jobs síncronos, executa diretamente
        if (method_exists($job, 'handle')) {
            $job->handle();
        } else {
            // Para jobs assíncronos, despacha para a fila
            dispatch($job);
        }
    }
}