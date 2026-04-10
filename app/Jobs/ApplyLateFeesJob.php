<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Setting;
use App\Services\BillingService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApplyLateFeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(BillingService $billing, NotificationService $notification): void
    {
        $lateFeesEnabled = Setting::get('billing.late_fees_enabled', false);
        if (!$lateFeesEnabled) {
            Log::info('ApplyLateFeesJob: Late fees disabled, skipping.');
            return;
        }

        $lateFeePercent = (float) Setting::get('billing.late_fee_percent', 2.0);
        $interestDaily = (float) Setting::get('billing.interest_daily', 0.033);
        $maxLateFee = (float) Setting::get('billing.max_late_fee_percent', 50.0);
        $graceDays = (int) Setting::get('billing.late_fee_grace_days', 1);

        $overdueInvoices = Invoice::with('client')
            ->where('status', 'overdue')
            ->where('date_due', '<=', now()->subDays($graceDays))
            ->whereColumn('late_fee', '<', 'subtotal') // Evita multa > valor original
            ->get();

        $processed = 0;

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = now()->diffInDays($invoice->date_due);
            
            // Calcula multa (uma vez só)
            $currentLateFee = $invoice->late_fee ?: 0;
            if ($currentLateFee == 0) {
                $lateFeeAmount = round($invoice->subtotal * ($lateFeePercent / 100), 2);
                $invoice->late_fee = $lateFeeAmount;
            }
            
            // Calcula juros diários
            $dailyInterest = round($invoice->subtotal * ($interestDaily / 100), 2);
            $totalInterest = $dailyInterest * $daysOverdue;
            
            // Aplica limite máximo (multa + juros não pode exceder X% do valor original)
            $maxFeeAmount = round($invoice->subtotal * ($maxLateFee / 100), 2);
            $totalFees = $invoice->late_fee + $totalInterest;
            
            if ($totalFees > $maxFeeAmount) {
                $totalInterest = $maxFeeAmount - $invoice->late_fee;
                if ($totalInterest < 0) $totalInterest = 0;
            }
            
            $oldTotal = $invoice->total;
            $invoice->interest = $totalInterest;
            $invoice->total = $invoice->subtotal - $invoice->discount + $invoice->tax + $invoice->late_fee + $invoice->interest;
            $invoice->amount_due = $invoice->total - $invoice->amount_paid - $invoice->credit_applied;
            
            if ($invoice->amount_due < 0) {
                $invoice->amount_due = 0;
            }
            
            $invoice->save();
            
            // Notifica cliente apenas se houve alteração significativa no valor
            if (abs($invoice->total - $oldTotal) >= 0.01) {
                $notification->send($invoice->client, 'late_fee_applied', [
                    'name' => $invoice->client->name,
                    'invoice_number' => $invoice->number,
                    'original_amount' => number_format($invoice->subtotal, 2, ',', '.'),
                    'late_fee' => number_format($invoice->late_fee, 2, ',', '.'),
                    'interest' => number_format($invoice->interest, 2, ',', '.'),
                    'new_total' => number_format($invoice->total, 2, ',', '.'),
                    'days_overdue' => $daysOverdue,
                    'due_date' => $invoice->date_due->format('d/m/Y'),
                    'action_url' => url('/cliente/faturas/' . $invoice->id),
                ]);
            }
            
            $processed++;
        }

        Log::info("ApplyLateFeesJob: {$processed} invoice(s) processed for late fees.");
    }
}