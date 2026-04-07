<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Coupon;
use App\Models\Credit;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public function generateInvoiceForService(Service $service): Invoice
    {
        return DB::transaction(function () use ($service) {
            $price = $service->price;

            $invoice = Invoice::create([
                'client_id'  => $service->client_id,
                'order_id'   => $service->order_id,
                'status'     => 'pending',
                'subtotal'   => $price,
                'total'      => $price,
                'amount_due' => $price,
                'currency'   => $service->currency,
                'date_issued'=> now()->toDateString(),
                'date_due'   => now()->addDays(7)->toDateString(),
            ]);

            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'service_id'  => $service->id,
                'description' => $service->product_name . ' (' . $service->billing_cycle . ')',
                'amount'      => $price,
                'unit_price'  => $price,
                'period_from' => $service->next_due_date ?? now(),
                'period_to'   => $this->calculatePeriodEnd($service),
                'type'        => 'service',
            ]);

            return $invoice->fresh(['items', 'client']);
        });
    }

    public function applyPayment(Invoice $invoice, float $amount, string $gateway, ?string $gatewayTransactionId = null): Transaction
    {
        return DB::transaction(function () use ($invoice, $amount, $gateway, $gatewayTransactionId) {
            $transaction = Transaction::create([
                'client_id'              => $invoice->client_id,
                'invoice_id'             => $invoice->id,
                'gateway'                => $gateway,
                'gateway_transaction_id' => $gatewayTransactionId,
                'type'                   => 'payment',
                'amount'                 => $amount,
                'currency'               => $invoice->currency,
                'status'                 => 'completed',
                'description'            => "Pagamento da fatura #{$invoice->number}",
            ]);

            $newAmountPaid = $invoice->amount_paid + $amount;
            $newAmountDue  = max(0, $invoice->total - $newAmountPaid);

            $status = $newAmountDue <= 0 ? 'paid' : 'partially_paid';

            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'amount_due'  => $newAmountDue,
                'status'      => $status,
                'date_paid'   => $status === 'paid' ? now() : $invoice->date_paid,
                'gateway'     => $gateway,
                'payment_method' => $gateway,
            ]);

            if ($status === 'paid') {
                $this->handlePaidInvoice($invoice);
            }

            Log::info("Payment applied to invoice #{$invoice->number}: R$ {$amount} via {$gateway}");

            return $transaction;
        });
    }

    public function applyCreditToInvoice(Invoice $invoice, float $amount): void
    {
        DB::transaction(function () use ($invoice, $amount) {
            $client  = $invoice->client;
            $amount  = min($amount, $client->credit_balance, $invoice->amount_due);

            if ($amount <= 0) return;

            $balanceBefore = $client->credit_balance;
            $balanceAfter  = $balanceBefore - $amount;

            $client->decrement('credit_balance', $amount);

            Credit::create([
                'client_id'      => $client->id,
                'invoice_id'     => $invoice->id,
                'type'           => 'use',
                'amount'         => $amount,
                'description'    => "Crédito aplicado à fatura #{$invoice->number}",
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
            ]);

            $newAmountDue = $invoice->amount_due - $amount;
            $invoice->update([
                'credit_applied' => $invoice->credit_applied + $amount,
                'amount_due'     => max(0, $newAmountDue),
                'status'         => $newAmountDue <= 0 ? 'paid' : $invoice->status,
                'date_paid'      => $newAmountDue <= 0 ? now() : $invoice->date_paid,
            ]);

            if ($newAmountDue <= 0) {
                $this->handlePaidInvoice($invoice->fresh());
            }
        });
    }

    public function addCredit(Client $client, float $amount, string $description, ?int $adminId = null): Credit
    {
        return DB::transaction(function () use ($client, $amount, $description, $adminId) {
            $balanceBefore = $client->credit_balance;
            $balanceAfter  = $balanceBefore + $amount;

            $client->increment('credit_balance', $amount);

            return Credit::create([
                'client_id'      => $client->id,
                'type'           => 'add',
                'amount'         => $amount,
                'description'    => $description,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'admin_id'       => $adminId,
            ]);
        });
    }

    public function applyLateFees(Invoice $invoice): void
    {
        if (!in_array($invoice->status, ['pending', 'overdue'])) return;
        if (!$invoice->date_due->isPast()) return;

        $daysOverdue  = $invoice->date_due->diffInDays(now());
        $lateFeeRate  = config('hostpanel.invoice.late_fee', 2);
        $dailyRate    = config('hostpanel.invoice.interest_daily', 0.033);

        $lateFee  = round($invoice->subtotal * ($lateFeeRate / 100), 2);
        $interest = round($invoice->subtotal * ($dailyRate / 100) * $daysOverdue, 2);

        $invoice->update([
            'late_fee'   => $lateFee,
            'interest'   => $interest,
            'total'      => $invoice->subtotal - $invoice->discount + $lateFee + $interest,
            'amount_due' => $invoice->subtotal - $invoice->discount + $lateFee + $interest - $invoice->amount_paid,
            'status'     => 'overdue',
        ]);
    }

    protected function handlePaidInvoice(Invoice $invoice): void
    {
        $services = Service::where('order_id', $invoice->order_id)
            ->orWhereHas('invoiceItems', fn($q) => $q->where('invoice_id', $invoice->id))
            ->get();

        foreach ($services as $service) {
            if ($service->status === 'suspended') {
                app(ProvisioningService::class)->reactivate($service);
            }
            $nextDue = $this->calculateNextDueDate($service);
            $service->update(['next_due_date' => $nextDue]);
        }
    }

    protected function calculatePeriodEnd(Service $service): string
    {
        $months = \App\Models\ProductPricing::cycleMonths($service->billing_cycle);
        return now()->addMonths($months)->toDateString();
    }

    protected function calculateNextDueDate(Service $service): string
    {
        $months = \App\Models\ProductPricing::cycleMonths($service->billing_cycle);
        $base   = $service->next_due_date ?? now();
        return $base->addMonths($months)->toDateString();
    }

    /**
     * Inicia cobrança via gateway e envia notificação (email + WhatsApp).
     */
    public function initiatePayment(Invoice $invoice, string $driverName, array $options = []): array
    {
        $transaction = \App\Services\Gateways\GatewayManager::charge($invoice, $driverName, $options);

        $meta = $transaction->meta ?? [];

        // Enviar notificação conforme tipo de cobrança
        try {
            $notifier = app(\App\Services\InvoiceNotificationService::class);

            if (!empty($meta['pix_emv'])) {
                $notifier->sendPixGenerated($invoice->fresh(['client']), $transaction);
            } elseif (!empty($meta['barcode'])) {
                $notifier->sendBoletoGenerated($invoice->fresh(['client']), $transaction);
            } else {
                $notifier->sendInvoiceCreated($invoice->fresh(['client']));
            }
        } catch (\Throwable $e) {
            Log::warning("initiatePayment notification failed: " . $e->getMessage());
        }

        return [
            'transaction_id'    => $transaction->gateway_transaction_id,
            'payment_url'       => $meta['payment_url']       ?? null,
            'pix_emv'           => $meta['pix_emv']           ?? null,
            'pix_qrcode'        => $meta['pix_qrcode']        ?? null,
            'barcode'           => $meta['barcode']           ?? null,
            'barcode_formatted' => $meta['barcode_formatted'] ?? null,
            'expires_at'        => $meta['expires_at']        ?? null,
            'message'           => 'Cobrança gerada com sucesso!',
        ];
    }

    public function generateInvoicesForDueServices(): int
    {
        $count = 0;
        $daysAhead = 7;

        $services = Service::with('client')
            ->where('status', 'active')
            ->where('billing_cycle', '!=', 'one_time')
            ->where('next_due_date', '<=', now()->addDays($daysAhead)->toDateString())
            ->whereDoesntHave('invoiceItems', function ($q) {
                $q->whereHas('invoice', fn($iq) =>
                    $iq->whereIn('status', ['pending', 'paid', 'partially_paid'])
                );
            })
            ->get();

        foreach ($services as $service) {
            try {
                $this->generateInvoiceForService($service);
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to generate invoice for service #{$service->id}: " . $e->getMessage());
            }
        }

        return $count;
    }
}
