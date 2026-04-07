<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug'       => 'welcome',
                'name'       => 'Boas-vindas',
                'subject'    => 'Bem-vindo ao {app_name}, {name}!',
                'body_html'  => '<h2>Bem-vindo, {name}!</h2><p>Sua conta foi criada com sucesso no {app_name}. Acesse agora: <a href="{url}">Acessar Painel</a></p>',
                'variables'  => ['name', 'app_name', 'url'],
                'active'     => true,
            ],
            [
                'slug'       => 'order_created',
                'name'       => 'Pedido Criado',
                'subject'    => 'Seu pedido #{order_number} foi recebido!',
                'body_html'  => '<h2>Pedido Recebido</h2><p>Olá {name}, seu pedido <strong>#{order_number}</strong> foi criado com sucesso.</p><p>Total: <strong>{total}</strong></p><p><a href="{action_url}">Ver Pedido</a></p>',
                'variables'  => ['name', 'order_number', 'total', 'action_url'],
                'active'     => true,
            ],
            [
                'slug'       => 'payment_approved',
                'name'       => 'Pagamento Confirmado',
                'subject'    => 'Pagamento da fatura #{invoice_number} confirmado!',
                'body_html'  => '<h2>Pagamento Confirmado!</h2><p>Olá {name}, o pagamento da fatura <strong>#{invoice_number}</strong> no valor de <strong>{amount}</strong> foi confirmado.</p><p><a href="{action_url}">Ver Fatura</a></p>',
                'variables'  => ['name', 'invoice_number', 'amount', 'action_url'],
                'active'     => true,
            ],
            [
                'slug'       => 'invoice_overdue',
                'name'       => 'Fatura em Atraso',
                'subject'    => 'Fatura #{invoice_number} em atraso - Regularize agora',
                'body_html'  => '<h2>Fatura em Atraso</h2><p>Olá {name}, sua fatura <strong>#{invoice_number}</strong> venceu em <strong>{due_date}</strong>.</p><p>Valor: <strong>{amount}</strong></p><p>Regularize para evitar a suspensão dos seus serviços.</p><p><a href="{action_url}">Pagar Agora</a></p>',
                'variables'  => ['name', 'invoice_number', 'due_date', 'amount', 'action_url'],
                'active'     => true,
            ],
            [
                'slug'       => 'service_active',
                'name'       => 'Serviço Ativado',
                'subject'    => 'Seu serviço {product} está ativo!',
                'body_html'  => '<h2>Serviço Ativado!</h2><p>Olá {name}, seu serviço <strong>{product}</strong> foi ativado com sucesso.</p><ul><li><strong>Domínio:</strong> {domain}</li><li><strong>Usuário:</strong> {username}</li><li><strong>Servidor:</strong> {server}</li><li><strong>NS1:</strong> {ns1}</li><li><strong>NS2:</strong> {ns2}</li></ul><p><a href="{action_url}">Acessar Painel do Serviço</a></p>',
                'variables'  => ['name', 'product', 'domain', 'username', 'server', 'ns1', 'ns2', 'action_url'],
                'active'     => true,
            ],
            [
                'slug'       => 'service_suspended',
                'name'       => 'Serviço Suspenso',
                'subject'    => 'Seu serviço {product} foi suspenso',
                'body_html'  => '<h2>Serviço Suspenso</h2><p>Olá {name}, seu serviço <strong>{product}</strong> foi suspenso por inadimplência.</p><p>Fatura: #{invoice} — Vencimento: {due_date}</p><p><a href="{action_url}">Regularizar e Reativar</a></p>',
                'variables'  => ['name', 'product', 'invoice', 'due_date', 'action_url'],
                'active'     => true,
            ],
            [
                'slug'       => 'service_expiring',
                'name'       => 'Serviço Próximo do Vencimento',
                'subject'    => 'Seu serviço {product} vence em 7 dias',
                'body_html'  => '<h2>Aviso de Vencimento</h2><p>Olá {name}, seu serviço <strong>{product}</strong> vence em <strong>{due_date}</strong>.</p><p><a href="{action_url}">Renovar Agora</a></p>',
                'variables'  => ['name', 'product', 'due_date', 'action_url'],
                'active'     => true,
            ],
            [
                'slug'       => 'ticket_created',
                'name'       => 'Ticket Aberto',
                'subject'    => 'Ticket #{ticket_num} aberto: {subject}',
                'body_html'  => '<h2>Ticket Aberto</h2><p>Olá {name}, seu ticket <strong>#{ticket_num}</strong> foi aberto com sucesso.</p><p>Assunto: {subject}</p><p>Responderemos em breve!</p><p><a href="{action_url}">Ver Ticket</a></p>',
                'variables'  => ['name', 'ticket_num', 'subject', 'action_url'],
                'active'     => true,
            ],
            [
                'slug'       => 'ticket_reply',
                'name'       => 'Resposta no Ticket',
                'subject'    => 'Nova resposta no ticket #{ticket_num}',
                'body_html'  => '<h2>Seu Ticket foi Respondido</h2><p>Olá {name}, há uma nova resposta no ticket <strong>#{ticket_num}</strong>.</p><p>Assunto: {subject}</p><p><a href="{action_url}">Ver Resposta</a></p>',
                'variables'  => ['name', 'ticket_num', 'subject', 'action_url'],
                'active'     => true,
            ],
            [
                'slug'       => 'password_reset',
                'name'       => 'Redefinição de Senha',
                'subject'    => 'Instruções para redefinir sua senha',
                'body_html'  => '<h2>Redefinição de Senha</h2><p>Olá {name}, recebemos uma solicitação para redefinir sua senha.</p><p><a href="{link}">Clique aqui para redefinir sua senha</a></p><p>Se você não solicitou, ignore este e-mail. O link expira em 60 minutos.</p>',
                'variables'  => ['name', 'link'],
                'active'     => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::firstOrCreate(['slug' => $template['slug']], $template);
        }

        $this->command->info('Email templates seeded.');
    }
}
