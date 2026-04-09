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

        $rawTasks = [
            'generate_invoices' => [
                'name'        => 'Gerar Faturas',
                'description' => 'Gera faturas de renovacao automaticamente.',
                'schedule'    => 'Diariamente (08:00)',
                'cron_expr'   => '0 8 * * *',
                'job'         => 'App\Jobs\GenerateInvoicesJob',
            ],
            'suspend_overdue' => [
                'name'        => 'Suspender Vencidos',
                'description' => 'Suspende servicos com faturas em atraso.',
                'schedule'    => 'Diariamente (09:00)',
                'cron_expr'   => '0 9 * * *',
                'job'         => 'App\Jobs\SuspendOverdueServicesJob',
            ],
            'server_health' => [
                'name'        => 'Saude dos Servidores',
                'description' => 'Monitora CPU, RAM e integridade da rede.',
                'schedule'    => 'A cada 5 minutos',
                'cron_expr'   => '*/5 * * * *',
                'job'         => 'App\Jobs\ServerHealthCheckJob',
            ],
            'late_fees' => [
                'name'        => 'Multas e Juros',
                'description' => 'Aplica encargos em faturas vencidas.',
                'schedule'    => 'Diariamente (00:30)',
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
