<?php

namespace App\Services\Gateways;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;

interface GatewayInterface
{
    public function boot(Gateway $gateway): static;

    /**
     * Gera cobrança avulsa (boleto / PIX / link de pagamento).
     *
     * @return array{
     *   transaction_id: string,
     *   status: string,
     *   payment_url: ?string,
     *   pix_qrcode: ?string,
     *   pix_emv: ?string,
     *   barcode: ?string,
     *   barcode_formatted: ?string,
     *   expires_at: ?string,
     *   fee: float,
     *   raw: array
     * }
     */
    public function charge(Invoice $invoice, array $options = []): array;

    /**
     * Cria cobrança recorrente / assinatura.
     */
    public function chargeRecurring(Invoice $invoice, array $options = []): array;

    /**
     * Cancela / reembolsa uma transação.
     *
     * @param  'full'|'partial'  $type
     */
    public function refund(Transaction $transaction, float $amount, string $type = 'full'): array;

    /**
     * Processa retorno automático (webhook / IPN) do gateway.
     */
    public function handleWebhook(Request $request): array;

    /**
     * Retorna se a cobrança foi paga a partir dos dados do webhook.
     */
    public function isPaid(array $webhookData): bool;

    /**
     * Consulta status de uma transação diretamente no gateway.
     */
    public function getStatus(string $transactionId): array;

    /**
     * Extrai ID da transação dos dados do webhook.
     */
    public function extractTransactionId(array $webhookData): ?string;

    /**
     * Verifica se o pagamento foi confirmado nos dados do webhook.
     */
    public function isPaymentConfirmed(array $webhookData): bool;

    public function supportsRecurring(): bool;
    public function supportsRefund(): bool;
    public function supportsPartialRefund(): bool;
    public function supportsTransparentCheckout(): bool;
}
