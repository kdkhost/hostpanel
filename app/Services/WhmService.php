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

        // 1. Load averages (WHM API v1: data.one, data.five, data.fifteen)
        try {
            $raw = $this->call('loadavg');
            $d = $raw['data'] ?? $raw;
            $stats['load_avg_1']  = (float) ($d['one'] ?? 0);
            $stats['load_avg_5']  = (float) ($d['five'] ?? 0);
            $stats['load_avg_15'] = (float) ($d['fifteen'] ?? 0);
            $stats['raw_data']['loadavg'] = $d;
        } catch (\Throwable $e) {
            Log::warning("WHM loadavg failed [{$this->server->hostname}]: " . $e->getMessage());
        }

        // 2. CPU from load average (systemloadavg API v0 has cpu_count)
        try {
            $raw = $this->call('systemloadavg', ['api.version' => 0]);
            $cpuCount = (int) ($raw['cpucount'] ?? $raw['cpu_count'] ?? 1);
            $cpuCount = max($cpuCount, 1);
            if ($stats['load_avg_1'] !== null) {
                $stats['cpu_usage'] = round(min(($stats['load_avg_1'] / $cpuCount) * 100, 100), 2);
            }
            if (isset($raw['uptime'])) {
                $stats['uptime_seconds'] = $this->parseUptimeToSeconds($raw['uptime']);
            }
            $stats['raw_data']['systemloadavg'] = $raw;
        } catch (\Throwable) {
            // Fallback: estimate CPU from load_avg_1 assuming 1 CPU
            if ($stats['load_avg_1'] !== null) {
                $stats['cpu_usage'] = round(min($stats['load_avg_1'] * 100, 100), 2);
            }
        }

        // 3. Memory usage via API v0 (retorna memory_used, memory_total etc.)
        try {
            $raw = $this->call('systemloadavg', ['api.version' => 0]);
            $memUsed = (float) ($raw['memory_used'] ?? 0);
            $memTotal = (float) ($raw['memory_total'] ?? 0);
            if ($memTotal > 0) {
                $stats['ram_usage'] = round(($memUsed / $memTotal) * 100, 2);
            } elseif (isset($raw['memorypercent'])) {
                $stats['ram_usage'] = (float) $raw['memorypercent'];
            }
        } catch (\Throwable) {
            // Tentativa alternativa: getmemoryusage (cPanel >= 110)
            try {
                $raw = $this->call('getmemoryusage');
                $d = $raw['data'] ?? $raw;
                $stats['ram_usage'] = (float) ($d['percent'] ?? $d['percentage'] ?? 0);
            } catch (\Throwable) {}
        }

        // 4. Disk usage
        try {
            $raw = $this->call('getdiskusage');
            $d = $raw['data'] ?? $raw;
            $partitions = $d['partition'] ?? $d['partitions'] ?? $d;
            if (is_array($partitions)) {
                // Procura partição root "/" ou usa a primeira
                $root = null;
                foreach ($partitions as $p) {
                    if (!is_array($p)) continue;
                    $mount = $p['mount'] ?? $p['filesystem'] ?? $p['mounted'] ?? '';
                    if ($mount === '/' || $mount === '/home') {
                        $root = $p;
                        if ($mount === '/') break;
                    }
                }
                $root = $root ?? (is_array(reset($partitions)) ? reset($partitions) : null);
                if ($root) {
                    $pct = $root['percentage'] ?? $root['percent'] ?? $root['percentage_used'] ?? null;
                    if ($pct !== null) {
                        $stats['disk_usage'] = (float) str_replace('%', '', (string) $pct);
                    } elseif (isset($root['used'], $root['total'])) {
                        $total = (float) str_replace(['M', 'G', 'T', '%'], '', (string) $root['total']);
                        $used = (float) str_replace(['M', 'G', 'T', '%'], '', (string) $root['used']);
                        if ($total > 0) {
                            $stats['disk_usage'] = round(($used / $total) * 100, 2);
                        }
                    }
                }
            }
            $stats['raw_data']['diskusage'] = $d;
        } catch (\Throwable $e) {
            Log::warning("WHM getdiskusage failed [{$this->server->hostname}]: " . $e->getMessage());
        }

        // 5. cPanel version
        try {
            $raw = $this->call('version');
            $d = $raw['data'] ?? $raw;
            $stats['cpanel_version'] = $d['version'] ?? null;
        } catch (\Throwable) {}

        // 6. Account count
        try {
            $raw = $this->call('listaccts', ['want' => 'user']);
            $acctList = $raw['data']['acct'] ?? $raw['acct'] ?? [];
            $stats['account_count'] = is_array($acctList) ? count($acctList) : 0;
        } catch (\Throwable) {}

        return $stats;
    }

    protected function parseUptimeToSeconds(string $uptime): ?int
    {
        // Formatos comuns: "up 10 days, 5:30" ou "10 days 5 hours 30 min" ou "5:30"
        $seconds = 0;
        if (preg_match('/(\d+)\s*day/', $uptime, $m)) {
            $seconds += (int) $m[1] * 86400;
        }
        if (preg_match('/(\d+)\s*hour/', $uptime, $m)) {
            $seconds += (int) $m[1] * 3600;
        }
        if (preg_match('/(\d+):(\d+)/', $uptime, $m)) {
            $seconds += (int) $m[1] * 3600 + (int) $m[2] * 60;
        }
        if (preg_match('/(\d+)\s*min/', $uptime, $m)) {
            $seconds += (int) $m[1] * 60;
        }
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
