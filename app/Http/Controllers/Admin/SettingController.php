<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $groups = Setting::query()->orderBy('group')->orderBy('key')->get()->groupBy('group');
        return view('admin.settings.index', compact('groups'));
    }

    public function cron()
    {
        $basePath   = base_path();
        $phpBinary  = PHP_BINARY ?: '/usr/local/bin/php';

        // Heartbeat status
        $lastHeartbeat = Setting::get('cron.last_heartbeat');
        $cronStatus = $lastHeartbeat && (now()->timestamp - $lastHeartbeat) < 120 ? 'online' : 'offline';

        $username = null;
        if (preg_match('#^/home/([^/]+)/#', $basePath, $m)) {
            $username = $m[1];
        } elseif (preg_match('#^/var/www/([^/]+)/#', $basePath, $m)) {
            $username = $m[1];
        }
        $username = $username ?: get_current_user() ?: 'usuario';

        $cronTasks = $this->getCronTasks();
        
        return view('admin.settings.cron', compact(
            'basePath', 'phpBinary', 'username', 'cronStatus', 'cronTasks', 'lastHeartbeat'
        ));
    }
    
    private function getCronTasks(): array
    {
        return [
            'generate_invoices' => [
                'name'        => 'Gerar Faturas',
                'description' => 'Gera faturas de renovação automaticamente para serviços com vencimento próximo.',
                'schedule'    => Setting::get('cron.generate_invoices.schedule', '0 8 * * *'),
                'enabled'     => Setting::get('cron.generate_invoices.enabled', true),
                'last_run'    => Setting::get('cron.generate_invoices.last_run'),
                'last_status' => Setting::get('cron.generate_invoices.last_status'),
                'last_error'  => Setting::get('cron.generate_invoices.last_error'),
                'last_duration' => Setting::get('cron.generate_invoices.last_duration'),
                'job'         => 'App\Jobs\GenerateInvoicesJob',
            ],
            'suspend_overdue' => [
                'name'        => 'Suspender Vencidos',
                'description' => 'Suspende serviços com faturas em atraso após período de carência.',
                'schedule'    => Setting::get('cron.suspend_overdue.schedule', '0 9 * * *'),
                'enabled'     => Setting::get('cron.suspend_overdue.enabled', true),
                'last_run'    => Setting::get('cron.suspend_overdue.last_run'),
                'last_status' => Setting::get('cron.suspend_overdue.last_status'),
                'last_error'  => Setting::get('cron.suspend_overdue.last_error'),
                'last_duration' => Setting::get('cron.suspend_overdue.last_duration'),
                'job'         => 'App\Jobs\SuspendOverdueServicesJob',
            ],
            'server_health' => [
                'name'        => 'Saúde dos Servidores',
                'description' => 'Monitora CPU, RAM, disco e conectividade dos servidores.',
                'schedule'    => Setting::get('cron.server_health.schedule', '*/5 * * * *'),
                'enabled'     => Setting::get('cron.server_health.enabled', true),
                'last_run'    => Setting::get('cron.server_health.last_run'),
                'last_status' => Setting::get('cron.server_health.last_status'),
                'last_error'  => Setting::get('cron.server_health.last_error'),
                'last_duration' => Setting::get('cron.server_health.last_duration'),
                'job'         => 'App\Jobs\ServerHealthCheckJob',
            ],
            'late_fees' => [
                'name'        => 'Multas e Juros',
                'description' => 'Aplica multas e juros em faturas vencidas conforme configuração.',
                'schedule'    => Setting::get('cron.late_fees.schedule', '0 0 * * *'),
                'enabled'     => Setting::get('cron.late_fees.enabled', true),
                'last_run'    => Setting::get('cron.late_fees.last_run'),
                'last_status' => Setting::get('cron.late_fees.last_status'),
                'last_error'  => Setting::get('cron.late_fees.last_error'),
                'last_duration' => Setting::get('cron.late_fees.last_duration'),
                'job'         => 'App\Jobs\ApplyLateFeesJob',
            ],
            'affiliate_commissions' => [
                'name'        => 'Processar Comissões',
                'description' => 'Processa comissões de afiliados para faturas pagas recentemente.',
                'schedule'    => Setting::get('cron.affiliate_commissions.schedule', '0 2 * * *'),
                'enabled'     => Setting::get('cron.affiliate_commissions.enabled', true),
                'last_run'    => Setting::get('cron.affiliate_commissions.last_run'),
                'last_status' => Setting::get('cron.affiliate_commissions.last_status'),
                'last_error'  => Setting::get('cron.affiliate_commissions.last_error'),
                'last_duration' => Setting::get('cron.affiliate_commissions.last_duration'),
                'job'         => 'App\Jobs\ProcessAffiliateCommissionsJob',
            ],
            'cleanup_tokens' => [
                'name'        => 'Limpar Tokens Expirados',
                'description' => 'Remove tokens expirados, itens de carrinho antigos e logs desnecessários.',
                'schedule'    => Setting::get('cron.cleanup_tokens.schedule', '0 3 * * *'),
                'enabled'     => Setting::get('cron.cleanup_tokens.enabled', true),
                'last_run'    => Setting::get('cron.cleanup_tokens.last_run'),
                'last_status' => Setting::get('cron.cleanup_tokens.last_status'),
                'last_error'  => Setting::get('cron.cleanup_tokens.last_error'),
                'last_duration' => Setting::get('cron.cleanup_tokens.last_duration'),
                'job'         => 'App\Jobs\CleanupExpiredTokensJob',
            ],
            'due_reminders' => [
                'name'        => 'Lembretes de Vencimento',
                'description' => 'Envia lembretes de vencimento de faturas por email e WhatsApp.',
                'schedule'    => Setting::get('cron.due_reminders.schedule', '0 10 * * *'),
                'enabled'     => Setting::get('cron.due_reminders.enabled', true),
                'last_run'    => Setting::get('cron.due_reminders.last_run'),
                'last_status' => Setting::get('cron.due_reminders.last_status'),
                'last_error'  => Setting::get('cron.due_reminders.last_error'),
                'last_duration' => Setting::get('cron.due_reminders.last_duration'),
                'job'         => 'App\Jobs\SendDueRemindersJob',
            ],
        ];
    }
    
    public function cronUpdate(Request $request)
    {
        $request->validate([
            'task' => 'required|string',
            'enabled' => 'boolean',
            'schedule' => 'required|string|regex:/^[\d\*\/\-\,\s]+$/',
        ]);
        
        $task = $request->task;
        $enabled = $request->boolean('enabled');
        $schedule = $request->schedule;
        
        // Valida formato básico do cron
        $cronParts = explode(' ', $schedule);
        if (count($cronParts) !== 5) {
            return response()->json(['error' => 'Formato de cron inválido. Use: minuto hora dia mês dia_semana'], 400);
        }
        
        Setting::set("cron.{$task}.enabled", $enabled);
        Setting::set("cron.{$task}.schedule", $schedule);
        
        return response()->json(['message' => 'Configuração de cron atualizada com sucesso!']);
    }
    
    public function cronRunTask(Request $request)
    {
        $request->validate(['task' => 'required|string']);
        
        $task = $request->task;
        $cronTasks = $this->getCronTasks();
        
        if (!isset($cronTasks[$task])) {
            return response()->json(['error' => 'Tarefa não encontrada'], 404);
        }
        
        try {
            \Artisan::call('cron:master');
            return response()->json(['message' => 'Tarefa executada com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao executar tarefa: ' . $e->getMessage()], 500);
        }
                'cron_expr'   => '30 0 * * *',
                'job'         => 'Closure (apply-late-fees)',
            ],
        ];

        $tasks = [];
        foreach ($rawTasks as $key => $task) {
            $lastRun = Setting::get("cron.last_run.{$key}");
            $task['key'] = $key;
            $task['last_run'] = $lastRun ? \Carbon\Carbon::createFromTimestamp($lastRun)->diffForHumans() : 'Nunca';
            $task['status'] = $lastRun && (now()->timestamp - $lastRun) < 86400 * 2 ? 'ok' : 'pending';
            $tasks[] = $task;
        }

        return view('admin.settings.cron', compact('basePath', 'phpBinary', 'username', 'tasks', 'cronStatus', 'lastHeartbeat'));
    }

    public function runTask(Request $request): JsonResponse
    {
        $taskKey = $request->input('task');

        $jobs = [
            'generate_invoices' => \App\Jobs\GenerateInvoicesJob::class,
            'suspend_overdue'   => \App\Jobs\SuspendOverdueServicesJob::class,
            'server_health'     => \App\Jobs\ServerHealthCheckJob::class,
        ];

        try {
            if (isset($jobs[$taskKey])) {
                $jobClass = $jobs[$taskKey];
                $jobClass::dispatchSync();
                Setting::set("cron.last_run.{$taskKey}", now()->timestamp, 'system');
                return response()->json(['message' => 'Tarefa executada com sucesso!']);
            }

            return response()->json(['message' => 'Esta tarefa nao pode ser executada manualmente.'], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $encryptedKeys = Setting::whereIn('type', ['encrypted'])
            ->pluck('type', 'key')
            ->toArray();

        foreach ($request->except(['_token', '_method']) as $key => $value) {
            if (isset($encryptedKeys[$key])) {
                if ($value !== '' && $value !== null) {
                    Setting::setEncrypted($key, (string) $value);
                }
                // valor vazio = manter o anterior (não sobrescreve)
            } else {
                Setting::set($key, $value);
            }
        }

        Setting::flush();
        return response()->json(['message' => 'Configurações salvas com sucesso!']);
    }

    public function saveWhatsApp(Request $request): JsonResponse
    {
        $request->validate([
            'url'      => 'nullable|string|max:255',
            'api_key'  => 'nullable|string|max:255',
            'instance' => 'nullable|string|max:100',
            'enabled'  => 'nullable|boolean',
        ]);

        Setting::setEncrypted('integration.whatsapp.url', rtrim($request->input('url', ''), '/'), 'integrations');
        Setting::setEncrypted('integration.whatsapp.api_key', $request->input('api_key', ''), 'integrations');
        Setting::setEncrypted('integration.whatsapp.instance', $request->input('instance', ''), 'integrations');
        Setting::set('modules.whatsapp', $request->boolean('enabled') ? '1' : '0', 'modules');

        return response()->json(['message' => 'Configurações do WhatsApp salvas!']);
    }

    public function testWhatsApp(Request $request): JsonResponse
    {
        $url      = rtrim($request->input('url',      Setting::get('integration.whatsapp.url', '')), '/');
        $apiKey   = $request->input('api_key',   Setting::get('integration.whatsapp.api_key', ''));
        $instance = $request->input('instance',  Setting::get('integration.whatsapp.instance', ''));

        try {
            $res = \Illuminate\Support\Facades\Http::withHeaders(['apikey' => $apiKey])
                ->timeout(10)
                ->get("{$url}/instance/connectionState/{$instance}");

            $state = $res->json('instance.state') ?? $res->json('state') ?? null;
            $connected = $res->successful() && in_array($state, ['open', 'connecting', 'connected']);

            return response()->json([
                'success' => $connected,
                'message' => $connected
                    ? "Conectado! Estado: {$state}"
                    : "Falha na conexão. Verifique a URL, API Key e instância.",
                'raw'     => $res->json(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ]);
        }
    }
}
