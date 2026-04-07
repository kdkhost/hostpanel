<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    public function index()
    {
        $servers = Server::where('active', true)
            ->with(['latestHealthLog'])
            ->orderBy('name')
            ->get()
            ->map(function (Server $server) {
                $log = $server->latestHealthLog;
                return [
                    'id'             => $server->id,
                    'name'           => $server->name ?: $server->hostname,
                    'location'       => $server->location ?: $server->datacenter,
                    'status'         => $log?->network_status ?? $server->status ?? 'unknown',
                    'latency_ms'     => $log?->latency_ms,
                    'packet_loss'    => $log?->packet_loss_pct,
                    'cpu'            => $log?->cpu_usage,
                    'ram'            => $log?->ram_usage,
                    'disk'           => $log?->disk_usage,
                    'uptime'         => $log?->uptime_human,
                    'checked_at'     => $log?->checked_at?->diffForHumans(),
                    'checked_at_iso' => $log?->checked_at?->toIso8601String(),
                ];
            });

        $overall = $this->overallStatus($servers);

        return view('status.index', compact('servers', 'overall'));
    }

    public function api(): JsonResponse
    {
        $servers = Server::where('active', true)
            ->with(['latestHealthLog'])
            ->orderBy('name')
            ->get()
            ->map(function (Server $server) {
                $log = $server->latestHealthLog;
                return [
                    'id'          => $server->id,
                    'name'        => $server->name ?: $server->hostname,
                    'location'    => $server->location ?: $server->datacenter,
                    'status'      => $log?->network_status ?? $server->status ?? 'unknown',
                    'latency_ms'  => $log?->latency_ms,
                    'packet_loss' => $log?->packet_loss_pct,
                    'cpu'         => $log?->cpu_usage,
                    'ram'         => $log?->ram_usage,
                    'checked_at'  => $log?->checked_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'overall' => $this->overallStatus($servers),
            'servers' => $servers,
            'updated' => now()->toIso8601String(),
        ]);
    }

    private function overallStatus($servers): string
    {
        if ($servers->isEmpty()) return 'unknown';
        if ($servers->every(fn($s) => $s['status'] === 'online'))    return 'operational';
        if ($servers->every(fn($s) => $s['status'] === 'offline'))   return 'outage';
        if ($servers->contains(fn($s) => $s['status'] === 'offline')) return 'partial_outage';
        return 'degraded';
    }
}
