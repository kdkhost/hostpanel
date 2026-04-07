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
                if (!$service || $service->status !== 'active') continue;

                if ($provisioning->suspend($service, "Inadimplência - Fatura #{$invoice->number}")) {
                    $suspended++;
                    $notification->send($invoice->client, 'service_suspended', [
                        'name'         => $invoice->client->name,
                        'product'      => $service->product_name,
                        'invoice'      => $invoice->number,
                        'due_date'     => $invoice->date_due->format('d/m/Y'),
                        'action_url'   => url('/cliente/faturas/' . $invoice->id),
                        'message'      => "Seu serviço {$service->product_name} foi suspenso por inadimplência.",
                    ]);
                }
            }
        }

        Log::info("SuspendOverdueServicesJob: {$suspended} service(s) suspended.");
    }
}
