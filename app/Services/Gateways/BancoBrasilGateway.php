<?php

namespace App\Services\Gateways;

use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Banco do Brasil — PIX API
 * Docs: https://developers.bb.com.br/docs/pix
 */
class BancoBrasilGateway extends AbstractGateway
{
    protected function sandboxUrl(): string    { return 'https://api.hm.bb.com.br'; }
    protected function productionUrl(): string { return 'https://api.bb.com.br'; }

    public function supportsRefund(): bool        { return true; }
    public function supportsPartialRefund(): bool { return true; }
    public function supportsRecurring(): bool     { return false; }

    /* ------------------------------------------------------------------ */
    /*  OAuth2 Token                                                        */
    /* ------------------------------------------------------------------ */

    private function accessToken(): string
    {
        $cacheKey = 'bb_token_' . $this->gateway->id . ($this->sandbox ? '_h' : '');

        return Cache::remember($cacheKey, 3300, function () {
            $clientId     = $this->setting('client_id');
            $clientSecret = $this->setting('client_secret');
            $grantType    = 'client_credentials';

            $res = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post("{$this->baseUrl()}/oauth/token", [
                    'grant_type' => $grantType,
                    'scope'      => 'cob.write cob.read pix.write pix.read cobv.read cobv.write',
                ]);

            if (!$res->successful()) {
                throw new \RuntimeException('Banco do Brasil OAuth2: ' . $res->body());
            }

            return $res->json('access_token');
        });
    }

    private function httpBB(array $extra = [])
    {
        $devApp = $this->sandbox
            ? ['gw-dev-app-key' => $this->setting('developer_app_key_sandbox', '')]
            : [];

        return \Illuminate\Support\Facades\Http::withToken($this->accessToken())
            ->withHeaders(array_merge([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ], $devApp, $extra))
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
        $expSecs  = (int) $this->setting('expiration_hours', 24) * 3600;

        $doc  = $this->clientDocument($invoice);
        $txid = 'inv' . str_pad($invoice->id, 26, '0', STR_PAD_LEFT);

        $payload = [
            'calendario'   => ['expiracao' => $expSecs],
            'devedor'      => strlen($doc) <= 11
                ? ['cpf' => $doc, 'nome' => $this->clientName($invoice)]
                : ['cnpj' => $doc, 'nome' => $this->clientName($invoice)],
            'valor'        => ['original' => number_format($total, 2, '.', '')],
            'chave'        => $this->setting('pix_key'),
            'solicitacaoPagador' => "Fatura #{$invoice->number}",
            'infoAdicionais' => [
                ['nome' => 'invoice_id',       'valor' => (string) $invoice->id],
                ['nome' => 'notification_url',  'valor' => $this->notificationUrl($invoice)],
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

        $devKey = $this->sandbox
            ? '?gw-dev-app-key=' . $this->setting('developer_app_key_sandbox', '')
            : '';

        $res  = $this->httpBB()->put("{$this->baseUrl()}/pix/v2/cob/{$txid}{$devKey}", $payload);
        $data = $res->json();

        $this->logRequest('charge', $payload, $data, $res->successful());

        if (!$res->successful()) {
            throw new \RuntimeException('Banco do Brasil: ' . ($data['mensagem'] ?? json_encode($data)));
        }

        // Obter QR Code
        $locId  = $data['loc']['id'] ?? null;
        $qrData = [];
        if ($locId) {
            $qrRes  = $this->httpBB()->get("{$this->baseUrl()}/pix/v2/loc/{$locId}/qrcode{$devKey}");
            $qrData = $qrRes->json();
        }

        return [
            'transaction_id'    => $data['txid'] ?? $txid,
            'status'            => $data['status'] ?? 'ATIVA',
            'payment_url'       => $qrData['linkVisualizacao'] ?? null,
            'pix_qrcode'        => $qrData['imagemQrcode'] ?? null,
            'pix_emv'           => $qrData['qrcode'] ?? null,
            'barcode'           => null,
            'barcode_formatted' => null,
            'expires_at'        => null,
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
        $devKey = $this->sandbox
            ? '?gw-dev-app-key=' . $this->setting('developer_app_key_sandbox', '')
            : '';

        $res  = $this->httpBB()->put(
            "{$this->baseUrl()}/pix/v2/pix/{$e2eId}/devolucao/{$refId}{$devKey}",
            ['valor' => number_format($amount, 2, '.', '')]
        );
        $data = $res->json();

        $this->logRequest('refund', ['amount' => $amount, 'e2e' => $e2eId], $data, $res->successful());

        return [
            'success' => $res->successful(),
            'message' => $res->successful() ? 'Devolução PIX solicitada ao Banco do Brasil.' : ($data['mensagem'] ?? 'Erro'),
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
        $devKey = $this->sandbox
            ? '?gw-dev-app-key=' . $this->setting('developer_app_key_sandbox', '')
            : '';
        $res = $this->httpBB()->get("{$this->baseUrl()}/pix/v2/cob/{$transactionId}{$devKey}");
        return $res->json();
    }
}
