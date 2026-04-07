<?php

namespace App\Services;

use App\Jobs\SendWhatsAppJob;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Evolution API — WhatsApp Business
 * Docs: https://doc.evolution-api.com/
 */
class WhatsAppService
{
    private string $baseUrl;
    private string $apiKey;
    private string $instance;
    private bool   $enabled;

    public function __construct()
    {
        $this->baseUrl  = rtrim(Setting::get('integration.whatsapp.url',      config('hostpanel.evolution_api.url', '')), '/');
        $this->apiKey   = Setting::get('integration.whatsapp.api_key',         config('hostpanel.evolution_api.key', ''));
        $this->instance = Setting::get('integration.whatsapp.instance',        config('hostpanel.evolution_api.instance', ''));
        $this->enabled  = (bool) Setting::get('modules.whatsapp',              config('hostpanel.modules.whatsapp', false));
    }

    /* ------------------------------------------------------------------ */
    /*  Core HTTP                                                           */
    /* ------------------------------------------------------------------ */

    private function phone(string $number): string
    {
        $clean = preg_replace('/\D/', '', $number);
        if (!str_starts_with($clean, '55')) {
            $clean = '55' . $clean;
        }
        return $clean;
    }

    private function dispatch(
        string  $phone,
        string  $message,
        string  $type          = 'text',
        ?string $mediaUrl      = null,
        ?string $mediaCaption  = null,
        ?int    $clientId      = null,
        ?string $templateSlug  = null,
        int     $minDelay      = 5,
        int     $maxDelay      = 15,
    ): bool {
        if (!$this->isEnabled()) return false;

        SendWhatsAppJob::dispatch(
            phone:           $phone,
            message:         $message,
            type:            $type,
            mediaUrl:        $mediaUrl,
            mediaCaption:    $mediaCaption,
            notifiableId:    $clientId,
            notifiableType:  $clientId ? \App\Models\Client::class : null,
            templateSlug:    $templateSlug,
            minDelaySeconds: $minDelay,
            maxDelaySeconds: $maxDelay,
        )->onQueue('whatsapp');

        return true;
    }

