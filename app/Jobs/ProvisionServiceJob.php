<?php

namespace App\Jobs;

use App\Models\Service;
use App\Services\NotificationService;
use App\Services\ProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProvisionServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(public int $serviceId) {}

    public function handle(ProvisioningService $provisioning, NotificationService $notification): void
    {
        $service = Service::with(['client', 'product', 'server'])->findOrFail($this->serviceId);

        $success = $provisioning->provision($service);

        if ($success) {
            $notification->send($service->client, 'service_active', [
                'name'       => $service->client->name,
                'product'    => $service->product_name,
                'domain'     => $service->domain ?? 'N/A',
                'username'   => $service->username,
                'server'     => $service->server_hostname,
                'ns1'        => $service->nameserver1,
                'ns2'        => $service->nameserver2,
                'action_url' => url('/cliente/servicos/' . $service->id),
                'message'    => "Seu serviço {$service->product_name} foi ativado com sucesso!",
            ]);
        }
    }
}
