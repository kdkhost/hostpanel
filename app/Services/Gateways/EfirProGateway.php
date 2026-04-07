<?php

namespace App\Services\Gateways;

use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Efí (ex-Gerencianet) / Efir Pro — PIX API v2
 * Docs: https://dev.efipay.com.br/docs/api-pix/
 */
class EfirProGateway extends AbstractGateway
{
    protected function sandboxUrl(): string    { return 'https://pix-h.api.efipay.com.br'; }
    protected function productionUrl(): string { return 'https://pix.api.efipay.com.br'; }

    public function supportsRefund(): bool           { return true; }
    public function supportsPartialRefund(): bool    { return true; }
    public function supportsRecurring(): bool        { return true; }
    public function supportsTransparentCheckout(): bool { return true; }

    /* ------------------------------------------------------------------ */
    /*  OAuth2 Token (cacheado por 50 min)                                 */
    /* ------------------------------------------------------------------ */

    private function accessToken(): string
    {
        $cacheKey = 'efi_token_' . $this->gateway->id . ($this->sandbox ? '_sandbox' : '');

        return Cache::remember($cacheKey, 3000, function () {
            $clientId     = $this->setting('client_id');
            $clientSecret = $this->setting('client_secret');
            $certPath     = $this->setting('cert_path', storage_path('app/gateways/efi_cert.pem'));

            $baseAuth = "{$this->baseUrl()}/oauth/token";

            $res = \Illuminate\Support\Facades\Http::withOptions([
                'cert'     => $certPath,
                'ssl_key'  => $this->setting('key_path', ''),
                'verify'   => !$this->sandbox,
            ])->withBasicAuth($clientId, $clientSecret)
              ->post($baseAuth, ['grant_type' => 'client_credentials']);

            if (!$res->successful()) {
                throw new \RuntimeException('Efí: Falha ao obter token OAuth2');
            }

            return $res->json('access_token');
        });
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->accessToken()];
    }

    private function httpWithCert(array $extraHeaders = [])
    {
        $certPath = $this->setting('cert_path', storage_path('app/gateways/efi_cert.pem'));

        return \Illuminate\Support\Facades\Http::withOptions([
            'cert'    => $certPath,
            'verify'  => !$this->sandbox,
        ])->withHeaders(array_merge([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ], $this->authHeaders(), $extraHeaders))->timeout(30);
    }

    /* ------------------------------------------------------------------ */
    /*  Cobrança                                                            */
    /* ------------------------------------------------------------------ */

    public function charge(Invoice $invoice, array $options = []): array
    {
        $amount   = $this->amountWithLateFees($invoice);
        $fee      = $this->setting('pass_fee', false) ? $this->feeAmount($amount) : 0;
        $total    = round($amount + $fee, 2);
        $expHours = (int) $this->setting('expiration_hours', 24);

        $txid = 'inv' . str_pad($invoice->id, 10, '0', STR_PAD_LEFT);

        $payload = [
            'calendario' => [
                'expiracao' => $expHours * 3600,
            ],
            'devedor' => [
                'nome' => $this->clientName($invoice),
            ],
            'valor' => [
                'original' => number_format($total, 2, '.', ''),
            ],
            'chave'          => $this->setting('pix_key'),
            'solicitacaoPagador' => "Fatura #{$invoice->number}",
            'infoAdicionais' => [
                ['nome' => 'invoice_id', 'valor' => (string) $invoice->id],
                ['nome' => 'notification_url', 'valor' => $this->notificationUrl($invoice)],
            ],
        ];

        $doc = $this->clientDocument($invoice);
        if (strlen($doc) <= 11) {
            $payload['devedor']['cpf'] = $doc;
        } else {
            $payload['devedor']['cnpj'] = $doc;
        }

        if ($this->setting('late_fee_enabled', true)) {
            $payload['valor']['multa'] = [
                'modalidade' => 2,
                'valorPerc'  => number_format($this->setting('late_fee_percent', 2), 2, '.', ''),
            ];
            $payload['valor']['juros'] = [
                'modalidade' => 2,
                'valorPerc'  => number_format($this->setting('interest_daily', 0.033), 2, '.', ''),
            ];
        }

        $res  = $this->httpWithCert()->put("{$this->baseUrl()}/v2/cob/{$txid}", $payload);
        $data = $res->json();

        $this->logRequest('charge', $payload, $data, $res->successful());

        if (!$res->successful()) {
            throw new \RuntimeException('Efí: ' . ($data['mensagem'] ?? json_encode($data)));
        }

        // Gerar QR Code
        $qrRes  = $this->httpWithCert()->get("{$this->baseUrl()}/v2/loc/{$data['loc']['id']}/qrcode");
        $qrData = $qrRes->json();

        return [
            'transaction_id'    => $data['txid'] ?? $txid,
            'status'            => $data['status'] ?? 'ATIVA',
            'payment_url'       => $qrData['linkVisualizacao'] ?? null,
            'pix_qrcode'        => $qrData['imagemQrcode'] ?? null,
            'pix_emv'           => $qrData['qrcode'] ?? null,
            'barcode'           => null,
            'barcode_formatted' => null,
            'expires_at'        => $data['calendario']['expiracao'] ?? null,
            'fee'               => $fee,
            'raw'               => array_merge($data, ['qr' => $qrData]),
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Reembolso                                                           */
    /* ------------------------------------------------------------------ */

    public function refund(Transaction $transaction, float $amount, string $type = 'full'): array
    {
        $e2eId = $transaction->meta['e2e_id'] ?? $transaction->gateway_transaction_id;
        $refId = 'ref' . str_pad($transaction->id, 10, '0', STR_PAD_LEFT);

        $payload = [
            'valor' => number_format($amount, 2, '.', ''),
        ];

        $res  = $this->httpWithCert()->put("{$this->baseUrl()}/v2/pix/{$e2eId}/devolucao/{$refId}", $payload);
        $data = $res->json();

        $this->logRequest('refund', $payload, $data, $res->successful());

        return [
            'success' => $res->successful(),
            'message' => $res->successful() ? 'Devolução PIX solicitada.' : ($data['mensagem'] ?? 'Erro'),
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

        $pix = $payload['pix'][0] ?? null;
        if (!$pix) return ['status' => 'ignored'];

        return [
            'transaction_id' => $pix['endToEndId']       ?? '',
            'txid'           => $pix['txid']              ?? '',
            'status'         => 'paid',
            'amount'         => $pix['valor']             ?? 0,
            'pix_type'       => $pix['componentesValor']  ?? [],
            'raw'            => $pix,
        ];
    }

    public function isPaid(array $webhookData): bool
    {
        return ($webhookData['status'] ?? '') === 'paid'
            || isset($webhookData['transaction_id']);
    }

    public function getStatus(string $transactionId): array
    {
        $res = $this->httpWithCert()->get("{$this->baseUrl()}/v2/cob/{$transactionId}");
        return $res->json();
    }
}
