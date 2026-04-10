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
            // Gera número único da fatura
            $invoiceNumber = $this->generateInvoiceNumber();
            
            $price = $service->price;
            $setupFee = $service->setup_fee ?? 0;
            $subtotal = $price + $setupFee;

            $invoice = Invoice::create([
                'number'     => $invoiceNumber,
                'client_id'  => $service->client_id,
                'order_id'   => $service->order_id,
                'status'     => 'pending',
                'subtotal'   => $subtotal,
                'total'      => $subtotal,
                'amount_due' => $subtotal,
                'currency'   => $service->currency ?? 'BRL',
                'date_issued'=> now()->toDateString(),
                'date_due'   => now()->addDays(7)->toDateString(),
            ]);

            // Calcula período correto da cobrança
            $periodFrom = $service->next_due_date ?? now();
            $periodTo = $this->calculatePeriodEnd($service, $periodFrom);

            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'service_id'  => $service->id,
                'description' => $service->product_name . ' (' . $this->formatBillingCycle($service->billing_cycle) . ')',
                'amount'      => $price,
                'unit_price'  => $price,
                'period_from' => $periodFrom,
                'period_to'   => $periodTo,
                'type'        => 'service',
            ]);

            // Adiciona taxa de setup se houver
            if ($setupFee > 0) {
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'service_id'  => $service->id,
                    'description' => 'Taxa de Setup - ' . $service->product_name,
                    'amount'      => $setupFee,
                    'unit_price'  => $setupFee,
                    'type'        => 'setup',
                ]);
            }

            return $invoice->fresh(['items', 'client']);
        });
    }

    public function applyPayment(Invoice $invoice, float $amount, string $gateway, ?string $gatewayTransactionId = null): Transaction
    {
        return DB::transaction(function () use ($invoice, $amount, $gateway, $gatewayTransactionId) {
            // Verifica se já existe transação com mesmo gateway_transaction_id (idempotência)
            if ($gatewayTransactionId) {
                $existingTransaction = Transaction::where('gateway_transaction_id', $gatewayTransactionId)
                    ->where('gateway', $gateway)
                    ->first();
                
                if ($existingTransaction) {
                    Log::warning("Duplicate transaction attempt: {$gatewayTransactionId}");
                    return $existingTransaction;
                }
            }

            $transaction = Transaction::create([
                'client_id'              => $invoice->client_id,
                'invoice_id'             => $invoice->id,
                'gateway'                => $gateway,
                'gateway_transaction_id' => $gatewayTransactionId,
                'type'                   => 'payment',
                'amount'                 => $amount,
                'currency'               => $invoice->currency ?? 'BRL',
                'status'                 => 'completed',
                'description'            => "Pagamento da fatura #{$invoice->number}",
            ]);

            $newAmountPaid = $invoice->amount_paid + $amount;
            $newAmountDue  = max(0, $invoice->total - $newAmountPaid - $invoice->credit_applied);

            $status = $newAmountDue <= 0.01 ? 'paid' : 'partially_paid'; // Tolerância de 1 centavo

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

            Log::info("Payment applied to invoice #{$invoice->number}: {$invoice->currency} {$amount} via {$gateway}");

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

    public function addCredit(Client $client, float $amount, string $description, ?int $adminId = null, string $source = 'manual'): Credit
    {
        return DB::transaction(function () use ($client, $amount, $description, $adminId, $source) {
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
                'source'         => $source, // manual, refund, affiliate, bonus, etc
                'created_by'     => $adminId ? 'admin' : 'system',
            ]);
        });
    }

    public function applyLateFees(Invoice $invoice): void
    {
        if (!in_array($invoice->status, ['pending', 'overdue'])) return;
        if (!$invoice->date_due->isPast()) return;

        $daysOverdue  = $invoice->date_due->diffInDays(now());
        $lateFeeRate  = (float) \App\Models\Setting::get('billing.late_fee_percent', 2.0);
        $dailyRate    = (float) \App\Models\Setting::get('billing.interest_daily', 0.033);
        $maxLateFeePercent = (float) \App\Models\Setting::get('billing.max_late_fee_percent', 50.0);

        // Calcula multa (apenas uma vez)
        $lateFee = $invoice->late_fee ?: round($invoice->subtotal * ($lateFeeRate / 100), 2);
        
        // Calcula juros diários
        $interest = round($invoice->subtotal * ($dailyRate / 100) * $daysOverdue, 2);

        // Aplica limite máximo
        $maxFeeAmount = round($invoice->subtotal * ($maxLateFeePercent / 100), 2);
        $totalFees = $lateFee + $interest;
        
        if ($totalFees > $maxFeeAmount) {
            $interest = $maxFeeAmount - $lateFee;
            if ($interest < 0) $interest = 0;
        }

        $newTotal = $invoice->subtotal - $invoice->discount + $invoice->tax + $lateFee + $interest;
        $newAmountDue = $newTotal - $invoice->amount_paid - $invoice->credit_applied;

        $invoice->update([
            'late_fee'   => $lateFee,
            'interest'   => $interest,
            'total'      => $newTotal,
            'amount_due' => max(0, $newAmountDue),
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

    protected function calculatePeriodEnd(Service $service, $periodFrom = null): string
    {
        $months = \App\Models\ProductPricing::cycleMonths($service->billing_cycle);
        if (!$months) {
            throw new \InvalidArgumentException("Invalid billing cycle: {$service->billing_cycle}");
        }
        
        $baseDate = $periodFrom ? \Carbon\Carbon::parse($periodFrom) : now();
        return $baseDate->copy()->addMonths($months)->toDateString();
    }

    protected function calculateNextDueDate(Service $service): string
    {
        $months = \App\Models\ProductPricing::cycleMonths($service->billing_cycle);
        if (!$months) {
            throw new \InvalidArgumentException("Invalid billing cycle: {$service->billing_cycle}");
        }
        
        $base = $service->next_due_date ? \Carbon\Carbon::parse($service->next_due_date) : now();
        return $base->copy()->addMonths($months)->toDateString();
    }
    
    private function generateInvoiceNumber(): string
    {
        $prefix = \App\Models\Setting::get('billing.invoice_prefix', 'INV');
        $year = now()->year;
        
        // Busca o último número do ano atual
        $lastInvoice = Invoice::where('number', 'like', "{$prefix}{$year}%")
            ->orderBy('number', 'desc')
            ->first();
        
        if ($lastInvoice && preg_match("/^{$prefix}{$year}(\d+)$/", $lastInvoice->number, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . $year . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
    
    private function formatBillingCycle(string $cycle): string
    {
        $cycles = [
            'monthly' => 'Mensal',
            'quarterly' => 'Trimestral',
            'semi_annual' => 'Semestral',
            'annual' => 'Anual',
            'biennial' => 'Bienal',
            'triennial' => 'Trienal',
            'one_time' => 'Pagamento Único',
        ];
        
        return $cycles[$cycle] ?? ucfirst($cycle);
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
