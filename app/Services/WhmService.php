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
        try {
            $info       = $this->call('loadavg');
            $diskInfo   = $this->call('getdiskusage');
            $cpuInfo    = $this->call('systemloadavg');
            $memInfo    = $this->call('getmemorypercent');
            $serverInfo = $this->call('version');
            $acctCount  = $this->call('listaccts', ['want' => 'user']);

            $load = [
                $info['one'] ?? $info['loadavg'][0] ?? 0,
                $info['five'] ?? $info['loadavg'][1] ?? 0,
                $info['fifteen'] ?? $info['loadavg'][2] ?? 0
            ];

            return [
                'cpu_usage'       => round(min(($load[0] ?? 0) * 10, 100), 2),
                'ram_usage'       => $memInfo['percent'] ?? null,
                'load_avg_1'      => $load[0] ?? null,
                'load_avg_5'      => $load[1] ?? null,
                'load_avg_15'     => $load[2] ?? null,
                'disk_usage'      => $diskInfo['percent'] ?? null,
                'account_count'   => count($acctCount['acct'] ?? $acctCount['data']['acct'] ?? []),
                'cpanel_version'  => $serverInfo['version'] ?? null,
                'status'          => 'online',
                'raw_data'        => compact('info', 'diskInfo', 'memInfo', 'serverInfo'),
            ];
        } catch (\Exception $e) {
            Log::error('WHM health check failed: ' . $e->getMessage());
            return ['status' => 'offline', 'error' => $e->getMessage()];
        }
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
