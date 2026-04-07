<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Geral
            ['group' => 'general', 'key' => 'app.name',          'value' => 'HostPanel',              'type' => 'string',  'label' => 'Nome do Sistema'],
            ['group' => 'general', 'key' => 'app.short_name',     'value' => 'HostPanel',              'type' => 'string',  'label' => 'Nome Curto (PWA)'],
            ['group' => 'general', 'key' => 'app.description',    'value' => 'Painel de hospedagem completo', 'type' => 'string', 'label' => 'Descrição'],
            ['group' => 'general', 'key' => 'app.logo',           'value' => '',                       'type' => 'string',  'label' => 'Logo URL'],
            ['group' => 'general', 'key' => 'app.favicon',        'value' => '',                       'type' => 'string',  'label' => 'Favicon URL'],
            ['group' => 'general', 'key' => 'app.theme_color',    'value' => '#1a56db',                'type' => 'string',  'label' => 'Cor Principal'],
            ['group' => 'general', 'key' => 'app.company_name',   'value' => 'HostPanel Ltda',         'type' => 'string',  'label' => 'Nome da Empresa'],
            ['group' => 'general', 'key' => 'app.company_email',  'value' => 'contato@hostpanel.com',  'type' => 'string',  'label' => 'E-mail da Empresa'],
            ['group' => 'general', 'key' => 'app.company_phone',  'value' => '',                       'type' => 'string',  'label' => 'Telefone'],
            ['group' => 'general', 'key' => 'app.company_address','value' => '',                       'type' => 'string',  'label' => 'Endereço'],

            // Financeiro
            ['group' => 'billing', 'key' => 'billing.currency',                 'value' => 'BRL',       'type' => 'string',  'label' => 'Moeda'],
            ['group' => 'billing', 'key' => 'billing.invoice_prefix',           'value' => 'FAT',       'type' => 'string',  'label' => 'Prefixo da Fatura'],
            ['group' => 'billing', 'key' => 'billing.invoice_due_days',         'value' => '7',         'type' => 'integer', 'label' => 'Dias de Vencimento da Fatura'],
            ['group' => 'billing', 'key' => 'billing.generate_days_before',     'value' => '7',         'type' => 'integer', 'label' => 'Gerar Fatura X dias antes do vencimento'],
            ['group' => 'billing', 'key' => 'billing.suspension_grace_days',    'value' => '3',         'type' => 'integer', 'label' => 'Dias de carência antes de suspender'],
            ['group' => 'billing', 'key' => 'billing.termination_grace_days',   'value' => '30',        'type' => 'integer', 'label' => 'Dias para encerrar após suspensão'],
            ['group' => 'billing', 'key' => 'billing.late_fee_percent',         'value' => '2',         'type' => 'decimal', 'label' => 'Multa por atraso (%)'],
            ['group' => 'billing', 'key' => 'billing.interest_daily_percent',   'value' => '0.033',     'type' => 'decimal', 'label' => 'Juros diários (%)'],
            ['group' => 'billing', 'key' => 'billing.tax_enabled',              'value' => '0',         'type' => 'boolean', 'label' => 'Habilitar Impostos'],
            ['group' => 'billing', 'key' => 'billing.tax_rate',                 'value' => '0',         'type' => 'decimal', 'label' => 'Alíquota de Imposto (%)'],

            // E-mail
            ['group' => 'email', 'key' => 'mail.from_name',    'value' => 'HostPanel',             'type' => 'string', 'label' => 'Nome do Remetente'],
            ['group' => 'email', 'key' => 'mail.from_address', 'value' => 'noreply@hostpanel.com', 'type' => 'string', 'label' => 'E-mail Remetente'],
            ['group' => 'email', 'key' => 'mail.signature',    'value' => 'Equipe HostPanel',      'type' => 'string', 'label' => 'Assinatura'],

            // Segurança
            ['group' => 'security', 'key' => 'security.max_login_attempts', 'value' => '5',  'type' => 'integer', 'label' => 'Tentativas máx. de login'],
            ['group' => 'security', 'key' => 'security.lockout_minutes',    'value' => '15', 'type' => 'integer', 'label' => 'Minutos de bloqueio'],
            ['group' => 'security', 'key' => 'security.session_timeout',    'value' => '120','type' => 'integer', 'label' => 'Timeout de sessão (min)'],

            // Suporte
            ['group' => 'support', 'key' => 'support.ticket_auto_close_days', 'value' => '7', 'type' => 'integer', 'label' => 'Fechar tickets respondidos após (dias)'],
            ['group' => 'support', 'key' => 'support.rating_enabled',          'value' => '1', 'type' => 'boolean', 'label' => 'Habilitar avaliação de tickets'],

            // Integrações — Evolution API (WhatsApp)
            ['group' => 'integrations', 'key' => 'integration.whatsapp.url',      'value' => '', 'type' => 'encrypted', 'label' => 'Evolution API — URL',          'description' => 'Ex: https://api.evolution.com (sem barra no final)'],
            ['group' => 'integrations', 'key' => 'integration.whatsapp.api_key',  'value' => '', 'type' => 'encrypted', 'label' => 'Evolution API — API Key',       'description' => 'Chave de autenticação da API Evolution'],
            ['group' => 'integrations', 'key' => 'integration.whatsapp.instance', 'value' => '', 'type' => 'encrypted', 'label' => 'Evolution API — Instância',     'description' => 'Nome da instância configurada na Evolution API'],

            // Módulos
            ['group' => 'modules', 'key' => 'modules.whatsapp',  'value' => '0', 'type' => 'boolean', 'label' => 'Módulo WhatsApp'],
            ['group' => 'modules', 'key' => 'modules.domains',   'value' => '1', 'type' => 'boolean', 'label' => 'Módulo Domínios'],
            ['group' => 'modules', 'key' => 'modules.kanban',    'value' => '1', 'type' => 'boolean', 'label' => 'Módulo Kanban'],

            // Aparência
            ['group' => 'appearance', 'key' => 'active_theme',      'value' => 'default', 'type' => 'string',  'label' => 'Tema Ativo',           'description' => 'Nome da pasta do tema em resources/themes/'],
            ['group' => 'appearance', 'key' => 'company_logo',       'value' => '',        'type' => 'string',  'label' => 'Logo da Empresa (URL)', 'description' => 'URL da imagem de logo exibida no cabeçalho público'],
            ['group' => 'appearance', 'key' => 'company_logo_dark',  'value' => '',        'type' => 'string',  'label' => 'Logo Dark (URL)',       'description' => 'Logo versão clara para fundos escuros'],

            // Manutenção
            ['group' => 'maintenance', 'key' => 'maintenance.enabled',     'value' => '0',  'type' => 'boolean', 'label' => 'Modo Manutenção',       'description' => 'Ativar página de manutenção para visitantes'],
            ['group' => 'maintenance', 'key' => 'maintenance.message',     'value' => 'Estamos realizando melhorias no sistema. Voltaremos em breve!', 'type' => 'string', 'label' => 'Mensagem de Manutenção'],
            ['group' => 'maintenance', 'key' => 'maintenance.allowed_ips', 'value' => '',   'type' => 'textarea', 'label' => 'IPs Liberados',         'description' => 'Um IP por linha que pode acessar durante manutenção'],
            ['group' => 'maintenance', 'key' => 'maintenance.secret',      'value' => '',   'type' => 'string',  'label' => 'Token de Bypass',        'description' => 'Adicione ?bypass=TOKEN na URL para liberar acesso'],

            // Afiliados
            ['group' => 'affiliate', 'key' => 'affiliate.enabled',            'value' => '1',          'type' => 'boolean', 'label' => 'Sistema de Afiliados',    'description' => 'Ativar programa de afiliados'],
            ['group' => 'affiliate', 'key' => 'affiliate.commission_rate',     'value' => '10',         'type' => 'decimal', 'label' => 'Taxa de Comissão Padrão', 'description' => 'Percentual ou valor fixo de comissão padrão'],
            ['group' => 'affiliate', 'key' => 'affiliate.commission_type',     'value' => 'percentage', 'type' => 'string',  'label' => 'Tipo de Comissão',       'description' => 'percentage ou fixed'],
            ['group' => 'affiliate', 'key' => 'affiliate.min_payout',          'value' => '50',         'type' => 'decimal', 'label' => 'Saque Mínimo (R$)',      'description' => 'Valor mínimo para solicitar saque'],
            ['group' => 'affiliate', 'key' => 'affiliate.min_commission',      'value' => '1',          'type' => 'decimal', 'label' => 'Comissão Mínima (R$)',   'description' => 'Valor mínimo de comissão por fatura'],
            ['group' => 'affiliate', 'key' => 'affiliate.auto_approve',        'value' => '0',          'type' => 'boolean', 'label' => 'Auto-aprovar Comissões', 'description' => 'Se ativo, comissões são creditadas automaticamente'],
            ['group' => 'affiliate', 'key' => 'affiliate.only_first_invoice',  'value' => '0',          'type' => 'boolean', 'label' => 'Apenas Primeira Fatura', 'description' => 'Gerar comissão apenas na primeira compra do indicado'],
        ];

        foreach ($settings as $s) {
            Setting::firstOrCreate(['key' => $s['key']], $s);
        }

        $this->command->info('Settings seeded.');
    }
}
