<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\AutoLoginService;
use App\Services\ServerModules\ServerModuleManager;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AutoLoginController extends Controller
{
    public function __construct(
        private AutoLoginService $autoLoginService,
        private WhatsAppService  $whatsapp,
    ) {}

    /**
     * Admin acessa diretamente o painel do cliente via URL gerada pelo módulo.
     * Rota: GET /admin/servicos/{service}/autologin
     */
    public function login(Request $request, Service $service): RedirectResponse|JsonResponse
    {
        try {
            $module = ServerModuleManager::forService($service);
            $url    = $module->getAutoLoginUrl($service);

            $this->autoLoginService->forAdmin($service, auth('admin')->id(), 1);

            Log::info("Admin auto-login for service #{$service->id}", [
                'admin_id' => auth('admin')->id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['url' => $url]);
            }

            return redirect()->away($url);
        } catch (\Throwable $e) {
            Log::error("AutoLogin failed for service #{$service->id}: " . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', 'Auto login falhou: ' . $e->getMessage());
        }
    }

    /**
     * Gera token e envia link de acesso ao cliente por email e/ou WhatsApp.
     * Rota: POST /admin/servicos/{service}/enviar-acesso
     */
    public function sendLink(Request $request, Service $service): JsonResponse
    {
        $request->validate([
            'hours' => 'nullable|integer|min:1|max:720',
        ]);

        if (!$service->username || !$service->server) {
            return response()->json(['message' => 'Serviço sem servidor ou usuário configurado.'], 422);
        }

        try {
            $hours  = (int) ($request->input('hours', 72));
            $token  = $this->autoLoginService->forAdmin($service, auth('admin')->id(), $hours);
            $url    = $token->publicUrl();
            $client = $service->client;

            $panelName = \App\Services\ServerModules\ServerModuleManager::panelLabel(
                $service->server?->module,
                'Painel'
            );

            // WhatsApp
            if ($client->phone && $client->whatsapp_enabled) {
                $expiresAt = $token->expires_at->format('d/m/Y \à\s H:i');
                $msg  = "🔐 *Link de Acesso ao Painel*\n";
                $msg .= "━━━━━━━━━━━━━━━━━━━\n";
                $msg .= "Serviço: *" . ($service->domain ?? $service->product?->name) . "*\n";
                $msg .= "Painel: *{$panelName}*\n";
                $msg .= "Validade: *{$expiresAt}*\n\n";
                $msg .= "Clique no link para acessar:\n{$url}\n\n";
                $msg .= "_Este link é de uso pessoal. Não compartilhe._\n";
                $msg .= "_" . config('app.name') . "_";
                $this->whatsapp->sendText($client->phone, $msg);
            }

            // Email
            if ($client->email) {
                $subject = 'Link de Acesso ao Painel — ' . ($service->domain ?? $service->product?->name);
                $body    = $this->buildEmail($client, $service, $url, $token->expires_at, $panelName);
                Mail::send([], [], function ($m) use ($client, $subject, $body) {
                    $m->to($client->email, $client->name)->subject($subject)->html($body);
                });
            }

            Log::info("Admin sent access link for service #{$service->id}", [
                'admin_id' => auth('admin')->id(),
                'hours'    => $hours,
            ]);

            return response()->json([
                'success'    => true,
                'message'    => "Link enviado para {$client->name} (" . ($client->email ?? $client->phone) . ").",
                'expires_at' => $token->expires_at->format('d/m/Y H:i'),
                'url'        => $url,
            ]);

        } catch (\Throwable $e) {
            Log::error("sendLink failed for service #{$service->id}: " . $e->getMessage());
            return response()->json(['message' => 'Erro ao gerar link: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Testa a conexão com o módulo do servidor.
     * Rota: POST /admin/servicos/{service}/autologin/testar
     */
    public function test(Service $service): JsonResponse
    {
        try {
            $module = ServerModuleManager::forService($service);
            $ok     = $module->testConnection();

            return response()->json([
                'success' => $ok,
                'message' => $ok
                    ? 'Conexão com o servidor OK.'
                    : 'Falha na conexão com o servidor.',
                'module'  => $service->server?->module ?? 'N/A',
                'host'    => $service->server?->hostname ?? 'N/A',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Retorna métricas de uso do serviço via módulo.
     * Rota: GET /admin/servicos/{service}/uso
     */
    public function usage(Service $service): JsonResponse
    {
        try {
            $stats = ServerModuleManager::forService($service)->getUsageStats($service);
            return response()->json($stats);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function buildEmail($client, $service, string $url, $expiresAt, string $panelName): string
    {
        $appName   = config('app.name');
        $domain    = $service->domain ?? $service->product?->name ?? "Serviço #{$service->id}";
        $expiresStr = $expiresAt instanceof \Carbon\Carbon
            ? $expiresAt->format('d/m/Y \à\s H:i')
            : $expiresAt;

        return <<<HTML
        <!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">
        <style>body{font-family:Inter,Arial,sans-serif;background:#f8fafc;margin:0;padding:2rem;}
        .wrap{max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.07);}
        .header{background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:2rem;color:#fff;text-align:center;}
        .body{padding:2rem;}h2{margin:0 0 .5rem;font-size:1.25rem;}
        p{color:#475569;line-height:1.6;margin:.75rem 0;}
        .btn{display:block;width:fit-content;margin:1.5rem auto;padding:1rem 2rem;background:#4f46e5;color:#fff !important;font-weight:700;border-radius:8px;text-decoration:none;}
        .footer{text-align:center;color:#94a3b8;font-size:.8125rem;padding:1.5rem;border-top:1px solid #f1f5f9;}
        .warn{background:#fef9c3;border-left:3px solid #f59e0b;padding:.75rem 1rem;border-radius:0 6px 6px 0;font-size:.875rem;color:#854d0e;margin-top:1.5rem;}
        .info{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:1rem;margin:1rem 0;font-size:.875rem;}
        </style></head><body>
        <div class="wrap">
          <div class="header"><h2>🔐 Link de Acesso ao Painel</h2><p style="margin:0;opacity:.85">{$appName}</p></div>
          <div class="body">
            <p>Olá, <strong>{$client->name}</strong>!</p>
            <p>Segue o link de acesso direto ao seu painel <strong>{$panelName}</strong> do serviço <strong>{$domain}</strong>:</p>
            <a href="{$url}" class="btn">Acessar o Painel {$panelName} →</a>
            <div class="info">
              <strong>🌐 Link direto:</strong><br>
              <small style="word-break:break-all;font-family:monospace;">{$url}</small>
            </div>
            <div class="warn">⏱ Este link expira em <strong>{$expiresStr}</strong>.<br>
            Não compartilhe este link — ele dá acesso direto ao seu painel.</div>
          </div>
          <div class="footer">{$appName} · Se você não solicitou este link, ignore este email.</div>
        </div></body></html>
        HTML;
    }
}
