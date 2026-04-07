<?php

namespace App\Services\ServerModules;

use App\Models\Server;
use App\Models\Service;

/**
 * Factory para resolver o módulo correto baseado no campo `module` do servidor.
 */
class ServerModuleManager
{
    /** @var array<string, class-string<ServerModuleInterface>> */
    protected static array $drivers = [
        'whm'     => WhmModule::class,
        'cpanel'  => WhmModule::class,
        'aapanel' => AAPanelModule::class,
        'btpanel' => AAPanelModule::class,
    ];

    public static function registerDriver(string $name, string $class): void
    {
        static::$drivers[$name] = $class;
    }

    public static function make(Server $server): ServerModuleInterface
    {
        $driver = strtolower($server->module ?? 'whm');

        if (!isset(static::$drivers[$driver])) {
            throw new \RuntimeException(
                "Módulo de servidor '{$driver}' não encontrado. Disponíveis: "
                . implode(', ', array_keys(static::$drivers))
            );
        }

        return new (static::$drivers[$driver])($server);
    }

    public static function forService(Service $service): ServerModuleInterface
    {
        if (!$service->server) {
            throw new \RuntimeException("Serviço #{$service->id} sem servidor configurado.");
        }
        return static::make($service->server);
    }

    public static function drivers(): array
    {
        return array_keys(static::$drivers);
    }
}
