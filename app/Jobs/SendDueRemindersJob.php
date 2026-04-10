<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Setting;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDueRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notification): void
    {
        $reminderDays = [
            (int) Setting::get('billing.reminder_days_1', 3),
            (int) Setting::get('billing.reminder_days_2', 1),
            (int) Setting::get('billing.reminder_days_3', 0), // No dia do vencimento
        ];

        $sent = 0;

        foreach ($reminderDays as $days) {
            $targetDate = now()->addDays($days)->toDateString();
            
            $invoices = Invoice::with('client')
                ->where('status', 'pending')
                ->where('date_due', $targetDate)
                ->whereDoesntHave('notificationLogs', function ($query) use ($days) {
                    $query->where('trigger', 'due_reminder_' . $days)
                          ->where('created_at', '>=', now()->subDay());
                })
                ->get();

            foreach ($invoices as $invoice) {
                $daysText = $days == 0 ? 'hoje' : ($days == 1 ? 'amanhã' : "em {$days} dias");
                
                $notification->send($invoice->client, 'due_reminder_' . $days, [
                    'name' => $invoice->client->name,
                    'invoice_number' => $invoice->number,
                    'amount' => number_format($invoice->amount_due, 2, ',', '.'),
                    'due_date' => $invoice->date_due->format('d/m/Y'),
                    'days_until_due' => $days,
                    'days_text' => $daysText,
                    'action_url' => url('/cliente/faturas/' . $invoice->id),
                    'payment_methods' => $this->getAvailablePaymentMethods(),
                ]);
                
                $sent++;
            }
        }

        Log::info("SendDueRemindersJob: {$sent} reminder(s) sent.");
    }

    private function getAvailablePaymentMethods(): string
    {
        $gateways = \App\Models\Gateway::where('active', true)->pluck('name')->toArray();
        
        if (empty($gateways)) {
            return 'Consulte nosso suporte';
        }
        
        if (count($gateways) == 1) {
            return $gateways[0];
        }
        
        $last = array_pop($gateways);
        return implode(', ', $gateways) . ' ou ' . $last;
    }
}