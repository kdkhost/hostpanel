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
        foreach ($request->except(['_token', '_method']) as $key => $value) {
            Setting::set($key, $value);
        }
        Setting::flush();
        return response()->json(['message' => 'Configurações salvas com sucesso!']);
    }

    public function saveWhatsApp(Request $request): JsonResponse
    {
        $request->validate([
            'url'      => 'required|url',
            'api_key'  => 'required|string',
            'instance' => 'required|string',
        ]);

        $env = base_path('.env');
        $contents = file_get_contents($env);

        $map = [
            'EVOLUTION_API_URL'      => $request->url,
            'EVOLUTION_API_KEY'      => $request->api_key,
            'EVOLUTION_API_INSTANCE' => $request->instance,
        ];

        foreach ($map as $key => $value) {
            if (str_contains($contents, "{$key}=")) {
                $contents = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $contents);
            } else {
                $contents .= "\n{$key}={$value}";
            }
        }

        file_put_contents($env, $contents);

        \Illuminate\Support\Facades\Artisan::call('config:clear');

        return response()->json(['message' => 'Configurações do WhatsApp salvas!']);
    }

    public function testWhatsApp(Request $request): JsonResponse
    {
        $url      = rtrim($request->input('url', config('hostpanel.evolution_api.url')), '/');
        $apiKey   = $request->input('api_key', config('hostpanel.evolution_api.key'));
        $instance = $request->input('instance', config('hostpanel.evolution_api.instance'));

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
