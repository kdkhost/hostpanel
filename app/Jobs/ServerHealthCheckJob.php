<?php

namespace App\Jobs;

use App\Models\Server;
use App\Models\ServerHealthLog;
use App\Services\ServerModules\ServerModuleManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ServerHealthCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public function __construct(public ?int $serverId = null) {}

    public function handle(): void
    {
        $servers = $this->serverId
            ? Server::where('id', $this->serverId)->get()
            : Server::where('active', true)->get();

        foreach ($servers as $server) {
            $this->checkServer($server);
        }
    }

    protected function checkServer(Server $server): void
    {
        $network = $this->measureNetwork($server);

        $stats = [];
        try {
            $module = ServerModuleManager::make($server);
            $stats  = $module->getServerStats();
        } catch (\Exception $e) {
            Log::warning("Health check module failed for server #{$server->id}: " . $e->getMessage());
        }

        $cpuUsage  = $stats['cpu_usage'] ?? null;
        $ramUsage  = $stats['ram_usage'] ?? null;
        $diskUsage = $stats['disk_usage'] ?? null;
        $load1     = $stats['load_avg_1'] ?? null;
        $load5     = $stats['load_avg_5'] ?? null;
        $load15    = $stats['load_avg_15'] ?? null;

        // Normalizar para porcentagem (0-100)
        if ($cpuUsage !== null && $cpuUsage > 100) {
            $cpuUsage = min(round($cpuUsage, 2), 100);
        }

        $networkStatus = $this->deriveNetworkStatus($network);
        // Se módulo retornou dados, considerar online mesmo que rede tenha degradação
        if (!empty($stats) && ($cpuUsage !== null || $ramUsage !== null)) {
            $networkStatus = $networkStatus === 'offline' ? 'degraded' : $networkStatus;
        }

        ServerHealthLog::create([
            'server_id'       => $server->id,
            'cpu_usage'       => $cpuUsage,
            'ram_usage'       => $ramUsage,
            'disk_usage'      => $diskUsage,
            'load_avg_1'      => $load1,
            'load_avg_5'      => $load5,
            'load_avg_15'     => $load15,
            'uptime_seconds'  => $stats['uptime_seconds'] ?? null,
            'account_count'   => $stats['account_count'] ?? $server->current_accounts,
            'status'          => $network['online'] ? 'online' : 'offline',
            'latency_ms'      => $network['latency_ms'],
            'packet_loss_pct' => $network['packet_loss_pct'],
            'network_status'  => $networkStatus,
            'checked_at'      => now(),
            'raw_data'        => $stats,
        ]);

        $server->update([
            'status'           => $network['online'] ? 'online' : 'offline',
            'last_check_at'    => now(),
            'cpanel_version'   => $stats['cpanel_version'] ?? $server->cpanel_version,
            'current_accounts' => $stats['account_count'] ?? $server->current_accounts,
        ]);
    }

    protected function measureNetwork(Server $server): array
    {
        $host    = $server->ip_address ?: $server->hostname;
        $port    = $server->port ?: 80;
        $attempts = 3;
        $latencies = [];
        $lost      = 0;

        for ($i = 0; $i < $attempts; $i++) {
            $start = microtime(true);
            $conn  = @fsockopen($host, $port, $errno, $errstr, 3);
            $end   = microtime(true);

            if ($conn) {
                fclose($conn);
                $latencies[] = round(($end - $start) * 1000);
            } else {
                $lost++;
            }
        }

        $latencyMs  = count($latencies) ? (int) round(array_sum($latencies) / count($latencies)) : null;
        $packetLoss = round(($lost / $attempts) * 100, 2);
        $online     = count($latencies) > 0;

        return compact('latencyMs', 'packetLoss', 'online') + [
            'latency_ms'      => $latencyMs,
            'packet_loss_pct' => $packetLoss,
        ];
    }

    protected function deriveNetworkStatus(array $network): string
    {
        if (!$network['online'])                      return 'offline';
        if (($network['packet_loss_pct'] ?? 0) > 30) return 'degraded';
        if (($network['latency_ms'] ?? 0) > 500)     return 'degraded';
        return 'online';
    }

}

