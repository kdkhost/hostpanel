<?php

namespace App\Services\ServerModules;

use App\Models\Server;
use App\Models\Service;

class ServerModuleManager
{
    /**
     * Catalogo central de paineis suportados.
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $catalog = [
        'whm' => [
            'label' => 'WHM/cPanel',
            'panel_label' => 'cPanel',
            'driver' => WhmModule::class,
            'default_port' => 2087,
            'requires_username' => true,
            'requires_api_key' => true,
            'requires_password' => false,
            'login_path' => '/',
        ],
        'cpanel' => [
            'label' => 'cPanel',
            'panel_label' => 'cPanel',
            'driver' => WhmModule::class,
            'default_port' => 2087,
            'requires_username' => true,
            'requires_api_key' => true,
            'requires_password' => false,
            'login_path' => '/',
        ],
        'whmsonic' => [
            'label' => 'WHMSonic',
            'panel_label' => 'WHMSonic',
            'driver' => WhmModule::class,
            'default_port' => 2087,
            'requires_username' => true,
            'requires_api_key' => true,
            'requires_password' => false,
            'login_path' => '/',
        ],
        'aapanel' => [
            'label' => 'AAPanel',
            'panel_label' => 'AAPanel',
            'driver' => AAPanelModule::class,
            'default_port' => 8888,
            'requires_username' => false,
            'requires_api_key' => true,
            'requires_password' => false,
            'login_path' => '/login',
        ],
        'btpanel' => [
            'label' => 'BT Panel',
            'panel_label' => 'BT Panel',
            'driver' => AAPanelModule::class,
            'default_port' => 8888,
            'requires_username' => false,
            'requires_api_key' => true,
            'requires_password' => false,
            'login_path' => '/login',
        ],
        'plesk' => [
            'label' => 'Plesk',
            'panel_label' => 'Plesk',
            'driver' => GenericPanelModule::class,
            'default_port' => 8443,
            'requires_username' => true,
            'requires_api_key' => false,
            'requires_password' => true,
            'login_path' => '/login_up.php',
        ],
        'directadmin' => [
            'label' => 'DirectAdmin',
            'panel_label' => 'DirectAdmin',
            'driver' => GenericPanelModule::class,
            'default_port' => 2222,
            'requires_username' => true,
            'requires_api_key' => false,
            'requires_password' => true,
            'login_path' => '/CMD_LOGIN',
        ],
        'ispconfig' => [
            'label' => 'ISPConfig',
            'panel_label' => 'ISPConfig',
            'driver' => GenericPanelModule::class,
            'default_port' => 8080,
            'requires_username' => true,
            'requires_api_key' => false,
            'requires_password' => true,
            'login_path' => '/login/',
        ],
        'blesta' => [
            'label' => 'Blesta',
            'panel_label' => 'Blesta',
            'driver' => GenericPanelModule::class,
            'default_port' => 443,
            'requires_username' => true,
            'requires_api_key' => false,
            'requires_password' => true,
            'login_path' => '/admin/login/',
        ],
        'cyberpanel' => [
            'label' => 'CyberPanel',
            'panel_label' => 'CyberPanel',
            'driver' => GenericPanelModule::class,
            'default_port' => 8090,
            'requires_username' => true,
            'requires_api_key' => false,
            'requires_password' => true,
            'login_path' => '/login',
        ],
        'webuzo' => [
            'label' => 'Webuzo',
            'panel_label' => 'Webuzo',
            'driver' => GenericPanelModule::class,
            'default_port' => 2004,
            'requires_username' => true,
            'requires_api_key' => false,
            'requires_password' => true,
            'login_path' => '/',
        ],
        'hestia' => [
            'label' => 'HestiaCP',
            'panel_label' => 'HestiaCP',
            'driver' => GenericPanelModule::class,
            'default_port' => 8083,
            'requires_username' => true,
            'requires_api_key' => false,
            'requires_password' => true,
            'login_path' => '/login/',
        ],
        'virtualmin' => [
            'label' => 'Virtualmin',
            'panel_label' => 'Virtualmin',
            'driver' => GenericPanelModule::class,
            'default_port' => 10000,
            'requires_username' => true,
            'requires_api_key' => false,
            'requires_password' => true,
            'login_path' => '/session_login.cgi',
        ],
        'none' => [
            'label' => 'Nenhum',
            'panel_label' => 'Painel',
            'driver' => GenericPanelModule::class,
            'default_port' => 80,
            'requires_username' => false,
            'requires_api_key' => false,
            'requires_password' => false,
            'login_path' => '/',
        ],
    ];

    public static function registerDriver(string $name, string $class): void
    {
        $name = static::normalizeName($name);
        static::$catalog[$name] = array_merge(
            static::$catalog[$name] ?? [],
            ['driver' => $class]
        );
    }

    public static function catalog(): array
    {
        return static::$catalog;
    }

    public static function allowedModules(): array
    {
        return array_keys(static::$catalog);
    }

    public static function definition(?string $module): array
    {
        $module = static::normalizeName($module);

        return static::$catalog[$module] ?? static::$catalog['none'];
    }

    public static function label(?string $module, string $fallback = 'Painel'): string
    {
        return static::definition($module)['label'] ?? $fallback;
    }

    public static function panelLabel(?string $module, string $fallback = 'Painel'): string
    {
        return static::definition($module)['panel_label'] ?? $fallback;
    }

    public static function defaultPort(?string $module, int $fallback = 2087): int
    {
        return (int) (static::definition($module)['default_port'] ?? $fallback);
    }

    public static function requiresUsername(?string $module): bool
    {
        return (bool) (static::definition($module)['requires_username'] ?? false);
    }

    public static function requiresApiKey(?string $module): bool
    {
        return (bool) (static::definition($module)['requires_api_key'] ?? false);
    }

    public static function requiresPassword(?string $module): bool
    {
        return (bool) (static::definition($module)['requires_password'] ?? false);
    }

    public static function loginPath(?string $module): string
    {
        return (string) (static::definition($module)['login_path'] ?? '/');
    }

    public static function make(Server $server): ServerModuleInterface
    {
        $driver = static::normalizeName($server->module);
        $definition = static::definition($driver);
        $class = $definition['driver'] ?? null;

        if (!$class || !class_exists($class)) {
            throw new \RuntimeException(
                "Modulo de servidor '{$driver}' nao encontrado. Disponiveis: "
                . implode(', ', static::allowedModules())
            );
        }

        return new $class($server);
    }

    public static function forService(Service $service): ServerModuleInterface
    {
        if (!$service->server) {
            throw new \RuntimeException("Servico #{$service->id} sem servidor configurado.");
        }

        return static::make($service->server);
    }

    public static function drivers(): array
    {
        return static::allowedModules();
    }

    protected static function normalizeName(?string $module): string
    {
        $module = strtolower(trim((string) $module));

        return $module !== '' ? $module : 'none';
    }
}
