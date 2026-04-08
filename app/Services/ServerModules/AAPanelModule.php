<?php

namespace App\Services\ServerModules;

use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Módulo de provisionamento para AAPanel (宝塔面板 / BT Panel).
 *
 * Autenticação: request_token = MD5(time + MD5(api_key))
 * URL base: http(s)://{hostname}:{port}
 * Documentação: https://www.aapanel.com/new/download/aapanel_en_linux.html
 */
class AAPanelModule extends AbstractServerModule
{
    protected function moduleName(): string
    {
        return ServerModuleManager::label($this->server->module, 'AAPanel');
    }

    /* ------------------------------------------------------------------ */
    /*  Autenticação                                                        */
    /* ------------------------------------------------------------------ */

    private function authParams(): array
    {
        $time  = time();
        $token = md5($time . md5($this->server->api_key));
        return [
            'request_token' => $token,
            'request_time'  => $time,
        ];
    }

    private function post(string $path, array $data = []): array
    {
        $url  = $this->baseUrl() . $path;
        $body = array_merge($this->authParams(), $data);

        $response = Http::withOptions(['verify' => false])
            ->asForm()
            ->timeout(30)
            ->post($url, $body);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "AAPanel API error on {$path}: HTTP {$response->status()} — {$response->body()}"
            );
        }

        $json = $response->json();

        if (isset($json['status']) && $json['status'] === false) {
            throw new \RuntimeException(
                "AAPanel error: " . ($json['msg'] ?? json_encode($json))
            );
        }

        return $json ?? [];
    }

    /* ------------------------------------------------------------------ */
    /*  Implementação da interface                                          */
    /* ------------------------------------------------------------------ */

    public function createAccount(Service $service, array $options = []): array
    {
        $client   = $service->client;
        $domain   = $service->domain ?? ($this->generateUsername($client->name) . '.example.com');
        $username = $this->generateUsername($domain);
        $password = $options['password'] ?? Str::random(16);
        $phpVer   = $options['php_version'] ?? ($this->server->meta['default_php'] ?? '74');
        $path     = $options['path'] ?? "/www/wwwroot/{$domain}";
        $plan     = $service->product?->meta['aapanel_plan'] ?? null;

        // 1. Criar site
        $siteResult = $this->post('/site?action=AddSite', [
            'webname'  => json_encode([
                'domain'  => $domain,
                'domainlist' => [],
                'count'   => 0,
            ]),
            'path'     => $path,
            'type_id'  => 0,
            'type'     => 'PHP',
            'version'  => $phpVer,
            'port'     => 80,
            'ps'       => "HostPanel - {$client->name} - #{$service->id}",
            'ftp'      => false,
            'sql'      => false,
        ]);

        $siteId = $siteResult['siteId'] ?? null;

        // 2. Criar conta FTP
        $ftpResult = $this->post('/ftp?action=AddUser', [
            'ftp_username' => $username,
            'ftp_password' => $password,
            'ftp_path'     => $path,
        ]);

        // 3. Criar banco de dados (opcional, se produto configurado)
        $dbResult = [];
        if ($options['create_db'] ?? $service->product?->meta['create_db'] ?? false) {
            $dbName = substr($username, 0, 10) . '_db';
            $dbPass = Str::random(14);
            $dbResult = $this->post('/database?action=AddDatabase', [
                'name'     => $dbName,
                'codeing'  => 'utf8mb4',
                'db_user'  => $dbName,
                'db_pass'  => $dbPass,
                'address'  => 'localhost',
                'ps'       => "DB: {$client->name}",
            ]);
        }

        // 4. Aplicar plano de recursos (PHP + limites) se configurado
        if ($siteId && $plan) {
            $this->applyPlan($siteId, $domain, $plan);
        }

        $this->log('createAccount', ['domain' => $domain, 'username' => $username]);

        return [
            'success'  => true,
            'username' => $username,
            'password' => $password,
            'domain'   => $domain,
            'path'     => $path,
            'site_id'  => $siteId,
            'ftp'      => $ftpResult,
            'database' => $dbResult,
        ];
    }

    public function suspendAccount(Service $service, string $reason = ''): bool
    {
        if (!$service->username) return false;

        $site = $this->findSiteByDomain($service->domain ?? '');
        if (!$site) return false;

        try {
            $this->post('/site?action=SiteStop', [
                'id'   => $site['id'],
                'name' => $site['name'],
            ]);
            $this->log('suspendAccount', ['domain' => $service->domain, 'reason' => $reason]);
            return true;
        } catch (\Throwable $e) {
            $this->logError('suspendAccount', $e);
            return false;
        }
    }

    public function unsuspendAccount(Service $service): bool
    {
        $site = $this->findSiteByDomain($service->domain ?? '');
        if (!$site) return false;

        try {
            $this->post('/site?action=SiteStart', [
                'id'   => $site['id'],
                'name' => $site['name'],
            ]);
            $this->log('unsuspendAccount', ['domain' => $service->domain]);
            return true;
        } catch (\Throwable $e) {
            $this->logError('unsuspendAccount', $e);
            return false;
        }
    }

    public function terminateAccount(Service $service): bool
    {
        $site = $this->findSiteByDomain($service->domain ?? '');

        try {
            if ($site) {
                $this->post('/site?action=DeleteSite', [
                    'id'       => $site['id'],
                    'webname'  => $site['name'],
                    'ftp'      => 1,
                    'database' => 1,
                    'path'     => 1,
                ]);
            }

            // Remove FTP
            $ftps = $this->post('/data?action=getData&table=ftps&limit=100&p=1&search=');
            $ftp  = collect($ftps['data'] ?? [])->firstWhere('name', $service->username);
            if ($ftp) {
                $this->post('/ftp?action=DeleteUser', [
                    'id'       => $ftp['id'],
                    'username' => $ftp['name'],
                ]);
            }

            $this->log('terminateAccount', ['domain' => $service->domain]);
            return true;
        } catch (\Throwable $e) {
            $this->logError('terminateAccount', $e);
            return false;
        }
    }

    public function changePassword(Service $service, string $newPassword): bool
    {
        try {
            // Alterar senha FTP
            $ftps = $this->post('/data?action=getData&table=ftps&limit=100&p=1&search=');
            $ftp  = collect($ftps['data'] ?? [])->firstWhere('name', $service->username);

            if ($ftp) {
                $this->post('/ftp?action=SetUserPassword', [
                    'id'       => $ftp['id'],
                    'username' => $ftp['name'],
                    'password' => $newPassword,
                ]);
            }

            $this->log('changePassword', ['username' => $service->username]);
            return true;
        } catch (\Throwable $e) {
            $this->logError('changePassword', $e);
            return false;
        }
    }

    /**
     * Gera URL de auto login no AAPanel usando token temporário.
     * AAPanel 7.x+ suporta /login?tmp_token=... via API.
     */
    public function getAutoLoginUrl(Service $service): string
    {
        try {
            $result = $this->post('/plugin?action=getLoginURL', [
                'username' => $service->username,
            ]);

            if (!empty($result['login_url'])) {
                return $result['login_url'];
            }
        } catch (\Throwable) {}

        // Fallback: URL de login com cookie de sessão gerado pelo token
        try {
            $result = $this->post('/login?action=get_tmp_token', [
                'username' => $service->username ?? '',
            ]);
            if (!empty($result['tmp_token'])) {
                return $this->baseUrl() . '/login?tmp_token=' . $result['tmp_token'];
            }
        } catch (\Throwable) {}

        // Último fallback: URL direta do painel
        return $this->baseUrl() . '/';
    }

    public function getUsageStats(Service $service): array
    {
        try {
            $system = $this->post('/system?action=GetSystemTotal');
            $site   = $this->findSiteByDomain($service->domain ?? '');

            $disk     = $system['disk'] ?? [];
            $memTotal = ($system['mem']['memTotal'] ?? 0) * 1024;
            $memUsed  = ($system['mem']['memRealUsed'] ?? 0) * 1024;

            return [
                'disk_used_bytes'  => (int) (($disk[0]['used'] ?? 0) * 1024 * 1024),
                'disk_total_bytes' => (int) (($disk[0]['size'] ?? 0) * 1024 * 1024),
                'disk_percent'     => round(($disk[0]['use_rate'] ?? '0%'), 2),
                'mem_used_bytes'   => $memUsed,
                'mem_total_bytes'  => $memTotal,
                'load_average'     => $system['load']['one'] ?? 0,
                'site_path'        => $site['path'] ?? null,
                'site_id'          => $site['id'] ?? null,
                'raw'              => $system,
            ];
        } catch (\Throwable $e) {
            $this->logError('getUsageStats', $e);
            return [];
        }
    }

    public function testConnection(): bool
    {
        try {
            $result = $this->post('/system?action=GetSystemTotal');
            return !empty($result['disk']);
        } catch (\Throwable) {
            return false;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    public function getSiteList(): array
    {
        return $this->post('/data?action=getData&table=sites&limit=50&p=1&search=');
    }

    public function getFtpList(): array
    {
        return $this->post('/data?action=getData&table=ftps&limit=50&p=1&search=');
    }

    public function getDatabaseList(): array
    {
        return $this->post('/data?action=getData&table=databases&limit=50&p=1&search=');
    }

    public function getSystemInfo(): array
    {
        return $this->post('/system?action=GetSystemTotal');
    }

    public function getNetworkInfo(): array
    {
        return $this->post('/system?action=GetNetWorkInfo');
    }

    private function findSiteByDomain(string $domain): ?array
    {
        if (!$domain) return null;
        try {
            $result = $this->post('/data?action=getData&table=sites&limit=50&p=1', [
                'search' => $domain,
            ]);
            $sites = $result['data'] ?? [];
            return collect($sites)->firstWhere('name', $domain) ?? ($sites[0] ?? null);
        } catch (\Throwable) {
            return null;
        }
    }

    private function applyPlan(int $siteId, string $domain, array $plan): void
    {
        // Configurar PHP version
        if (!empty($plan['php_version'])) {
            $this->post('/site?action=SetSitePHPVersion', [
                'siteName'   => $domain,
                'version'    => $plan['php_version'],
            ]);
        }

        // Configurar limite de tráfego (bytes)
        if (!empty($plan['traffic_quota'])) {
            $this->post('/site?action=SetTraffic', [
                'id'      => $siteId,
                'traffic' => $plan['traffic_quota'],
                'unit'    => 'G',
            ]);
        }
    }
}
