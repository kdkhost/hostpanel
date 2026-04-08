<?php

namespace App\Services\ServerModules;

use App\Models\Server;
use App\Models\Service;
use App\Services\WhmService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Wrapper do WhmService existente como módulo padronizado.
 */
class WhmModule extends AbstractServerModule
{
    private WhmService $whm;

    public function __construct(Server $server)
    {
        parent::__construct($server);
        $this->whm = new WhmService($server);
    }

    protected function moduleName(): string
    {
        return ServerModuleManager::label($this->server->module, 'WHM/cPanel');
    }

    public function createAccount(Service $service, array $options = []): array
    {
        $username = $this->generateUsername($service->domain ?? $service->client->name);
        $password = $options['password'] ?? Str::random(16);

        $result = $this->whm->createAccount([
            'username' => $username,
            'domain'   => $service->domain ?? ($username . '.temp.com'),
            'password' => $password,
            'plan'     => $service->product?->cpanel_pkg ?? '',
            'email'    => $service->client->email,
        ]);

        if (($result['result'][0]['status'] ?? 0) !== 1) {
            throw new \RuntimeException(
                'WHM createAccount: ' . ($result['result'][0]['statusmsg'] ?? 'Erro desconhecido')
            );
        }

        $this->log('createAccount', ['username' => $username, 'domain' => $service->domain]);

        return [
            'success'  => true,
            'username' => $username,
            'password' => $password,
            'domain'   => $service->domain ?? $username . '.temp.com',
        ];
    }

    public function suspendAccount(Service $service, string $reason = ''): bool
    {
        try {
            $this->whm->suspendAccount($service->username, $reason);
            return true;
        } catch (\Throwable $e) {
            $this->logError('suspendAccount', $e);
            return false;
        }
    }

    public function unsuspendAccount(Service $service): bool
    {
        try {
            $this->whm->unsuspendAccount($service->username);
            return true;
        } catch (\Throwable $e) {
            $this->logError('unsuspendAccount', $e);
            return false;
        }
    }

    public function terminateAccount(Service $service): bool
    {
        try {
            $this->whm->terminateAccount($service->username);
            return true;
        } catch (\Throwable $e) {
            $this->logError('terminateAccount', $e);
            return false;
        }
    }

    public function changePassword(Service $service, string $newPassword): bool
    {
        try {
            $this->whm->changePassword($service->username, $newPassword);
            return true;
        } catch (\Throwable $e) {
            $this->logError('changePassword', $e);
            return false;
        }
    }

    public function getAutoLoginUrl(Service $service): string
    {
        return $this->whm->getCpanelAutoLoginUrl($service->username);
    }

    public function getUsageStats(Service $service): array
    {
        try {
            $info = $this->whm->getAccountBandwidth($service->username) ?? [];
            return [
                'disk_used_bytes'  => ($info['diskused'] ?? 0) * 1024 * 1024,
                'disk_total_bytes' => ($info['disklimit'] ?? 0) * 1024 * 1024,
                'bandwidth_used'   => $info['bwused'] ?? 0,
                'bandwidth_limit'  => $info['bwlimit'] ?? 0,
                'raw'              => $info,
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    public function testConnection(): bool
    {
        try {
            $result = $this->whm->testConnection();
            return (bool) $result;
        } catch (\Throwable) {
            return false;
        }
    }
}
