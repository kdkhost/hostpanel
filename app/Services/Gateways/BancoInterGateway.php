<?php

namespace App\Services\Gateways;

use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Banco Inter — PIX API v2 (OAuth 2.0 mTLS)
 * Docs: https://developers.bancointer.com.br/
 */
class BancoInterGateway extends AbstractGateway
{
    protected function sandboxUrl(): string    { return 'https://cdpj.partners.bancointer.com.br'; }
    protected function productionUrl(): string { return 'https://cdpj.partners.bancointer.com.br'; }

    public function supportsRefund(): bool        { return true; }
    public function supportsPartialRefund(): bool { return true; }
    public function supportsRecurring(): bool     { return false; }

    /* ------------------------------------------------------------------ */
    /*  OAuth2 mTLS Token (cacheado)                                       */
    /* ------------------------------------------------------------------ */

    private function accessToken(): string
    {
        $cacheKey = 'inter_token_' . $this->gateway->id;

        return Cache::remember($cacheKey, 3300, function () {
            $clientId     = $this->setting('client_id');
            $clientSecret = $this->setting('client_secret');
            $certPath     = $this->setting('cert_path', storage_path('app/gateways/inter_cert.crt'));
            $keyPath      = $this->setting('key_path',  storage_path('app/gateways/inter_cert.key'));

            $res = \Illuminate\Support\Facades\Http::withOptions([
                'cert'    => $certPath,
                'ssl_key' => $keyPath,
                'verify'  => true,
            ])->asForm()->post("{$this->baseUrl()}/oauth/v2/token", [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'scope'         => 'cob.write cob.read pix.write pix.read',
            ]);

            if (!$res->successful()) {
                throw new \RuntimeException('Banco Inter OAuth2: ' . $res->body());
            }

            return $res->json('access_token');
        });
    }

    private function httpWithCert(array $extra = [])
    {
        $certPath = $this->setting('cert_path', storage_path('app/gateways/inter_cert.crt'));
        $keyPath  = $this->setting('key_path',  storage_path('app/gateways/inter_cert.key'));

        return \Illuminate\Support\Facades\Http::withOptions([
            'cert'    => $certPath,
            'ssl_key' => $keyPath,
            'verify'  => true,
        ])->withToken($this->accessToken())
          ->withHeaders(array_merge(['Content-Type' => 'application/json'], $extra))
          ->timeout(30);
    }

    /* ------------------------------------------------------------------ */
    /*  Cobrança PIX                                                        */
    /* ------------------------------------------------------------------ */

    public function charge(Invoice $invoice, array $options = []): array
    {
        $amount   = $this->amountWithLateFees($invoice);
        $fee      = $this->setting('pass_fee', false) ? $this->feeAmount($amount) : 0;
        $total    = round($amount + $fee, 2);
        $expHours = (int) $this->setting('expiration_hours', 24);

        $txid = 'inv' . str_pad($invoice->id, 26, '0', STR_PAD_LEFT);

        $payload = [
            'calendario'   => ['expiracao' => $expHours * 3600],
            'devedor'      => $this->buildDevedor($invoice),
            'valor'        => ['original' => number_format($total, 2, '.', '')],
            'chave'        => $this->setting('pix_key'),
            'solicitacaoPagador' => "Fatura #{$invoice->number}",
            'infoAdicionais' => [
                ['nome' => 'invoice_id',  'valor' => (string) $invoice->id],
                ['nome' => 'webhook_url', 'valor' => $this->notificationUrl($invoice)],
            ],
        ];

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

        // Registrar webhook por txid
        $this->registerWebhook($txid, $invoice);

        $res  = $this->httpWithCert()->put("{$this->baseUrl()}/pix/v2/cob/{$txid}", $payload);
        $data = $res->json();

        $this->logRequest('charge', $payload, $data, $res->successful());

        if (!$res->successful()) {
            throw new \RuntimeException('Banco Inter: ' . ($data['title'] ?? json_encode($data)));
        }

        // Obter QR Code
        $qrRes  = $this->httpWithCert()->get("{$this->baseUrl()}/pix/v2/cob/{$txid}/qrcode");
        $qrData = $qrRes->json();

        return [
            'transaction_id'    => $data['txid'] ?? $txid,
            'status'            => $data['status'] ?? 'ATIVA',
            'payment_url'       => null,
            'pix_qrcode'        => $qrData['imagemQrcode'] ?? null,
            'pix_emv'           => $qrData['qrcode'] ?? null,
            'barcode'           => null,
            'barcode_formatted' => null,
            'expires_at'        => $data['calendario']['expiracao'] ?? null,
            'fee'               => $fee,
            'raw'               => array_merge($data, ['qr' => $qrData]),
        ];
    }

    private function registerWebhook(string $txid, Invoice $invoice): void
    {
        try {
            $this->httpWithCert()->put(
                "{$this->baseUrl()}/pix/v2/webhook/{$this->setting('pix_key')}",
                ['webhookUrl' => $this->notificationUrl($invoice)]
            );
        } catch (\Throwable) {}
    }

    private function buildDevedor(Invoice $invoice): array
    {
        $doc  = $this->clientDocument($invoice);
        $name = $this->clientName($invoice);
        return strlen($doc) <= 11
            ? ['cpf' => $doc, 'nome' => $name]
            : ['cnpj' => $doc, 'nome' => $name];
    }

    /* ------------------------------------------------------------------ */
    /*  Reembolso                                                           */
    /* ------------------------------------------------------------------ */

    public function refund(Transaction $transaction, float $amount, string $type = 'full'): array
    {
        $e2eId = $transaction->meta['e2e_id'] ?? $transaction->gateway_transaction_id;
        $refId = 'ref' . str_pad($transaction->id, 10, '0', STR_PAD_LEFT);

        $res  = $this->httpWithCert()->put(
            "{$this->baseUrl()}/pix/v2/pix/{$e2eId}/devolucao/{$refId}",
            ['valor' => number_format($amount, 2, '.', '')]
        );
        $data = $res->json();

        $this->logRequest('refund', ['amount' => $amount], $data, $res->successful());

        return [
            'success' => $res->successful(),
            'message' => $res->successful() ? 'Devolução solicitada.' : ($data['title'] ?? 'Erro'),
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
            'transaction_id' => $pix['endToEndId'] ?? '',
            'txid'           => $pix['txid']       ?? '',
            'status'         => 'paid',
            'amount'         => $pix['valor']      ?? 0,
            'raw'            => $pix,
        ];
    }

    public function isPaid(array $webhookData): bool
    {
        return isset($webhookData['transaction_id']);
    }

    public function getStatus(string $transactionId): array
    {
        $res = $this->httpWithCert()->get("{$this->baseUrl()}/pix/v2/cob/{$transactionId}");
        return $res->json();
    }
}
