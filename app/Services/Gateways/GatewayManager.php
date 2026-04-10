<?php

namespace App\Services\Gateways;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GatewayManager
{
    private static array $drivers = [
        'paghiper'     => PagHiperGateway::class,
        'mercadopago'  => MercadoPagoGateway::class,
        'efirpro'      => EfirProGateway::class,
        'bancointer'   => BancoInterGateway::class,
        'bancobrasil'  => BancoBrasilGateway::class,
        'pagbank'      => PagBankGateway::class,
    ];

    /**
     * Resolve o driver para um gateway pelo seu slug/driver.
     */
    public static function driver(string $driverName): GatewayInterface
    {
        $gateway = Gateway::where('driver', $driverName)->where('active', true)->firstOrFail();
        return static::make($gateway);
    }

    /**
     * Instancia o driver para uma instância de Gateway.
     */
    public static function make(Gateway $gateway): GatewayInterface
    {
        $class = self::$drivers[$gateway->driver]
            ?? throw new \InvalidArgumentException("Driver '{$gateway->driver}' não registrado.");

        return (new $class)->boot($gateway);
    }

    /**
     * Cria uma cobrança e persiste a transação pendente.
     */
    public static function charge(Invoice $invoice, string $driverName, array $options = []): Transaction
    {
        return DB::transaction(function () use ($invoice, $driverName, $options) {
            $driver = static::driver($driverName);
            $result = $driver->charge($invoice, $options);

            return Transaction::create([
                'client_id'              => $invoice->client_id,
                'invoice_id'             => $invoice->id,
                'gateway'                => $driverName,
                'gateway_transaction_id' => $result['transaction_id'],
                'type'                   => 'payment',
                'amount'                 => $invoice->amount_due,
                'fee_amount'             => $result['fee'] ?? 0,
                'currency'               => $invoice->currency ?? 'BRL',
                'status'                 => 'pending',
                'description'            => "Cobrança gerada — Fatura #{$invoice->number}",
                'meta' => [
                    'pix_emv'           => $result['pix_emv'],
                    'pix_qrcode'        => $result['pix_qrcode'],
                    'barcode'           => $result['barcode'],
                    'barcode_formatted' => $result['barcode_formatted'],
                    'payment_url'       => $result['payment_url'],
                    'expires_at'        => $result['expires_at'],
                    'raw_status'        => $result['status'],
                ],
            ]);
        });
    }

    /**
     * Processa confirmação de pagamento vinda do webhook.
     */
    public static function confirmPayment(Invoice $invoice, Transaction $transaction, array $webhookData): void
    {
        if ($invoice->isPaid()) return;

        DB::transaction(function () use ($invoice, $transaction, $webhookData) {
            $transaction->update([
                'status' => 'completed',
                'meta'   => array_merge($transaction->meta ?? [], [
                    'webhook_confirmed' => true,
                    'e2e_id'            => $webhookData['transaction_id'] ?? null,
                    'payer'             => $webhookData['payer'] ?? null,
                ]),
            ]);

            app(BillingService::class)->applyPayment(
                $invoice,
                (float) $transaction->amount,
                $transaction->gateway,
                $transaction->gateway_transaction_id
            );
        });

        Log::info("Payment confirmed via webhook — Invoice #{$invoice->number}, gateway: {$transaction->gateway}");
    }

    /**
     * Executa reembolso (parcial ou total).
     */
    public static function refund(Transaction $transaction, float $amount, string $type = 'full'): array
    {
        $driver = static::driver($transaction->gateway);
        $result = $driver->refund($transaction, $amount, $type);

        if ($result['success']) {
            DB::transaction(function () use ($transaction, $amount, $type) {
                $transaction->update([
                    'refunded_amount' => ($transaction->refunded_amount ?? 0) + $amount,
                    'status'          => $type === 'full' ? 'refunded' : 'partially_refunded',
                ]);

                $invoice = $transaction->invoice;
                if ($invoice && $type === 'full') {
                    $invoice->update(['status' => 'refunded']);
                }
            });
        }

        return $result;
    }

    /**
     * Processa webhook de gateway.
     */
    public static function processWebhook(Gateway $gateway, array $webhookData): void
    {
        try {
            $driver = static::make($gateway);
            
            // Extrai ID da transação do webhook
            $transactionId = $driver->extractTransactionId($webhookData);
            if (!$transactionId) {
                Log::warning("No transaction ID found in webhook data", ['gateway' => $gateway->driver, 'data' => $webhookData]);
                return;
            }
            
            // Busca transação
            $transaction = Transaction::where('gateway_transaction_id', $transactionId)
                ->where('gateway', $gateway->driver)
                ->first();
                
            if (!$transaction) {
                Log::warning("Transaction not found for webhook", ['gateway' => $gateway->driver, 'transaction_id' => $transactionId]);
                return;
            }
            
            // Verifica status do pagamento
            $isPaid = $driver->isPaymentConfirmed($webhookData);
            if (!$isPaid) {
                Log::info("Webhook received but payment not confirmed", ['gateway' => $gateway->driver, 'transaction_id' => $transactionId]);
                return;
            }
            
            // Confirma pagamento
            static::confirmPayment($transaction->invoice, $transaction, $webhookData);
            
    public static function availableDrivers(): array
    {
        return array_keys(self::$drivers);
    }

    public static function registerDriver(string $name, string $class): void
    {
        self::$drivers[$name] = $class;
    }
}
