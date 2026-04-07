<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ServerHealthCheckJob;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\ServerHealthLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $servers = Server::with(['group', 'latestHealthLog'])
                ->withCount('services')
                ->get();
            return response()->json($servers);
        }
        return view('admin.servers.index');
    }

    public function show(Server $server)
    {
        $server->load(['group', 'latestHealthLog']);
        $server->loadCount('services');
        $healthHistory = ServerHealthLog::where('server_id', $server->id)
            ->orderByDesc('checked_at')->limit(24)->get();
        $services = \App\Models\Service::where('server_id', $server->id)
            ->with('client:id,name,email')
            ->orderByDesc('created_at')
            ->get();
        return view('admin.servers.show', compact('server', 'healthHistory', 'services'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'hostname'   => 'required|string',
            'ip_address' => 'required|ip',
            'port'       => 'required|integer|min:1|max:65535',
            'type'       => 'required|in:shared,reseller,vps,dedicated,other',
            'module'     => 'required|in:whm,cpanel,aapanel,btpanel,plesk,directadmin,ispconfig,none',
            'username'   => 'nullable|string',
            'api_key'    => 'required|string',
        ]);

        $server = Server::create($request->only([
            'server_group_id', 'name', 'hostname', 'ip_address', 'ip_address_secondary',
            'port', 'type', 'module', 'username', 'api_key', 'api_hash', 'max_accounts',
            'secure', 'active', 'nameserver1', 'nameserver2', 'nameserver3',
        ]));

        dispatch(new ServerHealthCheckJob($server->id));

        return response()->json(['message' => 'Servidor cadastrado com sucesso!', 'server' => $server], 201);
    }

    public function update(Request $request, Server $server): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'hostname'   => 'required|string',
            'ip_address' => 'required|ip',
        ]);

        $data = $request->only([
            'server_group_id', 'name', 'hostname', 'ip_address', 'ip_address_secondary',
            'port', 'type', 'module', 'username', 'max_accounts', 'secure', 'active',
            'nameserver1', 'nameserver2', 'nameserver3',
        ]);

        if ($request->filled('api_key')) {
            $data['api_key'] = $request->api_key;
        }

        $server->update($data);
        return response()->json(['message' => 'Servidor atualizado!', 'server' => $server->fresh()]);
    }

    public function destroy(Server $server): JsonResponse
    {
        if ($server->services()->where('status', 'active')->count() > 0) {
            return response()->json(['message' => 'Servidor possui serviços ativos. Migre-os antes de remover.'], 422);
        }
        $server->delete();
        return response()->json(['message' => 'Servidor removido com sucesso!']);
    }

    public function healthCheck(Server $server): JsonResponse
    {
        dispatch(new ServerHealthCheckJob($server->id));
        return response()->json(['message' => 'Health check iniciado!']);
    }

    public function healthStatus(Server $server): JsonResponse
    {
        $latest = $server->latestHealthLog;
        return response()->json([
            'server' => $server->only(['id', 'name', 'hostname', 'status', 'last_check_at']),
            'health' => $latest,
            'cpu'          => $latest?->cpu_usage,
            'ram'          => $latest?->ram_usage,
            'disk'         => $latest?->disk_usage,
            'load_avg_1'   => $latest?->load_avg_1,
            'load_avg_5'   => $latest?->load_avg_5,
            'load_avg_15'  => $latest?->load_avg_15,
            'latency_ms'   => $latest?->latency_ms,
            'packet_loss'  => $latest?->packet_loss_pct,
            'network_in'   => $latest?->network_in_mbps,
            'network_out'  => $latest?->network_out_mbps,
            'network_status'=> $latest?->network_status ?? $server->status,
            'uptime'       => $latest?->uptime_human,
            'checked_at'   => $latest?->checked_at?->diffForHumans(),
        ]);
    }

    public function healthHistory(Server $server): JsonResponse
    {
        $history = ServerHealthLog::where('server_id', $server->id)
            ->orderBy('checked_at')
            ->limit(48)
            ->get(['checked_at', 'cpu_usage', 'ram_usage', 'disk_usage',
                   'load_avg_1', 'latency_ms', 'packet_loss_pct', 'network_status']);

        return response()->json([
            'labels'      => $history->map(fn($h) => $h->checked_at->format('H:i'))->values(),
            'cpu'         => $history->pluck('cpu_usage')->map(fn($v) => (float)($v ?? 0))->values(),
            'ram'         => $history->pluck('ram_usage')->map(fn($v) => (float)($v ?? 0))->values(),
            'disk'        => $history->pluck('disk_usage')->map(fn($v) => (float)($v ?? 0))->values(),
            'latency'     => $history->pluck('latency_ms')->values(),
            'packet_loss' => $history->pluck('packet_loss_pct')->map(fn($v) => (float)($v ?? 0))->values(),
        ]);
    }

    public function testConnectivity(Server $server): JsonResponse
    {
        try {
            $result = \App\Services\ServerModules\ServerModuleManager::make($server)->testConnection();
            return response()->json([
                'success' => $result['success'] ?? true,
                'message' => $result['message'] ?? 'Conexão estabelecida com sucesso!',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function groups(): JsonResponse
    {
        return response()->json(ServerGroup::withCount('servers')->get());
    }

    public function storeGroup(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:100', 'fill_type' => 'required|in:sequential,least_used,random']);
        $group = ServerGroup::create($request->only(['name', 'description', 'fill_type', 'active']));
        return response()->json(['message' => 'Grupo criado!', 'group' => $group], 201);
    }
}
