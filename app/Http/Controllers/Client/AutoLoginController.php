<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\AutoLoginService;
use App\Services\ServerModules\ServerModuleManager;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AutoLoginController extends Controller
{
    public function __construct(
        private AutoLoginService $autoLoginService,
        private WhatsAppService  $whatsapp,
    ) {}

    protected function client() { return Auth::guard('client')->user(); }

    /**
     * Auto-login do cliente no painel de hospedagem.
     * Rota: GET /cliente/servicos/{service}/autologin
     */
    public function login(Request $request, Service $service): RedirectResponse|JsonResponse
    {
        if ($service->client_id !== $this->client()->id) {
            abort(403, 'Acesso não autorizado.');
        }

        if ($service->status !== 'active') {
            $msg = 'O serviço precisa estar ativo para acessar o painel.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        try {
            $module = ServerModuleManager::forService($service);
            $url    = $module->getAutoLoginUrl($service);

            Log::info("Client direct auto-login for service #{$service->id}", [
                'client_id' => $this->client()->id,
            ]);

            if ($request->expectsJson()) {
                return response()->json(['url' => $url]);
            }

            return redirect()->away($url);
        } catch (\Throwable $e) {
            Log::error("Client AutoLogin failed for service #{$service->id}: " . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', 'Auto login falhou: ' . $e->getMessage());
        }
    }

    /**
     * Gera token avulso e envia ao cliente via email e/ou WhatsApp.
     * Rota: POST /cliente/servicos/{service}/solicitar-acesso
     */
    public function requestAccess(Request $request, Service $service): JsonResponse
    {
        if ($service->client_id !== $this->client()->id) {
            abort(403);
        }

        if ($service->status !== 'active') {
            return response()->json([
                'message' => 'O serviço precisa estar ativo para solicitar link de acesso.',
            ], 422);
        }

        if (!$service->username || !$service->server) {
            return response()->json([
                'message' => 'Este serviço não possui painel de controle configurado.',
            ], 422);
        }

        try {
            $client = $this->client();
            $token  = $this->autoLoginService->onDemand($service, $client, 24);
            $url    = $token->publicUrl();

            // Enviar por WhatsApp
            if ($client->phone && $client->whatsapp_enabled) {
                $panelName = \App\Services\ServerModules\ServerModuleManager::panelLabel(
                    $service->server?->module,
                    'Painel'
                );
                $msg  = "🔐 *Link de Acesso ao Painel*\n";
                $msg .= "Serviço: *" . ($service->domain ?? $service->product?->name) . "*\n";
                $msg .= "Painel: *{$panelName}*\n";
                $msg .= "Validade: *24 horas*\n\n";
                $msg .= "Clique no link abaixo para acessar:\n{$url}\n\n";
                $msg .= "_Este link é pessoal e intransferível._";
                $this->whatsapp->sendText($client->phone, $msg);
            }

            // Enviar por email
            if ($client->email) {
                $subject = 'Link de Acesso ao Painel — ' . ($service->domain ?? $service->product?->name);
                $body    = $this->buildAccessLinkEmail($client, $service, $url, 24);
                Mail::send([], [], function ($m) use ($client, $subject, $body) {
                    $m->to($client->email, $client->name)->subject($subject)->html($body);
                });
            }

            Log::info("On-demand access link generated for service #{$service->id}", [
                'client_id' => $client->id,
                'expires'   => $token->expires_at,
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Link de acesso enviado para seu email' . ($client->whatsapp_enabled ? ' e WhatsApp' : '') . '.',
                'expires_at' => $token->expires_at->format('d/m/Y \à\s H:i'),
            ]);

        } catch (\Throwable $e) {
            Log::error("requestAccess failed for service #{$service->id}: " . $e->getMessage());
            return response()->json(['message' => 'Não foi possível gerar o link: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Retorna métricas de disco/uso para o cliente.
     * Rota: GET /cliente/servicos/{service}/uso
     */
    public function usage(Service $service): JsonResponse
    {
        if ($service->client_id !== $this->client()->id) {
            abort(403);
        }

        try {
            $stats = ServerModuleManager::forService($service)->getUsageStats($service);
            return response()->json($stats);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function buildAccessLinkEmail($client, $service, string $url, int $hours): string
    {
        $appName   = config('app.name');
        $domain    = $service->domain ?? $service->product?->name ?? "Serviço #{$service->id}";
        $expiresAt = now()->addHours($hours)->format('d/m/Y \à\s H:i');

        return <<<HTML
        <!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">
        <style>body{font-family:Inter,Arial,sans-serif;background:#f8fafc;margin:0;padding:2rem;}
        .wrap{max-width:540px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.07);}
        .header{background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:2rem;color:#fff;text-align:center;}
        .body{padding:2rem;}h2{margin:0 0 .5rem;font-size:1.25rem;}
        p{color:#475569;line-height:1.6;margin:.75rem 0;}
        .btn{display:block;width:fit-content;margin:1.5rem auto;padding:1rem 2rem;background:#4f46e5;color:#fff;font-weight:700;border-radius:8px;text-decoration:none;}
        .footer{text-align:center;color:#94a3b8;font-size:.8125rem;padding:1.5rem;border-top:1px solid #f1f5f9;}
        .warn{background:#fef9c3;border-left:3px solid #f59e0b;padding:.75rem 1rem;border-radius:0 6px 6px 0;font-size:.875rem;color:#854d0e;margin-top:1.5rem;}
        </style></head><body>
        <div class="wrap">
          <div class="header"><h2>🔐 Link de Acesso ao Painel</h2><p style="margin:0;opacity:.85">{$appName}</p></div>
          <div class="body">
            <p>Olá, <strong>{$client->name}</strong>!</p>
            <p>Aqui está o seu link de acesso automático ao painel de hospedagem do serviço <strong>{$domain}</strong>:</p>
            <a href="{$url}" class="btn">Acessar o Painel de Hospedagem →</a>
            <p>Ou copie e cole o endereço abaixo no seu navegador:</p>
            <p style="font-size:.8125rem;background:#f1f5f9;padding:.75rem;border-radius:6px;word-break:break-all;font-family:monospace;">{$url}</p>
            <div class="warn">⏱ Este link expira em <strong>{$expiresAt}</strong>. Não compartilhe este link com terceiros.</div>
          </div>
          <div class="footer">{$appName} · Enviado automaticamente. Em caso de dúvidas, contate o suporte.</div>
        </div></body></html>
        HTML;
    }
}