    /* ------------------------------------------------------------------ */
    /*  Métodos Públicos                                                    */
    /* ------------------------------------------------------------------ */

    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->baseUrl) && !empty($this->apiKey) && !empty($this->instance);
    }

    /**
     * Envia mensagem de texto simples.
     */
    public function sendText(string $phone, string $message, ?int $clientId = null, ?string $templateSlug = null): bool
    {
        return $this->dispatch(
            phone:         $phone,
            message:       $message,
            type:          'text',
            clientId:      $clientId,
            templateSlug:  $templateSlug,
        );
    }

    /**
     * Envia mídia (imagem do QR Code PIX, por exemplo).
     */
    public function sendMedia(string $phone, string $mediaUrl, string $caption = '', ?int $clientId = null): bool
    {
        return $this->dispatch(
            phone:        $phone,
            message:      $caption,
            type:         'media',
            mediaUrl:     $mediaUrl,
            mediaCaption: $caption,
            clientId:     $clientId,
            minDelay:     8,
            maxDelay:     20,
        );
    }

    /**
     * Envia fatura gerada (mensagem formatada + link de pagamento).
     */
    public function sendInvoice(Invoice $invoice, ?string $accessUrl = null): bool
    {
        if (!$this->isEnabled()) return false;

        $client = $invoice->client;
        if (!$client?->phone || !$client?->whatsapp_enabled) return false;

        $msg  = "🧾 *Fatura #{$invoice->number}*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "📋 *Status:* {$invoice->status_label}\n";
        $msg .= "💰 *Valor:* {$invoice->formatted_amount_due}\n";
        $msg .= "📅 *Vencimento:* {$invoice->date_due?->format('d/m/Y')}\n";

        if ($invoice->amount_due > 0) {
            $payUrl = route('client.invoices.show', $invoice->id);
            $msg   .= "\n🔗 *Pagar online:*\n{$payUrl}";
        }

        if ($accessUrl) {
            $expiresAt = $invoice->date_due?->format('d/m/Y') ?? 'vencimento';
            $msg .= "\n\n🔐 *Acesso ao painel* (válido até {$expiresAt}):\n{$accessUrl}";
        }

        $msg .= "\n\n_" . config('app.name') . "_";

        return $this->sendText($client->phone, $msg);
    }

    /**
     * Envia confirmação de pagamento.
     */
    public function sendPaymentConfirmed(Invoice $invoice): bool
    {
        if (!$this->isEnabled()) return false;

        $client = $invoice->client;
        if (!$client?->phone || !$client?->whatsapp_enabled) return false;

        $msg  = "✅ *Pagamento Confirmado!*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "🧾 *Fatura:* #{$invoice->number}\n";
        $msg .= "💰 *Valor pago:* R$ " . number_format($invoice->amount_paid, 2, ',', '.') . "\n";
        $msg .= "📅 *Data:* " . now()->format('d/m/Y H:i') . "\n";
        $msg .= "\nObrigado pelo pagamento! 🙏\n";
        $msg .= "_" . config('app.name') . "_";

        return $this->sendText($client->phone, $msg, $client->id, 'payment_confirmed');
    }

    /**
     * Envia PIX (EMV + QR Code) via WhatsApp.
     */
    public function sendPix(Invoice $invoice, string $pixEmv, ?string $qrCodeUrl = null, ?string $accessUrl = null): bool
    {
        if (!$this->isEnabled()) return false;

        $client = $invoice->client;
        if (!$client?->phone || !$client?->whatsapp_enabled) return false;

        $msg  = "💸 *PIX para Fatura #{$invoice->number}*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "💰 *Valor:* {$invoice->formatted_amount_due}\n";
        $msg .= "📅 *Vencimento:* {$invoice->date_due?->format('d/m/Y')}\n\n";
        $msg .= "📋 *Copia e Cola PIX:*\n`{$pixEmv}`";
        $msg .= "\n\n_Após o pagamento, a confirmação é automática._";

        if ($accessUrl) {
            $msg .= "\n\n🔐 *Acesso ao painel:*\n{$accessUrl}";
        }

        $sent = $this->sendText($client->phone, $msg, $client->id, 'pix_generated');

        if ($qrCodeUrl && $sent) {
            $this->sendMedia($client->phone, $qrCodeUrl, "QR Code PIX — Fatura #{$invoice->number}", $client->id);
        }

        return $sent;
    }

    /**
     * Envia boleto (código de barras).
     */
    public function sendBoleto(Invoice $invoice, string $barcode, string $boletoUrl, ?string $accessUrl = null): bool
    {
        if (!$this->isEnabled()) return false;

        $client = $invoice->client;
        if (!$client?->phone || !$client?->whatsapp_enabled) return false;

        $msg  = "🏦 *Boleto — Fatura #{$invoice->number}*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "💰 *Valor:* {$invoice->formatted_amount_due}\n";
        $msg .= "📅 *Vencimento:* {$invoice->date_due?->format('d/m/Y')}\n\n";
        $msg .= "📋 *Linha Digitável:*\n`{$barcode}`\n\n";
        $msg .= "🔗 *Link do boleto:* {$boletoUrl}";

        if ($accessUrl) {
            $msg .= "\n\n🔐 *Acesso ao painel:*\n{$accessUrl}";
        }

        return $this->sendText($client->phone, $msg, $client->id, 'boleto_generated');
    }

    /**
     * Envia lembrete de vencimento próximo.
     */
    public function sendDueReminder(Invoice $invoice, int $daysUntilDue): bool
    {
        if (!$this->isEnabled()) return false;

        $client = $invoice->client;
        if (!$client?->phone || !$client?->whatsapp_enabled) return false;

        $when = $daysUntilDue === 0 ? 'hoje' : "em {$daysUntilDue} dia(s)";
        $msg  = "⚠️ *Lembrete de Vencimento*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Sua fatura *#{$invoice->number}* vence {$when}.\n";
        $msg .= "💰 *Valor:* {$invoice->formatted_amount_due}\n\n";
        $msg .= "🔗 " . route('client.invoices.show', $invoice->id);

        return $this->sendText($client->phone, $msg, $client->id, 'due_reminder');
    }

    /**
     * Envia aviso de fatura vencida.
     */
    public function sendOverdueNotice(Invoice $invoice): bool
    {
        if (!$this->isEnabled()) return false;

        $client = $invoice->client;
        if (!$client?->phone || !$client?->whatsapp_enabled) return false;

        $days = $invoice->date_due->diffInDays(now());
        $msg  = "🚨 *Fatura Vencida*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Sua fatura *#{$invoice->number}* está vencida há {$days} dia(s).\n";
        $msg .= "💰 *Valor atualizado:* {$invoice->formatted_amount_due}\n\n";
        $msg .= "Regularize agora para evitar a suspensão dos serviços:\n";
        $msg .= "🔗 " . route('client.invoices.show', $invoice->id);

        return $this->sendText($client->phone, $msg, $client->id, 'overdue_notice');
    }
}
