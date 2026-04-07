<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\AutoLoginService;
use Illuminate\Support\Facades\Log;

class InvoiceNotificationService
{
    public function __construct(
        private readonly WhatsAppService  $whatsapp,
        private readonly AutoLoginService $autoLogin,
    ) {}

    /* ------------------------------------------------------------------ */
    /*  Nova fatura gerada                                                  */
    /* ------------------------------------------------------------------ */

    public function sendInvoiceCreated(Invoice $invoice): void
    {
        $accessUrl = $this->generateAccessUrl($invoice);
        $this->sendMail('invoice_created', $invoice, ['access_url' => $accessUrl]);
        $this->whatsapp->sendInvoice($invoice, $accessUrl);
    }

    /* ------------------------------------------------------------------ */
    /*  Pagamento confirmado (chamado pelo webhook)                         */
    /* ------------------------------------------------------------------ */

    public function sendPaymentConfirmed(Invoice $invoice): void
    {
        $this->sendMail('payment_confirmed', $invoice);
        $this->whatsapp->sendPaymentConfirmed($invoice);
    }

    /* ------------------------------------------------------------------ */
    /*  PIX gerado — envia EMV + QR Code                                   */
    /* ------------------------------------------------------------------ */

    public function sendPixGenerated(Invoice $invoice, Transaction $transaction): void
    {
        $meta      = $transaction->meta ?? [];
        $pixEmv    = $meta['pix_emv'] ?? null;
        $qrUrl     = $meta['pix_qrcode'] ?? null;
        $accessUrl = $this->generateAccessUrl($invoice);

        if ($pixEmv) {
            $this->whatsapp->sendPix($invoice, $pixEmv, $qrUrl, $accessUrl);
        }

        $this->sendMail('invoice_created', $invoice, [
            'pix_emv'    => $pixEmv,
            'pix_qrcode' => $qrUrl,
            'access_url' => $accessUrl,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Boleto gerado                                                       */
    /* ------------------------------------------------------------------ */

    public function sendBoletoGenerated(Invoice $invoice, Transaction $transaction): void
    {
        $meta      = $transaction->meta ?? [];
        $barcode   = $meta['barcode_formatted'] ?? $meta['barcode'] ?? '';
        $boletoUrl = $meta['payment_url'] ?? route('client.invoices.show', $invoice->id);
        $accessUrl = $this->generateAccessUrl($invoice);

        if ($barcode) {
            $this->whatsapp->sendBoleto($invoice, $barcode, $boletoUrl, $accessUrl);
        }

        $this->sendMail('invoice_created', $invoice, [
            'barcode'    => $barcode,
            'access_url' => $accessUrl,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Lembretes de vencimento                                             */
    /* ------------------------------------------------------------------ */

    public function sendDueReminder(Invoice $invoice, int $daysUntilDue): void
    {
        $this->sendMail('invoice_reminder', $invoice, ['days_until_due' => $daysUntilDue]);
        $this->whatsapp->sendDueReminder($invoice, $daysUntilDue);
    }

    /* ------------------------------------------------------------------ */
    /*  Aviso de vencido                                                    */
    /* ------------------------------------------------------------------ */

    public function sendOverdueNotice(Invoice $invoice): void
    {
        $this->sendMail('invoice_overdue', $invoice);
        $this->whatsapp->sendOverdueNotice($invoice);
    }

    /* ------------------------------------------------------------------ */
    /*  Reembolso processado                                                */
    /* ------------------------------------------------------------------ */

    public function sendRefundProcessed(Invoice $invoice, float $amount, string $type): void
    {
        $label = $type === 'full' ? 'total' : 'parcial';
        $phone = $invoice->client?->phone;

        if ($phone && $invoice->client?->whatsapp_enabled) {
            $msg  = "💸 *Reembolso {$label} processado*\n";
            $msg .= "Fatura *#{$invoice->number}*\n";
            $msg .= "💰 *Valor:* R$ " . number_format($amount, 2, ',', '.') . "\n";
            $msg .= "O reembolso será creditado em até 5 dias úteis.";
            $this->whatsapp->sendText($phone, $msg);
        }

        $this->sendMail('payment_refunded', $invoice, ['refund_amount' => $amount, 'refund_type' => $type]);
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    private function sendMail(string $template, Invoice $invoice, array $extra = []): void
    {
        $client = $invoice->client;
        if (!$client?->email) return;

        $emailTemplate = \App\Models\EmailTemplate::where('slug', $template)->first();

        if (!$emailTemplate || !$emailTemplate->active) {
            Log::info("EmailTemplate '{$template}' not found or inactive — skipping mail.");
            return;
        }

        $subject = $this->replacePlaceholders($emailTemplate->subject, $invoice, $extra);
        $body    = $this->replacePlaceholders($emailTemplate->body, $invoice, $extra);

        SendEmailJob::dispatch(
            toEmail:        $client->email,
            toName:         $client->name,
            subject:        $subject,
            htmlBody:       $body,
            notifiableId:   $client->id,
            notifiableType: \App\Models\Client::class,
            templateSlug:   $template,
        )->onQueue('email');

        $invoice->update(['email_sent' => true, 'email_sent_at' => now()]);
    }

    private function replacePlaceholders(string $text, Invoice $invoice, array $extra = []): string
    {
        $vars = array_merge([
            '{client_name}'     => $invoice->client?->name ?? '',
            '{invoice_number}'  => $invoice->number ?? '',
            '{invoice_total}'   => 'R$ ' . number_format($invoice->total, 2, ',', '.'),
            '{amount_due}'      => 'R$ ' . number_format($invoice->amount_due, 2, ',', '.'),
            '{due_date}'        => $invoice->date_due?->format('d/m/Y') ?? '',
            '{payment_url}'     => route('client.invoices.show', $invoice->id),
            '{app_name}'        => config('app.name'),
            '{app_url}'         => config('app.url'),
            '{pix_emv}'         => $extra['pix_emv'] ?? '',
            '{barcode}'         => $extra['barcode'] ?? '',
            '{access_url}'      => $extra['access_url'] ?? '',
            '{refund_amount}'   => isset($extra['refund_amount'])
                ? 'R$ ' . number_format($extra['refund_amount'], 2, ',', '.')
                : '',
        ], $extra);

        return str_replace(array_keys($vars), array_values($vars), $text);
    }

    /**
     * Gera URL pública de auto login vinculada à fatura (expira no vencimento).
     * Retorna null se o serviço não tiver painel configurado.
     */
    private function generateAccessUrl(Invoice $invoice): ?string
    {
        try {
            $service = $invoice->order?->services()->first() ?? null;

            if (!$service || $service->status !== 'active' || !$service->username || !$service->server) {
                return null;
            }

            $tokens = $this->autoLogin->forInvoiceServices($invoice);
            $token  = reset($tokens);

            return $token ? $token->publicUrl() : null;
        } catch (\Throwable $e) {
            Log::warning("generateAccessUrl failed for invoice #{$invoice->id}: " . $e->getMessage());
            return null;
        }
    }
}
