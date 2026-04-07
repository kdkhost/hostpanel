<?php

namespace App\Services;

use App\Models\AutoLoginToken;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Client;
use App\Services\ServerModules\ServerModuleManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoLoginService
{
    /**
     * Gera token vinculado a uma fatura.
     * Validade = data de vencimento da fatura.
     * Enviado junto ao email/WhatsApp da fatura.
     */
    public function forInvoice(Invoice $invoice, Service $service): ?AutoLoginToken
    {
        if (!$service->username || !$service->server) {
            return null;
        }

        // Revogar tokens invoice anteriores do mesmo serviço+fatura
        AutoLoginToken::where('service_id', $service->id)
            ->where('invoice_id', $invoice->id)
            ->where('type', 'invoice')
            ->delete();

        $expiresAt = Carbon::parse($invoice->date_due)->endOfDay();

        return AutoLoginToken::create([
            'service_id'   => $service->id,
            'invoice_id'   => $invoice->id,
            'client_id'    => $invoice->client_id,
            'type'         => 'invoice',
            'generated_by' => 'system',
            'expires_at'   => $expiresAt,
        ]);
    }

    /**
     * Gera token avulso a pedido do cliente.
     * Validade padrão: 24h (configurável).
     */
    public function onDemand(Service $service, Client $client, int $hours = 24): AutoLoginToken
    {
        if (!$service->username || !$service->server) {
            throw new \RuntimeException('Serviço sem servidor ou usuário configurado.');
        }

        // Revogar tokens ondemand anteriores não usados do mesmo serviço
        AutoLoginToken::where('service_id', $service->id)
            ->where('type', 'ondemand')
            ->whereNull('used_at')
            ->valid()
            ->delete();

        return AutoLoginToken::create([
            'service_id'   => $service->id,
            'client_id'    => $client->id,
            'type'         => 'ondemand',
            'generated_by' => 'client',
            'expires_at'   => now()->addHours($hours),
        ]);
    }

    /**
     * Gera token administrativo sem expiração automática.
     * Usado pelo admin para enviar acesso ao cliente manualmente.
     */
    public function forAdmin(Service $service, int $adminId, ?int $hours = 72): AutoLoginToken
    {
        return AutoLoginToken::create([
            'service_id'   => $service->id,
            'client_id'    => $service->client_id,
            'admin_id'     => $adminId,
            'type'         => 'admin',
            'generated_by' => 'admin',
            'expires_at'   => $hours ? now()->addHours($hours) : null,
        ]);
    }

    /**
     * Resolve o token público, valida e retorna a URL do painel.
     * Registra o uso.
     */
    public function resolve(string $token, string $ip): array
    {
        $record = AutoLoginToken::where('token', $token)
            ->with(['service.server', 'service.client', 'invoice'])
            ->first();

        if (!$record) {
            return ['valid' => false, 'reason' => 'not_found'];
        }

        if ($record->isExpired()) {
            return [
                'valid'      => false,
                'reason'     => 'expired',
                'expires_at' => $record->expires_at,
                'service'    => $record->service,
                'client'     => $record->service?->client,
            ];
        }

        $service = $record->service;

        if (!$service || !$service->server || !$service->username) {
            return ['valid' => false, 'reason' => 'service_unavailable'];
        }

        if ($service->status !== 'active') {
            return [
                'valid'  => false,
                'reason' => 'service_inactive',
                'status' => $service->status,
            ];
        }

        try {
            $module   = ServerModuleManager::make($service->server);
            $panelUrl = $module->getAutoLoginUrl($service);

            $record->markUsed($ip);

            Log::info("AutoLogin token used [{$record->type}]", [
                'token'      => $token,
                'service_id' => $service->id,
                'ip'         => $ip,
            ]);

            return [
                'valid'     => true,
                'panel_url' => $panelUrl,
                'record'    => $record,
                'service'   => $service,
            ];
        } catch (\Throwable $e) {
            Log::error("AutoLogin resolve failed: " . $e->getMessage());
            return ['valid' => false, 'reason' => 'panel_error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Gera tokens para todos os serviços ativos vinculados a uma fatura.
     * Retorna array de ['service_id' => AutoLoginToken].
     */
    public function forInvoiceServices(Invoice $invoice): array
    {
        $tokens = [];

        // Serviço vinculado diretamente à fatura via order
        $service = $invoice->order?->services()->first() ?? null;

        if ($service && $service->status === 'active' && $service->username && $service->server) {
            $tok = $this->forInvoice($invoice, $service);
            if ($tok) {
                $tokens[$service->id] = $tok;
            }
        }

        return $tokens;
    }

    /**
     * Limpa tokens expirados (para rodar via scheduler).
     */
    public function purgeExpired(): int
    {
        return AutoLoginToken::expired()
            ->where('created_at', '<', now()->subDays(30))
            ->delete();
    }
}
