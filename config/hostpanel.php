<?php

return [

    'name' => env('APP_NAME', 'HostPanel'),
    'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo'),
    'locale' => env('APP_LOCALE', 'pt_BR'),

    'currency' => [
        'code'   => env('SYSTEM_CURRENCY', 'BRL'),
        'symbol' => env('SYSTEM_CURRENCY_SYMBOL', 'R$'),
        'decimals' => 2,
        'decimal_separator' => ',',
        'thousands_separator' => '.',
    ],

    'date_format' => env('SYSTEM_DATE_FORMAT', 'd/m/Y'),

    'invoice' => [
        'prefix'          => env('SYSTEM_INVOICE_PREFIX', 'FAT'),
        'late_fee'        => env('SYSTEM_TAX_LATE_FEE', 2),
        'interest_daily'  => env('SYSTEM_TAX_INTEREST_DAILY', 0.033),
        'days_before_due' => [3, 7],
        'days_after_due'  => [1, 3, 7],
    ],

    'session' => [
        'max_simultaneous' => env('SESSION_MAX_SIMULTANEOUS', 5),
    ],

    'installer' => [
        'enabled'   => env('INSTALLER_ENABLED', true),
        'installed' => env('INSTALLED', false),
    ],

    'evolution_api' => [
        'url'      => env('EVOLUTION_API_URL', 'http://localhost:8080'),
        'key'      => env('EVOLUTION_API_KEY', ''),
        'instance' => env('EVOLUTION_API_INSTANCE', 'hostpanel'),
    ],

    'whm' => [
        'api_version' => env('WHM_API_VERSION', 1),
    ],

    'modules' => [
        'domains'    => true,
        'vps'        => true,
        'reseller'   => true,
        'dedicated'  => false,
        'whatsapp'   => true,
        'kanban'     => true,
    ],

    'security' => [
        'rate_limit_login'        => 5,
        'rate_limit_api'          => 60,
        'two_factor_required'     => false,
        'ip_whitelist_enabled'    => false,
        'impersonation_log_reason' => false,
    ],

    'pwa' => [
        'enabled'          => true,
        'name'             => env('APP_NAME', 'HostPanel'),
        'short_name'       => 'HostPanel',
        'theme_color'      => '#1e40af',
        'background_color' => '#ffffff',
        'display'          => 'standalone',
        'orientation'      => 'portrait',
    ],

];
