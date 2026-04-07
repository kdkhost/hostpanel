<?php

namespace App\Services\Gateways;

use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * PagBank (PagSeguro) — PIX + Boleto + Cartão (Checkout Transparente)
 * Docs: https://dev.pagbank.uol.com.br/reference
 */
class PagBankGateway extends AbstractGateway
{
    protected function sandboxUrl(): string    { return 'https://sandbox.api.pagseguro.com'; }
    protected function productionUrl(): string { return 'https://api.pagseguro.com'; }

    public function supportsRefund(): bool              { return true; }
    public function supportsPartialRefund(): bool       { return true; }
    public function supportsRecurring(): bool           { return true; }
    public function supportsTransparentCheckout(): bool { return true; }

    private function token(): string
    {
        return $this->sandbox
            ? $this->setting('token_sandbox', '')
            : $this->setting('token', '');
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token()];
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

        $doc = $this->clientDocument($invoice);

        $payload = [
            'reference_id' => (string) $invoice->id,
            'customer' => [
                'name'   => $this->clientName($invoice),
                'email'  => $this->clientEmail($invoice),
                'tax_id' => $doc,
                'phones' => [[
                    'country' => '55',
                    'area'    => substr($this->clientPhone($invoice), 0, 2),
                    'number'  => substr($this->clientPhone($invoice), 2),
                    'type'    => 'MOBILE',
                ]],
            ],
            'items' => [[
                'reference_id' => (string) $invoice->id,
                'name'         => "Fatura #{$invoice->number}",
                'quantity'     => 1,
                'unit_amount'  => $this->centavos($total),
            ]],
            'notification_urls' => [$this->notificationUrl($invoice)],
        ];

        if ($method === 'pix') {
            $expMin = (int) $this->setting('pix_expiration_minutes', 1440);
            $payload['qr_codes'] = [[
                'amount'      => ['value' => $this->centavos($total)],
                'expiration_date' => now()->addMinutes($expMin)->toIso8601String(),
            ]];
        } elseif ($method === 'boleto') {
            $dueDays = (int) $this->setting('due_days', 3);
            $payload['charges'] = [[
                'reference_id'   => (string) $invoice->id,
                'description'    => "Fatura #{$invoice->number}",
                'amount'         => ['value' => $this->centavos($total), 'currency' => 'BRL'],
                'payment_method' => [
                    'type'          => 'BOLETO',
                    'boleto'        => [
                        'due_date'        => now()->addDays($dueDays)->format('Y-m-d'),
                        'instruction_lines' => [
                            'line_1' => 'Pagamento referente à Fatura #' . $invoice->number,
                            'line_2' => 'Não receber após vencimento.',
                        ],
                        'holder' => [
                            'name'    => $this->clientName($invoice),
                            'tax_id'  => $doc,
                            'email'   => $this->clientEmail($invoice),
                            'address' => $this->buildAddress($invoice),
                        ],
                    ],
                ],
                'notification_urls' => [$this->notificationUrl($invoice)],
            ]];
            if ($this->setting('late_fee_enabled', true)) {
                $payload['charges'][0]['payment_method']['boleto']['interest'] = [
                    'value' => number_format($this->setting('interest_daily', 0.033), 2, '.', ''),
                ];
                $payload['charges'][0]['payment_method']['boleto']['fine'] = [
                    'value' => number_format($this->setting('late_fee_percent', 2), 2, '.', ''),
                ];
            }
        } elseif ($method === 'credit_card') {
            $payload['charges'] = [[
                'reference_id'   => (string) $invoice->id,
                'description'    => "Fatura #{$invoice->number}",
                'amount'         => ['value' => $this->centavos($total), 'currency' => 'BRL'],
                'payment_method' => [
                    'type'         => 'CREDIT_CARD',
                    'installments' => $options['installments'] ?? 1,
                    'capture'      => true,
                    'card'         => [
                        'encrypted'    => $options['card_encrypted'] ?? '',
                        'security_code'=> $options['security_code'] ?? '',
                        'holder'       => ['name' => $this->clientName($invoice)],
                        'store'        => false,
                    ],
                ],
                'notification_urls' => [$this->notificationUrl($invoice)],
            ]];
        }

        $res  = $this->http($this->authHeaders())
                    ->post("{$this->baseUrl()}/orders", $payload);
        $data = $res->json();

        $this->logRequest('charge', $payload, $data, $res->successful());

        if (!$res->successful()) {
            $err = $data['error_messages'][0]['description'] ?? ($data['message'] ?? json_encode($data));
            throw new \RuntimeException("PagBank: {$err}");
        }

        $qrCode = $data['qr_codes'][0] ?? null;
        $charge = $data['charges'][0] ?? null;

