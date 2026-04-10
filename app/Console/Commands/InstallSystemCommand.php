<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallSystemCommand extends Command
{
    protected $signature = 'system:install {--force : Force installation even if already installed}';
    protected $description = 'Instala e configura o sistema HostPanel';

    public function handle(): int
    {
        $this->info('=== INSTALAÇÃO DO HOSTPANEL ===');
        
        // Verifica se já está instalado
        if (!$this->option('force') && $this->isInstalled()) {
            $this->error('Sistema já está instalado. Use --force para reinstalar.');
            return 1;
        }

        try {
            // 1. Executa migrações
            $this->info('Executando migrações...');
            Artisan::call('migrate', ['--force' => true]);
            $this->line(Artisan::output());

            // 2. Executa seeders básicos
            $this->info('Executando seeders...');
            Artisan::call('db:seed', ['--force' => true]);
            $this->line(Artisan::output());

            // 3. Cria link de storage
            $this->info('Criando link de storage...');
            Artisan::call('storage:link');

            // 4. Limpa caches
            $this->info('Limpando caches...');
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            // 5. Gera chave da aplicação se necessário
            if (empty(config('app.key'))) {
                $this->info('Gerando chave da aplicação...');
                Artisan::call('key:generate', ['--force' => true]);
            }

            // 6. Configura permissões básicas
            $this->info('Configurando permissões...');
            $this->setupPermissions();

            // 7. Cria usuário admin padrão
            $this->info('Configurando usuário administrador...');
            $this->createAdminUser();

            // 8. Configura gateways padrão
            $this->info('Configurando gateways de pagamento...');
            $this->setupDefaultGateways();

            // 9. Configura templates de email
            $this->info('Configurando templates de notificação...');
            $this->setupEmailTemplates();

            $this->info('');
            $this->info('✅ Instalação concluída com sucesso!');
            $this->info('');
            $this->info('📋 Próximos passos:');
            $this->info('1. Configure o cron: * * * * * php ' . base_path() . '/artisan cron:master');
            $this->info('2. Configure as filas: php artisan queue:work');
            $this->info('3. Acesse o painel admin e configure os gateways');
            $this->info('4. Configure os templates de email/WhatsApp');
            $this->info('');

            return 0;

        } catch (\Exception $e) {
            $this->error('Erro durante a instalação: ' . $e->getMessage());
            return 1;
        }
    }

    private function isInstalled(): bool
    {
        try {
            return \Schema::hasTable('settings') && 
                   \App\Models\Setting::where('key', 'system.installed')->exists();
        } catch (\Exception) {
            return false;
        }
    }

    private function setupPermissions(): void
    {
        $directories = [
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                chmod($dir, 0755);
            }
        }
    }

    private function createAdminUser(): void
    {
        $email = $this->ask('Email do administrador', 'admin@hostpanel.com');
        $password = $this->secret('Senha do administrador');
        
        if (!$password) {
            $password = 'admin123';
            $this->warn('Usando senha padrão: admin123');
        }

        \App\Models\Admin::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrador',
                'password' => bcrypt($password),
                'active' => true,
            ]
        );

        $this->info("Usuário admin criado: {$email}");
    }

    private function setupDefaultGateways(): void
    {
        $gateways = [
            [
                'name' => 'PagHiper',
                'slug' => 'paghiper',
                'driver' => 'paghiper',
                'active' => false,
                'supports_recurring' => false,
                'supports_refund' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Mercado Pago',
                'slug' => 'mercadopago',
                'driver' => 'mercadopago',
                'active' => false,
                'supports_recurring' => false,
                'supports_refund' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'PagBank',
                'slug' => 'pagbank',
                'driver' => 'pagbank',
                'active' => false,
                'supports_recurring' => false,
                'supports_refund' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($gateways as $gateway) {
            \App\Models\Gateway::firstOrCreate(
                ['slug' => $gateway['slug']],
                $gateway
            );
        }
    }

    private function setupEmailTemplates(): void
    {
        $templates = [
            [
                'name' => 'Bem-vindo',
                'slug' => 'welcome',
                'subject' => 'Bem-vindo ao {company_name}!',
                'body_html' => '<p>Olá {name},</p><p>Bem-vindo ao nosso sistema!</p>',
                'active' => true,
            ],
            [
                'name' => 'Fatura Gerada',
                'slug' => 'invoice_created',
                'subject' => 'Nova fatura #{invoice_number}',
                'body_html' => '<p>Olá {name},</p><p>Uma nova fatura foi gerada no valor de {amount}.</p>',
                'active' => true,
            ],
            [
                'name' => 'Pagamento Confirmado',
                'slug' => 'payment_confirmed',
                'subject' => 'Pagamento confirmado - Fatura #{invoice_number}',
                'body_html' => '<p>Olá {name},</p><p>Seu pagamento foi confirmado!</p>',
                'active' => true,
            ],
        ];

        foreach ($templates as $template) {
            \App\Models\EmailTemplate::firstOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        // Marca sistema como instalado
        \App\Models\Setting::set('system.installed', true);
        \App\Models\Setting::set('system.installed_at', now()->toISOString());
    }
}