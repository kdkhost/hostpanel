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
        $groups = Setting::orderBy('group')->orderBy('key')->get()->groupBy('group');
        return view('admin.settings.index', compact('groups'));
    }

    public function cron()
    {
        $basePath   = base_path();
        $phpBinary  = PHP_BINARY ?: '/usr/local/bin/php';

        // Detecta usuário Linux a partir do caminho (/home/USER/...) ou função nativa
        $username = null;
        if (preg_match('#^/home/([^/]+)/#', $basePath, $m)) {
            $username = $m[1];
        } elseif (preg_match('#^/var/www/([^/]+)/#', $basePath, $m)) {
            $username = $m[1];
        } elseif (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $username = posix_getpwuid(posix_geteuid())['name'] ?? null;
        }
        $username = $username ?: get_current_user() ?: 'usuario';

        $tasks = [
            [
                'name'        => 'Gerar Faturas',
                'description' => 'Gera automaticamente as faturas de renovação dos serviços ativos antes do vencimento.',
                'schedule'    => 'Diariamente às 08:00',
                'cron_expr'   => '0 8 * * *',
                'job'         => 'App\Jobs\GenerateInvoicesJob',
            ],
            [
                'name'        => 'Suspender Serviços Vencidos',
                'description' => 'Suspende serviços com faturas vencidas após o período de carência configurado.',
                'schedule'    => 'Diariamente às 09:00',
                'cron_expr'   => '0 9 * * *',
                'job'         => 'App\Jobs\SuspendOverdueServicesJob',
            ],
            [
                'name'        => 'Monitoramento de Servidores',
                'description' => 'Verifica a saúde de todos os servidores: CPU, RAM, disco, latência e status de rede.',
                'schedule'    => 'A cada 5 minutos',
                'cron_expr'   => '*/5 * * * *',
                'job'         => 'App\Jobs\ServerHealthCheckJob',
            ],
            [
                'name'        => 'Aplicar Multas e Juros',
                'description' => 'Aplica multa e juros diários nas faturas com status "vencida" conforme configuração financeira.',
                'schedule'    => 'Diariamente à 00:30',
                'cron_expr'   => '30 0 * * *',
                'job'         => 'Closure (apply-late-fees)',
            ],
            [
                'name'        => 'Limpar Logs Antigos',
                'description' => 'Remove logs de login, notificações e gateway com mais de 90 dias para liberar espaço.',
                'schedule'    => 'Semanalmente (domingo)',
                'cron_expr'   => '0 0 * * 0',
                'job'         => 'Closure (clean-old-logs)',
            ],
            [
                'name'        => 'Purgar Tokens de Auto Login',
                'description' => 'Remove tokens de acesso automático expirados há mais de 30 dias.',
                'schedule'    => 'Diariamente às 03:00',
                'cron_expr'   => '0 3 * * *',
                'job'         => 'App\Services\AutoLoginService::purgeExpired',
            ],
            [
                'name'        => 'Alertas de Serviços Expirando',
                'description' => 'Envia notificação ao cliente quando um serviço ativo vence em 7 dias.',
                'schedule'    => 'Diariamente às 10:00',
                'cron_expr'   => '0 10 * * *',
                'job'         => 'Closure (service-expiry-alerts)',
            ],
        ];

        return view('admin.settings.cron', compact('basePath', 'phpBinary', 'username', 'tasks'));
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
