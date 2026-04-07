<?php

namespace App\Services\Gateways;

use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;

/**
 * Mercado Pago — PIX + Cartão de Crédito (Checkout Transparente)
 * Docs: https://www.mercadopago.com.br/developers/pt/docs
 */
class MercadoPagoGateway extends AbstractGateway
{
    protected function sandboxUrl(): string    { return 'https://api.mercadopago.com'; }
    protected function productionUrl(): string { return 'https://api.mercadopago.com'; }

    public function supportsRefund(): bool              { return true; }
    public function supportsPartialRefund(): bool       { return true; }
    public function supportsRecurring(): bool           { return true; }
    public function supportsTransparentCheckout(): bool { return true; }

    private function accessToken(): string
    {
        return $this->sandbox
            ? $this->setting('access_token_sandbox', '')
            : $this->setting('access_token', '');
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->accessToken()];
    }

    /* ------------------------------------------------------------------ */
    /*  Cobrança                                                            */
    /* ------------------------------------------------------------------ */

    public function charge(Invoice $invoice, array $options = []): array
    {
        $method = $options['method'] ?? $this->setting('default_method', 'pix');
        $amount = $this->amountWithLateFees($invoice);
        $fee    = $this->setting('pass_fee', false) ? $this->feeAmount($amount) : 0;
        $total  = round($amount + $fee, 2);

        $payload = [
            'transaction_amount' => $total,
            'description'        => "Fatura #{$invoice->number}",
            'external_reference' => (string) $invoice->id,
            'notification_url'   => $this->notificationUrl($invoice),
            'payer' => [
                'email'           => $this->clientEmail($invoice),
                'first_name'      => explode(' ', $this->clientName($invoice))[0],
                'last_name'       => implode(' ', array_slice(explode(' ', $this->clientName($invoice)), 1)) ?: '-',
                'identification'  => [
                    'type'   => strlen($this->clientDocument($invoice)) <= 11 ? 'CPF' : 'CNPJ',
                    'number' => $this->clientDocument($invoice),
                ],
            ],
        ];

        if ($method === 'pix') {
            $payload['payment_method_id'] = 'pix';
            $expireMinutes = (int) $this->setting('pix_expiration_minutes', 1440);
            $payload['date_of_expiration'] = now()->addMinutes($expireMinutes)->toIso8601String();
        } elseif ($method === 'credit_card') {
            $payload['payment_method_id'] = $options['payment_method_id'] ?? '';
            $payload['token']             = $options['card_token'] ?? '';
            $payload['installments']      = $options['installments'] ?? 1;
        }

        $idempotency = 'inv-' . $invoice->id . '-' . time();
        $res  = $this->http(array_merge($this->authHeaders(), ['X-Idempotency-Key' => $idempotency]))
                    ->post("{$this->baseUrl()}/v1/payments", $payload);
        $data = $res->json();

        $this->logRequest('charge', $payload, $data, $res->successful());

        if (!$res->successful()) {
            throw new \RuntimeException('MercadoPago: ' . ($data['message'] ?? json_encode($data)));
        }

        return [
            'transaction_id'    => (string) ($data['id'] ?? ''),
            'status'            => $data['status'] ?? 'pending',
            'payment_url'       => $data['init_point'] ?? null,
            'pix_qrcode'        => $data['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
            'pix_emv'           => $data['point_of_interaction']['transaction_data']['qr_code'] ?? null,
            'barcode'           => null,
            'barcode_formatted' => null,
            'expires_at'        => $data['date_of_expiration'] ?? null,
            'fee'               => $fee,
            'raw'               => $data,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Recorrência (Subscriptions)                                        */
    /* ------------------------------------------------------------------ */

    public function chargeRecurring(Invoice $invoice, array $options = []): array
    {
        $planId = $options['plan_id'] ?? null;

        if ($planId) {
            $payload = [
                'preapproval_plan_id' => $planId,
                'payer_email'         => $this->clientEmail($invoice),
                'external_reference'  => (string) $invoice->id,
                'back_url'            => route('client.invoices.show', $invoice->id),
            ];

            $res  = $this->http($this->authHeaders())
                        ->post("{$this->baseUrl()}/preapproval", $payload);
            $data = $res->json();

            $this->logRequest('recurring', $payload, $data, $res->successful());

            return [
                'transaction_id' => (string) ($data['id'] ?? ''),
                'status'         => $data['status'] ?? 'pending',
                'payment_url'    => $data['init_point'] ?? null,
                'pix_qrcode'     => null,
                'pix_emv'        => null,
                'barcode'        => null,
                'barcode_formatted' => null,
                'expires_at'     => null,
                'fee'            => 0,
                'raw'            => $data,
            ];
        }

        return $this->charge($invoice, $options);
    }

    /* ------------------------------------------------------------------ */
    /*  Reembolso                                                           */
    /* ------------------------------------------------------------------ */

    public function refund(Transaction $transaction, float $amount, string $type = 'full'): array
    {
        $txId    = $transaction->gateway_transaction_id;
        $payload = $type === 'partial' ? ['amount' => $amount] : [];

        $res  = $this->http($this->authHeaders())
                    ->post("{$this->baseUrl()}/v1/payments/{$txId}/refunds", $payload);
        $data = $res->json();

        $this->logRequest('refund', $payload, $data, $res->successful());

        return [
            'success' => $res->successful(),
            'message' => $res->successful() ? 'Reembolso solicitado com sucesso.' : ($data['message'] ?? 'Erro'),
            'raw'     => $data,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Webhook                                                             */
    /* ------------------------------------------------------------------ */

    public function handleWebhook(Request $request): array
    {
        $paymentId = $request->input('data.id') ?? $request->input('id');

        if (!$paymentId || $request->input('type') !== 'payment') {
            return ['status' => 'ignored'];
        }

        $res  = $this->http($this->authHeaders())
                    ->get("{$this->baseUrl()}/v1/payments/{$paymentId}");
        $data = $res->json();

        $this->logRequest('webhook', $request->all(), $data, $res->successful());

        return $data;
    }

    public function isPaid(array $webhookData): bool
    {
        return ($webhookData['status'] ?? '') === 'approved';
    }

    public function getStatus(string $transactionId): array
    {
        $res = $this->http($this->authHeaders())
                    ->get("{$this->baseUrl()}/v1/payments/{$transactionId}");
        return $res->json();
    }
}
