<?php

namespace App\Services\ServerModules;

use App\Models\Server;
use App\Models\Service;

interface ServerModuleInterface
{
    public function __construct(Server $server);

    /** Cria conta de hospedagem no servidor */
    public function createAccount(Service $service, array $options = []): array;

    /** Suspende a conta */
    public function suspendAccount(Service $service, string $reason = ''): bool;

    /** Reativa conta suspensa */
    public function unsuspendAccount(Service $service): bool;

    /** Termina / exclui a conta */
    public function terminateAccount(Service $service): bool;

    /** Altera senha da conta */
    public function changePassword(Service $service, string $newPassword): bool;

    /** Retorna URL de auto login válida por tempo limitado */
    public function getAutoLoginUrl(Service $service): string;

    /** Retorna métricas de uso (disco, memória, bandwidth, etc.) */
    public function getUsageStats(Service $service): array;

    /** Testa conectividade com o servidor (ping à API) */
    public function testConnection(): bool;
}