        return [
            'transaction_id'    => $data['id'] ?? '',
            'status'            => $data['charges'][0]['status'] ?? ($data['qr_codes'][0]['status'] ?? 'WAITING'),
            'payment_url'       => $data['links'][0]['href'] ?? null,
            'pix_qrcode'        => $qrCode['links'][0]['media'] === 'image/png' ? $qrCode['links'][0]['href'] : null,
            'pix_emv'           => $qrCode['text'] ?? null,
            'barcode'           => $charge['payment_response']['barcode'] ?? null,
            'barcode_formatted' => $charge['payment_response']['formatted_barcode'] ?? null,
            'expires_at'        => $qrCode['expiration_date'] ?? ($charge['payment_method']['boleto']['due_date'] ?? null),
            'fee'               => $fee,
            'raw'               => $data,
        ];
    }

    private function buildAddress(Invoice $invoice): array
    {
        $client = $invoice->client;
        return [
            'street'      => $client?->address ?? 'Rua não informada',
            'number'      => $client?->address_number ?? 'S/N',
            'locality'    => $client?->address_neighborhood ?? 'Centro',
            'city'        => $client?->city ?? '',
            'region_code' => $client?->state ?? 'SP',
            'country'     => 'BRA',
            'postal_code' => preg_replace('/\D/', '', $client?->zip ?? '00000000'),
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Recorrência                                                         */
    /* ------------------------------------------------------------------ */

    public function chargeRecurring(Invoice $invoice, array $options = []): array
    {
        $planId = $options['pagbank_plan_id'] ?? null;

        if ($planId) {
            $doc = $this->clientDocument($invoice);
            $payload = [
                'plan'    => ['id' => $planId],
                'customer' => [
                    'name'   => $this->clientName($invoice),
                    'email'  => $this->clientEmail($invoice),
                    'tax_id' => $doc,
                ],
                'payment_method' => [
                    'type' => 'CREDIT_CARD',
                    'card' => [
                        'encrypted' => $options['card_encrypted'] ?? '',
                        'security_code' => $options['security_code'] ?? '',
                        'holder' => ['name' => $this->clientName($invoice)],
                        'store' => true,
                    ],
                ],
                'reference_id' => (string) $invoice->id,
                'notification_urls' => [$this->notificationUrl($invoice)],
            ];

            $res  = $this->http($this->authHeaders())
                        ->post("{$this->baseUrl()}/subscriptions", $payload);
            $data = $res->json();

            $this->logRequest('recurring', $payload, $data, $res->successful());

            return [
                'transaction_id'    => $data['id'] ?? '',
                'status'            => $data['status'] ?? 'PENDING',
                'payment_url'       => null,
                'pix_qrcode'        => null,
                'pix_emv'           => null,
                'barcode'           => null,
                'barcode_formatted' => null,
                'expires_at'        => null,
                'fee'               => 0,
                'raw'               => $data,
            ];
        }

        return $this->charge($invoice, $options);
    }

    /* ------------------------------------------------------------------ */
    /*  Reembolso                                                           */
    /* ------------------------------------------------------------------ */

    public function refund(Transaction $transaction, float $amount, string $type = 'full'): array
    {
        $chargeId = $transaction->meta['charge_id'] ?? $transaction->gateway_transaction_id;
        $payload  = $type === 'partial'
            ? ['amount' => ['value' => $this->centavos($amount)]]
            : [];

        $res  = $this->http($this->authHeaders())
                    ->post("{$this->baseUrl()}/charges/{$chargeId}/cancel", $payload);
        $data = $res->json();

        $this->logRequest('refund', $payload, $data, $res->successful());

        return [
            'success' => $res->successful(),
            'message' => $res->successful() ? 'Reembolso solicitado ao PagBank.' : ($data['error_messages'][0]['description'] ?? 'Erro'),
            'raw'     => $data,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Webhook                                                             */
    /* ------------------------------------------------------------------ */

    public function handleWebhook(Request $request): array
    {
        $payload = $request->all();
        $this->logRequest('webhook', $payload, $payload, true);

        $charge = $payload['charges'][0] ?? null;
        $qr     = $payload['qr_codes'][0] ?? null;

        return [
            'transaction_id' => $payload['id'] ?? '',
            'status'         => $charge['status'] ?? ($qr['status'] ?? ''),
            'amount'         => $charge['amount']['value'] ?? 0,
            'raw'            => $payload,
        ];
    }

    public function isPaid(array $webhookData): bool
    {
        return in_array($webhookData['status'] ?? '', ['PAID', 'AUTHORIZED', 'AVAILABLE']);
    }

    public function getStatus(string $transactionId): array
    {
        $res = $this->http($this->authHeaders())
                    ->get("{$this->baseUrl()}/orders/{$transactionId}");
        return $res->json();
    }
}
