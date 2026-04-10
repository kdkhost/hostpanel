<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Server;
use App\Services\ServerModules\ServerModuleManager;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class ProvisioningService
{
    public function provision(Service $service): bool
    {
        $service->update(['provision_status' => 'processing']);
        $originalServerId = $service->server_id;

        try {
            $server = $this->resolveServer($service);
            if (!$server) {
                throw new \RuntimeException('Nenhum servidor disponível para provisionamento.');
            }

            // Atualiza servidor apenas temporariamente
            $service->update(['server_id' => $server->id]);
            $service->setRelation('server', $server);

            $module = ServerModuleManager::make($server);
            $result = $module->createAccount($service);

            if (empty($result['success'])) {
                throw new \RuntimeException($result['message'] ?? 'Módulo retornou falha ao criar conta.');
            }

            $username = $result['username'];
            $password = $result['password'];

            // Sucesso - atualiza todos os dados
            $service->update([
                'server_id'          => $server->id,
                'username'           => $username,
                'password_encrypted' => Crypt::encryptString($password),
                'server_hostname'    => $server->hostname,
                'server_ip'          => $server->ip_address,
                'nameserver1'        => $server->nameserver1,
                'nameserver2'        => $server->nameserver2,
                'status'             => 'active',
                'provision_status'   => 'active',
                'provisioned_at'     => now(),
                'registration_date'  => now()->toDateString(),
                'next_due_date'      => $this->calculateNextDueDate($service),
                'provision_log'      => "Conta criada [{$server->module}] em {$server->hostname} em " . now()->format('d/m/Y H:i:s'),
            ]);

            $server->increment('current_accounts');
            Log::info("Service #{$service->id} provisioned via [{$server->module}] on server #{$server->id}");
            return true;

        } catch (\Exception $e) {
            // Rollback: restaura servidor original e limpa dados de provisionamento
            $service->update([
                'server_id'          => $originalServerId,
                'username'           => null,
                'password_encrypted' => null,
                'server_hostname'    => null,
                'server_ip'          => null,
                'nameserver1'        => null,
                'nameserver2'        => null,
                'provision_status'   => 'failed',
                'provision_log'      => $e->getMessage(),
            ]);
            
            Log::error("Provision failed for service #{$service->id}: " . $e->getMessage());
            return false;
        }
    }

    public function suspend(Service $service, string $reason = 'Inadimplência'): bool
    {
        if (!$service->server || !$service->username) return false;

        try {
            ServerModuleManager::make($service->server)->suspendAccount($service, $reason);
            $service->update(['status' => 'suspended']);
            Log::info("Service #{$service->id} suspended. Reason: {$reason}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to suspend service #{$service->id}: " . $e->getMessage());
            return false;
        }
    }

    public function reactivate(Service $service): bool
    {
        if (!$service->server || !$service->username) return false;

        try {
            ServerModuleManager::make($service->server)->unsuspendAccount($service);
            $service->update(['status' => 'active']);
            Log::info("Service #{$service->id} reactivated.");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to reactivate service #{$service->id}: " . $e->getMessage());
            return false;
        }
    }

    public function terminate(Service $service): bool
    {
        try {
            if ($service->server && $service->username) {
                ServerModuleManager::make($service->server)->terminateAccount($service);
                $service->server->decrement('current_accounts');
            }
            $service->update(['status' => 'terminated', 'termination_date' => now()->toDateString()]);
            Log::info("Service #{$service->id} terminated.");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to terminate service #{$service->id}: " . $e->getMessage());
            return false;
        }
    }

    public function changePassword(Service $service, string $newPassword): bool
    {
        if (!$service->server || !$service->username) return false;

        try {
            ServerModuleManager::make($service->server)->changePassword($service, $newPassword);
            $service->update(['password_encrypted' => Crypt::encryptString($newPassword)]);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to change password for service #{$service->id}: " . $e->getMessage());
            return false;
        }
    }

    public function getAutoLoginUrl(Service $service): string
    {
        if (!$service->server || !$service->username) {
            throw new \RuntimeException('Serviço sem servidor ou usuário configurado.');
        }
        return ServerModuleManager::make($service->server)->getAutoLoginUrl($service);
    }

    /** @deprecated Use getAutoLoginUrl() */
    public function getCpanelLoginUrl(Service $service): string
    {
        return $this->getAutoLoginUrl($service);
    }

    protected function resolveServer(Service $service): ?Server
    {
        $product = $service->product;
        if ($product?->serverGroup) {
            return $product->serverGroup->getNextServer();
        }
        return Server::where('active', true)->where('status', 'online')->orderBy('current_accounts')->first();
    }

    protected function generateUsername(string $base): string
    {
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $base));
        $username = substr($username, 0, 8);

        $count = 0;
        $original = $username;
        while (\App\Models\Service::where('username', $username)->exists()) {
            $username = substr($original, 0, 6) . str_pad($count++, 2, '0', STR_PAD_LEFT);
        }

        return $username ?: 'user' . rand(1000, 9999);
    }

    protected function calculateNextDueDate(Service $service): string
    {
        $months = \App\Models\ProductPricing::cycleMonths($service->billing_cycle);
        return now()->addMonths($months)->toDateString();
    }
}
