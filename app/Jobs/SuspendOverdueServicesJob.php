<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Service;
use App\Services\NotificationService;
use App\Services\ProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SuspendOverdueServicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ProvisioningService $provisioning, NotificationService $notification): void
    {
        $graceDays = (int) \App\Models\Setting::get('billing.suspension_grace_days', 3);

        $overdueInvoices = Invoice::with(['client', 'items.service'])
            ->where('status', 'overdue')
            ->where('date_due', '<=', now()->subDays($graceDays))
            ->get();

        $suspended = 0;

        foreach ($overdueInvoices as $invoice) {
            foreach ($invoice->items as $item) {
                $service = $item->service;
                
                // Verifica se o serviço existe e está ativo (evita suspensão duplicada)
                if (!$service || $service->status !== 'active') continue;

                try {
                    if ($provisioning->suspend($service, "Inadimplência - Fatura #{$invoice->number}")) {
                        $suspended++;
                        
                        // Notifica apenas uma vez por cliente, não por serviço
                        $alreadyNotified = $invoice->notificationLogs()
                            ->where('trigger', 'service_suspended')
                            ->where('created_at', '>=', now()->subDay())
                            ->exists();
                            
                        if (!$alreadyNotified) {
                            $notification->send($invoice->client, 'service_suspended', [
                                'name'         => $invoice->client->name,
                                'product'      => $service->product_name,
                                'invoice'      => $invoice->number,
                                'due_date'     => $invoice->date_due->format('d/m/Y'),
                                'amount'       => number_format($invoice->amount_due, 2, ',', '.'),
                                'action_url'   => url('/cliente/faturas/' . $invoice->id),
                                'message'      => "Seu serviço {$service->product_name} foi suspenso por inadimplência.",
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to suspend service #{$service->id}: " . $e->getMessage());
                }
            }
        }

        Log::info("SuspendOverdueServicesJob: {$suspended} service(s) suspended.");
    }
}
