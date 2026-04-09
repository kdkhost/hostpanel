<?php

namespace App\Services;

use App\Models\Server;
use App\Models\ServerHealthLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class WhmService
{
    protected Client $http;
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
        $this->http   = new Client([
            'base_uri' => $this->server->api_url . '/',
            'timeout'  => 30,
            'verify'   => false,
            'headers'  => $this->buildHeaders(),
        ]);
    }

    protected function buildHeaders(): array
    {
        return [
            'Authorization' => 'whm ' . $this->server->username . ':' . $this->server->api_key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    public function call(string $function, array $params = []): array
    {
        try {
            $response = $this->http->get("json-api/{$function}", [
                'query' => array_merge(['api.version' => 1], $params),
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            $this->logApiCall($function, $params, $data, true);
            return $data;
        } catch (RequestException $e) {
            $error = $e->getMessage();
            $this->logApiCall($function, $params, ['error' => $error], false);
            throw new \RuntimeException("WHM API Error [{$function}]: {$error}");
        }
    }

    public function createAccount(array $data): array
    {
        return $this->call('createacct', [
            'username' => $data['username'],
            'domain'   => $data['domain'],
            'password' => $data['password'],
            'plan'     => $data['plan'] ?? '',
            'contactemail' => $data['email'] ?? '',
        ]);
    }

    public function suspendAccount(string $username, string $reason = ''): array
    {
        return $this->call('suspendacct', ['user' => $username, 'reason' => $reason]);
    }

    public function unsuspendAccount(string $username): array
    {
        return $this->call('unsuspendacct', ['user' => $username]);
    }

    public function terminateAccount(string $username): array
    {
        return $this->call('removeacct', ['user' => $username]);
    }

    public function changePassword(string $username, string $newPassword): array
    {
        return $this->call('passwd', ['user' => $username, 'password' => $newPassword]);
    }

    public function changePackage(string $username, string $pkg): array
    {
        return $this->call('changepackage', ['user' => $username, 'pkg' => $pkg]);
    }

    public function getAccountDetails(string $username): array
    {
        return $this->call('accountsummary', ['user' => $username]);
    }

    public function listAccounts(): array
    {
        return $this->call('listaccts');
    }

    public function getServerHealth(): array
    {
        $stats = [
            'cpu_usage'       => null,
            'ram_usage'       => null,
            'disk_usage'      => null,
            'load_avg_1'      => null,
            'load_avg_5'      => null,
            'load_avg_15'     => null,
            'uptime_seconds'  => null,
            'account_count'   => null,
            'cpanel_version'  => null,
            'status'          => 'online',
            'raw_data'        => [],
        ];

        // ──────────────────────────────────────────────
        // 1. Load averages  (WHM API v1 — sempre funciona)
        // ──────────────────────────────────────────────
        try {
            $raw = $this->call('loadavg');
            $d = $raw['data'] ?? $raw;
            $stats['load_avg_1']  = (float) ($d['one'] ?? 0);
            $stats['load_avg_5']  = (float) ($d['five'] ?? 0);
            $stats['load_avg_15'] = (float) ($d['fifteen'] ?? 0);
            $stats['raw_data']['loadavg'] = $d;
        } catch (\Throwable $e) {
            Log::warning("WHM loadavg failed [{$this->server->hostname}]: {$e->getMessage()}");
        }

        // CPU: estimar a partir do load avg (normalizado por nº de CPUs se disponível)
        $cpuCount = 1;
        if ($stats['load_avg_1'] !== null) {
            $stats['cpu_usage'] = round(min($stats['load_avg_1'] * 100, 100), 2);
        }

        // ──────────────────────────────────────────────
        // 2. Dados do servidor (RAM, Disco, Uptime, CPU count)
        //    Tenta várias fontes em ordem de prioridade
        // ──────────────────────────────────────────────
        $this->tryFetchServerStats($stats, $cpuCount);

        // Recalcular CPU com cpuCount real (se obtido)
        if ($stats['load_avg_1'] !== null && $cpuCount > 1) {
            $stats['cpu_usage'] = round(min(($stats['load_avg_1'] / $cpuCount) * 100, 100), 2);
        }

        // ──────────────────────────────────────────────
        // 3. Disco via getdiskusage (fallback se ainda null)
        // ──────────────────────────────────────────────
        if ($stats['disk_usage'] === null) {
            try {
                $raw = $this->call('getdiskusage');
                $stats['disk_usage'] = $this->parseDiskUsage($raw);
                $stats['raw_data']['diskusage'] = $raw['data'] ?? $raw;
            } catch (\Throwable $e) {
                Log::warning("WHM getdiskusage failed [{$this->server->hostname}]: {$e->getMessage()}");
            }
        }

        // ──────────────────────────────────────────────
        // 4. Contagem de contas
        // ──────────────────────────────────────────────
        try {
            $raw = $this->call('listaccts');
            $acctList = $raw['data']['acct'] ?? $raw['acct'] ?? [];
            $stats['account_count'] = is_array($acctList) ? count($acctList) : 0;
            $stats['raw_data']['listaccts_count'] = $stats['account_count'];
        } catch (\Throwable $e) {
            Log::info("WHM listaccts failed [{$this->server->hostname}]: {$e->getMessage()}");
        }

        // ──────────────────────────────────────────────
        // 5. Versão do cPanel
        // ──────────────────────────────────────────────
        try {
            $raw = $this->call('version');
            $d = $raw['data'] ?? $raw;
            $stats['cpanel_version'] = $d['version'] ?? null;
        } catch (\Throwable) {}

        return $stats;
    }

    /**
     * Tenta múltiplas fontes WHM para obter RAM, Disco, Uptime e CPU count.
     */
    protected function tryFetchServerStats(array &$stats, int &$cpuCount): void
    {
        // Fonte A: resourceusage (cPanel >= 86, retorna tudo)
        try {
            $raw = $this->call('resourceusage');
            $d = $raw['data'] ?? $raw;
            $stats['raw_data']['resourceusage'] = $d;

            if (isset($d['memory']['percent'])) {
                $stats['ram_usage'] = (float) $d['memory']['percent'];
            } elseif (isset($d['memory']['used'], $d['memory']['total'])) {
                $total = (float) $d['memory']['total'];
                if ($total > 0) {
                    $stats['ram_usage'] = round(((float) $d['memory']['used'] / $total) * 100, 2);
                }
            }

            if (isset($d['disk'])) {
                $stats['disk_usage'] = $this->parseDiskUsage(['data' => $d['disk']]);
            }

            if (isset($d['cpucount'])) {
                $cpuCount = max((int) $d['cpucount'], 1);
            }

            if (isset($d['uptime'])) {
                $stats['uptime_seconds'] = $this->parseUptimeToSeconds((string) $d['uptime']);
            }

            // Se temos RAM, podemos parar de tentar
            if ($stats['ram_usage'] !== null) return;
        } catch (\Throwable) {}

        // Fonte B: systemloadavg API v0 (pode retornar cpucount e uptime)
        try {
            $raw = $this->call('systemloadavg', ['api.version' => 0]);
            $stats['raw_data']['systemloadavg'] = $raw;

            if (isset($raw['cpucount'])) {
                $cpuCount = max((int) $raw['cpucount'], 1);
            }
            if (isset($raw['uptime'])) {
                $stats['uptime_seconds'] = $this->parseUptimeToSeconds((string) $raw['uptime']);
            }
        } catch (\Throwable) {}

        // Fonte C: Leitura direta do /proc/meminfo via read_cpanel_file (cPanel >= 94)
        if ($stats['ram_usage'] === null) {
            try {
                $memRaw = $this->http->get('json-api/get_vps_detail_info', [
                    'query' => ['api.version' => 1],
                ])->getBody()->getContents();
                $mem = json_decode($memRaw, true);
                $d = $mem['data'] ?? $mem;
                if (isset($d['memory_total'], $d['memory_used'])) {
                    $total = (float) $d['memory_total'];
                    if ($total > 0) {
                        $stats['ram_usage'] = round(((float) $d['memory_used'] / $total) * 100, 2);
                    }
                }
            } catch (\Throwable) {}
        }

        // Fonte D: Parsear /proc/meminfo e /proc/uptime via endpoint genérico
        if ($stats['ram_usage'] === null || $stats['uptime_seconds'] === null) {
            $this->tryReadProcStats($stats);
        }
    }

    /**
     * Tenta ler /proc/meminfo e /proc/uptime via WHM exec ou endpoint de status.
     */
    protected function tryReadProcStats(array &$stats): void
    {
        // Tentar via getserverstatus (retorna info do /proc)
        try {
            $raw = $this->call('getserverstatus');
            $d = $raw['data'] ?? $raw;
            $stats['raw_data']['serverstatus'] = $d;

            // Parsear memória se disponível
            if ($stats['ram_usage'] === null) {
                $memTotal = (float) ($d['memtotal'] ?? $d['memory_total'] ?? 0);
                $memFree = (float) ($d['memfree'] ?? $d['memory_free'] ?? 0);
                $memAvailable = (float) ($d['memavailable'] ?? $d['memory_available'] ?? $memFree);
                $cached = (float) ($d['cached'] ?? 0);
                $buffers = (float) ($d['buffers'] ?? 0);

                if ($memTotal > 0) {
                    // Usar MemAvailable se existir, senão Free + Buffers + Cached
                    $used = $memTotal - ($memAvailable > 0 ? $memAvailable : ($memFree + $cached + $buffers));
                    $stats['ram_usage'] = round(max(0, ($used / $memTotal) * 100), 2);
                }
            }

            // Parsear uptime se disponível
            if ($stats['uptime_seconds'] === null && isset($d['uptime'])) {
                $stats['uptime_seconds'] = $this->parseUptimeToSeconds((string) $d['uptime']);
            }
        } catch (\Throwable) {}

        // Último recurso para RAM: tentar endpoint de stat do backend cPanel
        if ($stats['ram_usage'] === null) {
            try {
                $raw = $this->http->get('json-api/stathp', ['query' => ['api.version' => 1]])->getBody()->getContents();
                $d = json_decode($raw, true) ?? [];
                $data = $d['data'] ?? $d;
                foreach (['memoryusage', 'memory_usage', 'physicalmem'] as $key) {
                    if (isset($data[$key]) && is_numeric($data[$key])) {
                        $stats['ram_usage'] = round((float) $data[$key], 2);
                        break;
                    }
                }
            } catch (\Throwable) {}
        }
    }

    /**
     * Parseia a resposta de getdiskusage para extrair a % de uso.
     */
    protected function parseDiskUsage(array $raw): ?float
    {
        $d = $raw['data'] ?? $raw;
        // A resposta pode ser: array de partições direto, ou { partition: [...] }
        $partitions = $d['partition'] ?? $d['partitions'] ?? $d;

        if (!is_array($partitions)) return null;

        $root = null;
        foreach ($partitions as $key => $p) {
            if (!is_array($p)) continue;
            // WHM usa 'item' e 'mount' para o ponto de montagem
            $mount = $p['item'] ?? $p['mount'] ?? $p['filesystem'] ?? $p['mounted'] ?? '';
            if ($mount === '/' || $mount === '/home') {
                $root = $p;
                if ($mount === '/') break;
            }
        }

        // Se não achou "/" nem "/home", usa a primeira partição válida
        if (!$root) {
            foreach ($partitions as $p) {
                if (is_array($p) && (isset($p['percentage']) || isset($p['used_bytes']))) {
                    $root = $p;
                    break;
                }
            }
        }

        if (!$root) return null;

        // Tentar campo de porcentagem direto
        $pct = $root['percentage'] ?? $root['percent'] ?? $root['percentage_used'] ?? null;
        if ($pct !== null) {
            return (float) str_replace('%', '', (string) $pct);
        }

        // Calcular a partir de bytes
        $usedBytes = (float) ($root['used_bytes'] ?? $root['blocks_used'] ?? 0);
        $totalBytes = (float) ($root['total_bytes'] ?? $root['blocks'] ?? 0);
        if ($totalBytes > 0) {
            return round(($usedBytes / $totalBytes) * 100, 2);
        }

        return null;
    }

    protected function parseUptimeToSeconds(string $uptime): ?int
    {
        $seconds = 0;
        // "up 10 days, 5:30" ou "10 days 5 hours 30 min" ou "5:30"
        if (preg_match('/(\d+)\s*day/', $uptime, $m)) $seconds += (int) $m[1] * 86400;
        if (preg_match('/(\d+)\s*hour/', $uptime, $m)) $seconds += (int) $m[1] * 3600;
        if (preg_match('/(\d+):(\d+)/', $uptime, $m)) $seconds += (int) $m[1] * 3600 + (int) $m[2] * 60;
        if (preg_match('/(\d+)\s*min/', $uptime, $m)) $seconds += (int) $m[1] * 60;
        // Formato numérico direto (segundos)
        if ($seconds === 0 && is_numeric(trim($uptime))) $seconds = (int) $uptime;
        return $seconds > 0 ? $seconds : null;
    }

    public function updateServerHealth(): ServerHealthLog
    {
        $health = $this->getServerHealth();

        $log = ServerHealthLog::create(array_merge($health, [
            'server_id'  => $this->server->id,
            'checked_at' => now(),
        ]));

        $this->server->update([
            'status'       => $health['status'] ?? 'unknown',
            'last_check_at'=> now(),
            'current_accounts' => $health['account_count'] ?? $this->server->current_accounts,
        ]);

        return $log;
    }

    public function getCpanelAutoLoginUrl(string $username): string
    {
        $result = $this->call('create_user_session', [
            'user'    => $username,
            'service' => 'cpaneld',
        ]);

        $url = $result['data']['url'] ?? null;
        if (!$url) {
            throw new \RuntimeException('Failed to generate cPanel auto-login URL.');
        }
        return $url;
    }

    public function testConnection(): bool
    {
        try {
            $result = $this->call('loadavg');
            // WHM retorna status 1 em metadados quando o token é válido
            return ($result['metadata']['result'] ?? 0) == 1 || isset($result['one']);
        } catch (\Throwable) {
            return false;
        }
    }

    public function getAccountBandwidth(string $username): array
    {
        try {
            $result = $this->call('showbw', ['searchtype' => 'user', 'search' => $username]);
            return $result['data']['acct'][0] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    protected function logApiCall(string $function, array $params, array $response, bool $success): void
    {
        Log::channel('whm')->info('WHM API Call', [
            'server_id' => $this->server->id,
            'function'  => $function,
            'params'    => $params,
            'success'   => $success,
            'response'  => array_slice($response, 0, 5),
        ]);
    }
}
