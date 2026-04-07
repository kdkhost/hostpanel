<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InstallerController extends Controller
{
    public function index()
    {
        $requirements = $this->checkRequirements();
        return view('installer.index', compact('requirements'));
    }

    public function check(Request $request)
    {
        $requirements = $this->checkRequirements();
        $allMet       = collect($requirements)->every(fn($r) => $r['ok']);
        return response()->json(['requirements' => $requirements, 'all_met' => $allMet]);
    }

    public function run(Request $request)
    {
        $request->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|integer',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            'app_name'    => 'required|string',
            'app_url'     => 'required|url',
            'admin_name'  => 'required|string',
            'admin_email' => 'required|email',
            'admin_pass'  => 'required|string|min:8',
        ]);

        try {
            $dbPassword = $request->db_password ?? '';

            // 1. Gerar APP_KEY se não existir
            $appKey = config('app.key');
            if (empty($appKey) || !str_starts_with($appKey, 'base64:')) {
                $appKey = 'base64:' . base64_encode(random_bytes(32));
                config()->set('app.key', $appKey);
            }

            // 2. GRAVAR .env PRIMEIRO com todas as credenciais do formulário
            //    Assim mesmo que falhe algo, o .env já terá os dados corretos
            $this->updateEnv([
                'APP_NAME'             => '"' . $request->app_name . '"',
                'APP_ENV'              => 'production',
                'APP_DEBUG'            => 'false',
                'APP_KEY'              => $appKey,
                'APP_URL'              => $request->app_url,
                'APP_TIMEZONE'         => 'America/Sao_Paulo',
                'APP_LOCALE'           => 'pt_BR',
                'APP_FALLBACK_LOCALE'  => 'pt_BR',
                'APP_FAKER_LOCALE'     => 'pt_BR',
                'DB_CONNECTION'        => 'mysql',
                'DB_HOST'              => $request->db_host,
                'DB_PORT'              => (string) $request->db_port,
                'DB_DATABASE'          => $request->db_database,
                'DB_USERNAME'          => $request->db_username,
                'DB_PASSWORD'          => $dbPassword,
                'SESSION_DRIVER'       => 'file',
                'CACHE_STORE'          => 'file',
                'INSTALLER_ENABLED'    => 'true',
                'INSTALLED'            => 'false',
            ]);

            // 3. Configurar conexão MySQL em runtime
            config()->set('database.default', 'mysql');
            config()->set('database.connections.mysql.host', $request->db_host);
            config()->set('database.connections.mysql.port', (int) $request->db_port);
            config()->set('database.connections.mysql.database', $request->db_database);
            config()->set('database.connections.mysql.username', $request->db_username);
            config()->set('database.connections.mysql.password', $dbPassword);

            // 4. Forçar session/cache para file durante instalação
            config()->set('session.driver', 'file');
            config()->set('cache.default', 'file');

            // 5. Reconectar ao banco
            DB::purge('mysql');
            DB::reconnect('mysql');

            // 6. Testar conexão — se falhar, erro claro (mas .env já foi salvo)
            try {
                DB::connection('mysql')->getPdo();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível conectar ao banco de dados. Verifique as credenciais. Erro: ' . $e->getMessage(),
                ], 422);
            }

            // 7. Executar migrations
            Artisan::call('migrate', ['--force' => true]);

            // 8. Executar seeders (cria roles, permissões, settings, gateways, etc.)
            Artisan::call('db:seed', ['--force' => true]);

            // 9. Criar superadmin
            $admin = \App\Models\Admin::create([
                'name'     => $request->admin_name,
                'email'    => $request->admin_email,
                'password' => Hash::make($request->admin_pass),
                'status'   => 'active',
            ]);
            $admin->assignRole('super-admin');

            // 10. Criar link de storage
            try {
                Artisan::call('storage:link');
            } catch (\Exception $e) {
                // Pode falhar se link já existe — não é crítico
            }

            // 11. Limpar caches do Laravel
            try {
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
                Artisan::call('view:clear');
            } catch (\Exception $e) {
                // Não é crítico
            }

            // 12. Atualizar .env final (session/cache para database, marcar como instalado)
            $this->updateEnv([
                'QUEUE_CONNECTION'     => 'database',
                'SESSION_DRIVER'       => 'database',
                'CACHE_STORE'          => 'database',
                'INSTALLED'            => 'true',
                'INSTALLER_ENABLED'    => 'false',
            ]);

            // 13. Marcar como instalado
            file_put_contents(storage_path('installed'), now()->toDateTimeString());

            return response()->json(['success' => true, 'redirect' => url('/admin/entrar')]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function complete()
    {
        if (!file_exists(storage_path('installed'))) {
            return redirect()->route('install.index');
        }
        return view('installer.complete');
    }

    protected function checkRequirements(): array
    {
        return [
            ['name' => 'PHP >= 8.4',         'ok' => version_compare(PHP_VERSION, '8.4.0', '>=')],
            ['name' => 'Extension PDO',       'ok' => extension_loaded('pdo')],
            ['name' => 'Extension PDO MySQL', 'ok' => extension_loaded('pdo_mysql')],
            ['name' => 'Extension OpenSSL',   'ok' => extension_loaded('openssl')],
            ['name' => 'Extension ctype',     'ok' => extension_loaded('ctype')],
            ['name' => 'Extension mbstring',  'ok' => extension_loaded('mbstring')],
            ['name' => 'Extension tokenizer', 'ok' => extension_loaded('tokenizer')],
            ['name' => 'Extension xml',       'ok' => extension_loaded('xml')],
            ['name' => 'Extension curl',      'ok' => extension_loaded('curl')],
            ['name' => 'Extension json',      'ok' => extension_loaded('json')],
            ['name' => 'Extension zip',       'ok' => extension_loaded('zip')],
            ['name' => 'storage/ gravável',   'ok' => is_writable(storage_path())],
            ['name' => 'bootstrap/cache/ gravável', 'ok' => is_writable(base_path('bootstrap/cache'))],
        ];
    }

    protected function updateEnv(array $data): void
    {
        $envFile = base_path('.env');
        if (!file_exists($envFile)) {
            copy(base_path('.env.example'), $envFile);
        }
        $env = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $escapedKey = preg_quote($key, '/');
            $pattern = "/^{$escapedKey}=.*/m";

            // Escapar valor se contém caracteres especiais
            $safeValue = $value;
            if (preg_match('/[\s#"\'\\\\$]/', $safeValue) && !preg_match('/^".*"$/', $safeValue)) {
                $safeValue = '"' . addcslashes($safeValue, '"\\') . '"';
            }

            $replace = "{$key}={$safeValue}";
            if (preg_match($pattern, $env)) {
                $env = preg_replace($pattern, $replace, $env);
            } else {
                $env .= "\n{$replace}";
            }
        }

        file_put_contents($envFile, $env, LOCK_EX);
    }
}
