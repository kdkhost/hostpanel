<?php

namespace App\Services\ServerModules;

use App\Models\Service;

class GenericPanelModule extends AbstractServerModule
{
    protected function moduleName(): string
    {
        return ServerModuleManager::label($this->server->module, 'Painel');
    }

    protected function panelUrl(): string
    {
        $baseUrl = rtrim($this->baseUrl(), '/');
        $path = ServerModuleManager::loginPath($this->server->module);

        if ($path === '/' || $path === '') {
            return $baseUrl . '/';
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }

    protected function unsupported(string $action): \RuntimeException
    {
        return new \RuntimeException(
            "O modulo {$this->moduleName()} ainda nao possui {$action} automatico implementado."
        );
    }

    public function createAccount(Service $service, array $options = []): array
    {
        throw $this->unsupported('provisionamento');
    }

    public function suspendAccount(Service $service, string $reason = ''): bool
    {
        throw $this->unsupported('suspensao');
    }

    public function unsuspendAccount(Service $service): bool
    {
        throw $this->unsupported('reativacao');
    }

    public function terminateAccount(Service $service): bool
    {
        throw $this->unsupported('encerramento');
    }

    public function changePassword(Service $service, string $newPassword): bool
    {
        throw $this->unsupported('alteracao de senha');
    }

    public function getAutoLoginUrl(Service $service): string
    {
        return $this->panelUrl();
    }

    public function getUsageStats(Service $service): array
    {
        return [];
    }

    public function testConnection(): bool
    {
        $statuses = [200, 201, 202, 204, 301, 302, 401, 403, 405];
        $host = $this->server->hostname ?: $this->server->ip_address;
        $port = $this->server->port ?: ServerModuleManager::defaultPort($this->server->module, 80);

        foreach ([$this->panelUrl(), rtrim($this->baseUrl(), '/') . '/'] as $url) {
            try {
                $response = $this->http()->withoutRedirecting()->get($url);
                if (in_array($response->status(), $statuses, true)) {
                    return true;
                }
            } catch (\Throwable) {
            }
        }

        $socket = @fsockopen($host, $port, $errorNumber, $errorMessage, 5);
        if ($socket) {
            fclose($socket);
            return true;
        }

        return false;
    }
}
