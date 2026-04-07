<?php

namespace App\Services\Gateways;

use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;

/**
 * PagHiper — Boleto + PIX
 * Docs: https://dev.paghiper.com/
 */
class PagHiperGateway extends AbstractGateway
{
    protected function sandboxUrl(): string    { return 'https://sandbox.paghiper.com'; }
    protected function productionUrl(): string { return 'https://api.paghiper.com'; }

    public function supportsRefund(): bool           { return true; }
    public function supportsPartialRefund(): bool    { return true; }
    public function supportsRecurring(): bool        { return true; }

    /* ------------------------------------------------------------------ */
    /*  Cobrança                                                            */
    /* ------------------------------------------------------------------ */

    public function charge(Invoice $invoice, array $options = []): array
    {
        $method   = $options['method'] ?? $this->setting('default_method', 'pix'); // pix | billet
        $apiKey   = $this->setting('api_key');
        $token    = $this->setting('token');
        $amount   = $this->amountWithLateFees($invoice);
        $fee      = $this->setting('pass_fee', false) ? $this->feeAmount($amount) : 0;
        $total    = round($amount + $fee, 2);
        $dueDays  = max(1, (int) $this->setting('due_days', 3));

        $endpoint = $method === 'pix'
            ? "{$this->baseUrl()}/transaction/pix/create/"
            : "{$this->baseUrl()}/transaction/create/";

        $payload = [
            'apiKey'                => $apiKey,
            'orderid'               => (string) $invoice->number,
            'referencia'            => (string) $invoice->id,
            'notification_url'      => $this->notificationUrl($invoice),  // por requisição
            'redirect_url'          => route('client.invoices.show', $invoice->id),
            'type_bank_slip'        => $method === 'pix' ? 'pix' : 'boletoBancario',
            'days_due_date'         => $dueDays,
            'valor_cents_liquido'   => $this->centavos($total),
            'payer_email'           => $this->clientEmail($invoice),
            'payer_name'            => $this->clientName($invoice),
            'payer_cpf_cnpj'        => $this->clientDocument($invoice),
            'payer_phone'           => $this->clientPhone($invoice),
            'items' => [[
                'description' => "Fatura #{$invoice->number}",
                'quantity'    => 1,
                'item_id'     => (string) $invoice->id,
                'price_cents' => $this->centavos($total),
            ]],
        ];

        if ($method === 'billet') {
            $payload['late_payment_fine']    = (float) $this->setting('late_fee_percent', 2);
            $payload['per_day_interest']     = true;
            $payload['per_day_interest_type'] = 1;
            $payload['per_day_interest_value'] = (float) $this->setting('interest_daily', 0.033);
        }

        $res  = $this->http(['apikey' => $apiKey, 'token' => $token])
                    ->post($endpoint, $payload);
        $data = $res->json();

        $this->logRequest('charge', $payload, $data, $res->successful());

        if (!$res->successful() || empty($data['create_request']['status'])) {
            throw new \RuntimeException('PagHiper: ' . ($data['create_request']['message'] ?? 'Erro desconhecido'));
        }

        $r = $data['create_request'];

        return [
            'transaction_id'   => $r['transaction_id'] ?? '',
            'status'           => $r['status'] ?? 'pending',
            'payment_url'      => $r['url_slip'] ?? $r['url_pix'] ?? null,
            'pix_qrcode'       => $r['pix_image_base64'] ?? null,
            'pix_emv'          => $r['pix_code']['emv'] ?? null,
            'barcode'          => $r['digitable_line'] ?? null,
            'barcode_formatted'=> $r['bar_code_number_to_image'] ?? null,
            'expires_at'       => $r['due_date'] ?? null,
            'fee'              => $fee,
            'raw'              => $r,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Reembolso                                                           */
    /* ------------------------------------------------------------------ */

    public function refund(Transaction $transaction, float $amount, string $type = 'full'): array
    {
        $apiKey = $this->setting('api_key');
        $token  = $this->setting('token');

        $payload = [
            'apiKey'         => $apiKey,
            'token'          => $token,
            'transaction_id' => $transaction->gateway_transaction_id,
        ];

        if ($type === 'partial') {
            $payload['amount_cents'] = $this->centavos($amount);
        }

        $endpoint = "{$this->baseUrl()}/transaction/cancel/";
        $res      = $this->http(['apikey' => $apiKey, 'token' => $token])->post($endpoint, $payload);
        $data     = $res->json();

        $this->logRequest('refund', $payload, $data, $res->successful());

        return [
            'success' => $res->successful(),
            'message' => $data['cancellation_request']['message'] ?? 'Reembolso processado.',
            'raw'     => $data,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Webhook                                                             */
    /* ------------------------------------------------------------------ */

    public function handleWebhook(Request $request): array
    {
        $notificationId = $request->input('notification_id')
                       ?? $request->input('idNotification');

        if (!$notificationId) {
            return ['status' => 'ignored'];
        }

        $apiKey = $this->setting('api_key');
        $token  = $this->setting('token');

        $res  = $this->http(['apikey' => $apiKey, 'token' => $token])
                    ->post("{$this->baseUrl()}/transaction/notification/", [
                        'apiKey'          => $apiKey,
                        'token'           => $token,
                        'notification_id' => $notificationId,
                        'transaction_id'  => $request->input('transaction_id', ''),
                    ]);
        $data = $res->json();

        $this->logRequest('webhook', $request->all(), $data, $res->successful());

        return $data['transaction'] ?? [];
    }

    public function isPaid(array $webhookData): bool
    {
        return in_array($webhookData['status'] ?? '', ['paid', 'reserved']);
    }

    public function getStatus(string $transactionId): array
    {
        $apiKey = $this->setting('api_key');
        $token  = $this->setting('token');

        $res  = $this->http(['apikey' => $apiKey, 'token' => $token])
                    ->post("{$this->baseUrl()}/transaction/get/", [
                        'apiKey'         => $apiKey,
                        'token'          => $token,
                        'transaction_id' => $transactionId,
                    ]);
        return $res->json()['transaction'] ?? [];
    }
}
