<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\Gateways\GatewayManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class GatewayWebhookController extends Controller
{
    /**
     * Ponto único de entrada para todos os webhooks de gateway.
     *
     * URL: /webhook/{driver}/{invoice_id}
     * Cada cobrança envia sua própria notification_url — sem configuração global.
     */
    public function handle(Request $request, string $driver, int $invoiceId): Response
    {
        Log::info("Webhook recebido — driver: {$driver}, invoice: {$invoiceId}", [
            'ip'      => $request->ip(),
            'payload' => $request->all(),
        ]);

        try {
            $invoice = Invoice::findOrFail($invoiceId);

            if ($invoice->isPaid()) {
                return response('already_paid', 200);
            }

            $gatewayDriver = GatewayManager::driver($driver);
            $webhookData   = $gatewayDriver->handleWebhook($request);

            if (empty($webhookData) || ($webhookData['status'] ?? '') === 'ignored') {
                return response('ignored', 200);
            }

            if ($gatewayDriver->isPaid($webhookData)) {
                $transactionId = $webhookData['transaction_id'] ?? $webhookData['txid'] ?? null;

                $transaction = Transaction::where('invoice_id', $invoice->id)
                    ->where('gateway', $driver)
                    ->where('status', 'pending')
                    ->orderByDesc('created_at')
                    ->first();

                if (!$transaction) {
                    $transaction = Transaction::create([
                        'client_id'              => $invoice->client_id,
                        'invoice_id'             => $invoice->id,
                        'gateway'                => $driver,
                        'gateway_transaction_id' => $transactionId,
                        'type'                   => 'payment',
                        'amount'                 => $invoice->amount_due,
                        'currency'               => $invoice->currency ?? 'BRL',
                        'status'                 => 'pending',
                        'description'            => "Pagamento confirmado via webhook ({$driver})",
                        'meta'                   => ['e2e_id' => $transactionId],
                    ]);
                }

                GatewayManager::confirmPayment($invoice, $transaction, $webhookData);

                // Disparar notificação (email + WhatsApp)
                try {
                    app(\App\Services\InvoiceNotificationService::class)
                        ->sendPaymentConfirmed($invoice->fresh(['client']));
                } catch (\Throwable $e) {
                    Log::warning("Notification failed after payment: " . $e->getMessage());
                }
            }

        } catch (\Throwable $e) {
            Log::error("Webhook error — driver: {$driver}, invoice: {$invoiceId}: " . $e->getMessage());
            return response('error', 500);
        }

        return response('ok', 200);
    }
}
