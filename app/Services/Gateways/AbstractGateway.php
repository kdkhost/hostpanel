<?php

namespace App\Services\Gateways;

use App\Models\Gateway;
use App\Models\GatewayLog;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractGateway implements GatewayInterface
{
    protected Gateway $gateway;
    protected array   $settings = [];
    protected bool    $sandbox  = false;

    public function boot(Gateway $gateway): static
    {
        $this->gateway  = $gateway;
        $this->settings = $gateway->getSettingsDecryptedAttribute();
        $this->sandbox  = (bool) $gateway->test_mode;
        return $this;
    }

    protected function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    protected function baseUrl(): string
    {
        return $this->sandbox ? $this->sandboxUrl() : $this->productionUrl();
    }

    abstract protected function sandboxUrl(): string;
    abstract protected function productionUrl(): string;

    protected function http(array $headers = [])
    {
        return Http::withHeaders(array_merge([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ], $headers))->timeout(30)->retry(2, 500);
    }

    protected function logRequest(string $action, array $payload, array $response, bool $success = true): void
    {
        try {
            GatewayLog::create([
                'gateway_id'       => $this->gateway->id,
                'event_type'       => $action,
                'request_payload'  => $payload,
                'response_payload' => $response,
                'success'          => $success,
            ]);
        } catch (\Throwable $e) {
            Log::error("GatewayLog write failed: " . $e->getMessage());
        }
    }

    protected function centavos(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * URL de notificação única por fatura — enviada em cada requisição de cobrança.
     * Evita configuração global no painel do gateway.
     */
    protected function notificationUrl(Invoice $invoice): string
    {
        return route('webhook.gateway', [
            'driver'     => $this->gateway->driver,
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Calcula valor total com juros por atraso (gateway-level) para cobranças PIX/boleto.
     */
    protected function amountWithLateFees(Invoice $invoice): float
    {
        if (!$invoice->date_due || !$invoice->date_due->isPast()) {
            return (float) $invoice->amount_due;
        }

        $days         = $invoice->date_due->diffInDays(now());
        $dailyRate    = (float) $this->setting('interest_daily', config('hostpanel.invoice.interest_daily', 0.033));
        $lateFeeRate  = (float) $this->setting('late_fee_percent', config('hostpanel.invoice.late_fee', 2));

        $base      = (float) $invoice->subtotal;
        $lateFee   = round($base * $lateFeeRate / 100, 2);
        $interest  = round($base * $dailyRate / 100 * $days, 2);

        return round((float) $invoice->amount_due + $lateFee + $interest, 2);
    }

    /**
     * Calcula taxa do gateway e retorna valor final considerando repasse.
     */
    protected function feeAmount(float $amount): float
    {
        return $this->gateway->calculateFee($amount);
    }

    protected function clientPhone(Invoice $invoice): string
    {
        $phone = preg_replace('/\D/', '', $invoice->client?->phone ?? '');
        return ltrim($phone, '0');
    }

    protected function clientDocument(Invoice $invoice): string
    {
        return preg_replace('/\D/', '', $invoice->client?->document_number ?? '');
    }

    protected function clientName(Invoice $invoice): string
    {
        return $invoice->client?->name ?? 'Cliente';
    }

    protected function clientEmail(Invoice $invoice): string
    {
        return $invoice->client?->email ?? '';
    }

    public function supportsRecurring(): bool        { return false; }
    public function supportsRefund(): bool           { return false; }
    public function supportsPartialRefund(): bool    { return false; }
    public function supportsTransparentCheckout(): bool { return false; }

    public function chargeRecurring(Invoice $invoice, array $options = []): array
    {
        return $this->charge($invoice, array_merge($options, ['recurring' => true]));
    }
}
