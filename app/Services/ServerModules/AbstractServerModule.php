<?php

namespace App\Services\ServerModules;

use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractServerModule implements ServerModuleInterface
{
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    protected function baseUrl(): string
    {
        $scheme = $this->server->secure ? 'https' : 'http';
        return "{$scheme}://{$this->server->hostname}:{$this->server->port}";
    }

    protected function http(array $extraHeaders = [])
    {
        return Http::withOptions(['verify' => false])
            ->withHeaders(array_merge([
                'Accept' => 'application/json',
            ], $extraHeaders))
            ->timeout(30);
    }

    protected function log(string $action, array $context = []): void
    {
        Log::info("[{$this->moduleName()}] {$action}", array_merge([
            'server_id'   => $this->server->id,
            'server_host' => $this->server->hostname,
        ], $context));
    }

    protected function logError(string $action, \Throwable $e): void
    {
        Log::error("[{$this->moduleName()}] {$action}: " . $e->getMessage(), [
            'server_id' => $this->server->id,
        ]);
    }

    abstract protected function moduleName(): string;

    protected function generateUsername(string $base): string
    {
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $base));
        $username = substr($username, 0, 8);
        $original = $username;
        $count    = 0;
        while (\App\Models\Service::where('username', $username)->exists()) {
            $username = substr($original, 0, 6) . str_pad($count++, 2, '0', STR_PAD_LEFT);
        }
        return $username ?: 'usr' . rand(1000, 9999);
    }
}
