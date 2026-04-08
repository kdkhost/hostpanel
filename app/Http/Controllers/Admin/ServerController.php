<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ServerHealthCheckJob;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\ServerHealthLog;
use App\Services\ServerModules\ServerModuleManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    protected function normalizeModule(?string $module): string
    {
        $module = strtolower(trim((string) $module));

        return match ($module) {
            '' => 'none',
            default => $module,
        };
    }

    protected function serverRules(Request $request, bool $updating = false): array
    {
        $module = $this->normalizeModule($request->input('module'));
        $requiresUsername = ServerModuleManager::requiresUsername($module);
        $requiresApiKey = ServerModuleManager::requiresApiKey($module);
        $requiresPassword = ServerModuleManager::requiresPassword($module);

        return [
            'server_group_id' => 'nullable|exists:server_groups,id',
            'name' => 'required|string|max:100',
            'hostname' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'ip_address_secondary' => 'nullable|ip',
            'port' => 'required|integer|min:1|max:65535',
            'type' => 'required|in:shared,reseller,vps,dedicated,other',
            'module' => 'required|in:' . implode(',', ServerModuleManager::allowedModules()),
            'username' => $requiresUsername ? 'required|string|max:100' : 'nullable|string|max:100',
            'api_key' => $requiresApiKey
                ? ($updating ? 'nullable|string' : 'required|string')
                : 'nullable|string',
            'password' => $requiresPassword
                ? ($updating ? 'nullable|string' : 'required|string')
                : 'nullable|string',
            'max_accounts' => 'nullable|integer|min:0',
            'secure' => 'nullable|boolean',
            'active' => 'nullable|boolean',
            'nameserver1' => 'nullable|string|max:255',
            'nameserver2' => 'nullable|string|max:255',
            'nameserver3' => 'nullable|string|max:255',
            'datacenter'  => 'nullable|string|max:100',
            'location'    => 'nullable|string|max:100',
            'api_hash'    => 'nullable|string',
        ];
    }

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
            ->orderByDesc('checked_at')
            ->limit(24)
            ->get();

        $services = \App\Models\Service::where('server_id', $server->id)
            ->with('client:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.servers.show', compact('server', 'healthHistory', 'services'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate($this->serverRules($request));

        $data = $request->only([
            'server_group_id', 'name', 'hostname', 'ip_address', 'ip_address_secondary',
            'port', 'type', 'module', 'username', 'api_key', 'api_hash', 'password', 'max_accounts',
            'secure', 'active', 'nameserver1', 'nameserver2', 'nameserver3',
            'datacenter', 'location',
        ]);

        $data['module'] = $this->normalizeModule($data['module'] ?? null);
        $data['username'] = ServerModuleManager::requiresUsername($data['module']) && !empty($data['username'])
            ? $data['username']
            : null;
        $data['api_key'] = ServerModuleManager::requiresApiKey($data['module']) && !empty($data['api_key'])
            ? $data['api_key']
            : null;
        $data['password'] = ServerModuleManager::requiresPassword($data['module']) && !empty($data['password'])
            ? $data['password']
            : null;

        $server = Server::create($data);

        ServerHealthCheckJob::dispatchSync($server->id);

        return response()->json([
            'message' => 'Servidor cadastrado com sucesso!',
            'server' => $server->fresh(['group', 'latestHealthLog']),
        ], 201);
    }

    public function update(Request $request, Server $server): JsonResponse
    {
        $request->validate($this->serverRules($request, true));

        $data = $request->only([
            'server_group_id', 'name', 'hostname', 'ip_address', 'ip_address_secondary',
            'port', 'type', 'module', 'username', 'max_accounts', 'secure', 'active',
            'nameserver1', 'nameserver2', 'nameserver3', 'datacenter', 'location',
        ]);

        $data['module'] = $this->normalizeModule($data['module'] ?? null);
        $data['username'] = ServerModuleManager::requiresUsername($data['module']) && !empty($data['username'])
            ? $data['username']
            : null;

        if (!ServerModuleManager::requiresApiKey($data['module'])) {
            $data['api_key'] = null;
        } elseif ($request->filled('api_key')) {
            $data['api_key'] = $request->input('api_key');
        }

        if (!ServerModuleManager::requiresPassword($data['module'])) {
            $data['password'] = null;
        } elseif ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        if ($request->filled('api_hash')) {
            $data['api_hash'] = $request->input('api_hash');
        }

        $server->update($data);

        ServerHealthCheckJob::dispatchSync($server->id);

        return response()->json([
            'message' => 'Servidor atualizado!',
            'server' => $server->fresh(['group', 'latestHealthLog']),
        ]);
    }

    public function destroy(Server $server): JsonResponse
    {
        if ($server->services()->where('status', 'active')->count() > 0) {
            return response()->json([
                'message' => 'Servidor possui servicos ativos. Migre-os antes de remover.',
            ], 422);
        }

        $server->delete();

        return response()->json(['message' => 'Servidor removido com sucesso!']);
    }

    public function healthCheck(Server $server): JsonResponse
    {
        ServerHealthCheckJob::dispatchSync($server->id);

        return response()->json([
            'message' => 'Health check executado com sucesso!',
            'server' => $server->fresh(['latestHealthLog']),
        ]);
    }

    public function healthStatus(Server $server): JsonResponse
    {
        $latest = $server->latestHealthLog;
        $lastCheckedAt = $latest?->checked_at ?: $server->last_check_at;
        $networkStatus = $latest?->network_status ?: ($server->last_check_at ? $server->status : 'unknown');
        $isStale = !$lastCheckedAt || $lastCheckedAt->lt(now()->subMinutes(15));

        return response()->json([
            'server' => $server->only(['id', 'name', 'hostname', 'status', 'last_check_at']),
            'health' => $latest,
            'cpu' => $latest?->cpu_usage,
            'ram' => $latest?->ram_usage,
            'disk' => $latest?->disk_usage,
            'load_avg_1' => $latest?->load_avg_1,
            'load_avg_5' => $latest?->load_avg_5,
            'load_avg_15' => $latest?->load_avg_15,
            'latency_ms' => $latest?->latency_ms,
            'packet_loss' => $latest?->packet_loss_pct,
            'network_in' => $latest?->network_in_mbps,
            'network_out' => $latest?->network_out_mbps,
            'network_status' => $networkStatus,
            'uptime' => $latest?->uptime_human,
            'checked_at' => $lastCheckedAt?->diffForHumans(),
            'last_checked_at' => $lastCheckedAt?->toIso8601String(),
            'minutes_since_check' => $lastCheckedAt?->diffInMinutes(now()),
            'has_recent_check' => !$isStale,
            'is_stale' => $isStale,
        ]);
    }

    public function healthHistory(Server $server): JsonResponse
    {
        $history = ServerHealthLog::where('server_id', $server->id)
            ->orderBy('checked_at')
            ->limit(48)
            ->get([
                'checked_at',
                'cpu_usage',
                'ram_usage',
                'disk_usage',
                'load_avg_1',
                'latency_ms',
                'packet_loss_pct',
                'network_status',
            ]);

        return response()->json([
            'labels' => $history->map(fn ($item) => $item->checked_at->format('H:i'))->values(),
            'cpu' => $history->pluck('cpu_usage')->map(fn ($value) => (float) ($value ?? 0))->values(),
            'ram' => $history->pluck('ram_usage')->map(fn ($value) => (float) ($value ?? 0))->values(),
            'disk' => $history->pluck('disk_usage')->map(fn ($value) => (float) ($value ?? 0))->values(),
            'latency' => $history->pluck('latency_ms')->values(),
            'packet_loss' => $history->pluck('packet_loss_pct')->map(fn ($value) => (float) ($value ?? 0))->values(),
        ]);
    }

    public function testConnectivity(Server $server): JsonResponse
    {
        try {
            $result = \App\Services\ServerModules\ServerModuleManager::make($server)->testConnection();
            $success = is_array($result) ? (bool) ($result['success'] ?? false) : (bool) $result;
            $message = is_array($result) ? ($result['message'] ?? null) : null;

            return response()->json([
                'success' => $success,
                'message' => $message ?: ($success
                    ? 'Conexao estabelecida com sucesso!'
                    : 'Nao foi possivel conectar ao servidor com os dados atuais.'),
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function toggleStatus(Server $server): JsonResponse
    {
        $server->update([
            'active' => !$server->active,
        ]);

        return response()->json([
            'message' => $server->active ? 'Servidor ativado com sucesso!' : 'Servidor desativado com sucesso!',
            'active' => (bool) $server->active,
            'server' => $server->fresh(['group', 'latestHealthLog']),
        ]);
    }

    public function groups(): JsonResponse
    {
        return response()->json(ServerGroup::withCount('servers')->get());
    }

    public function storeGroup(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'fill_type' => 'required|in:sequential,least_used,random',
        ]);

        $group = ServerGroup::create($request->only(['name', 'description', 'fill_type', 'active']));

        return response()->json(['message' => 'Grupo criado!', 'group' => $group], 201);
    }
}
