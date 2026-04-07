# HostPanel

> Plataforma completa de gerenciamento de hospedagem, domínios e serviços digitais.  
> Alternativa brasileira ao WHMCS, construída com **Laravel 12**, Blade, Alpine.js e Bootstrap 5.

---

## Visão Geral

O **HostPanel** é um sistema de gestão para provedores de hospedagem e revenda de serviços digitais. Ele centraliza clientes, pedidos, faturas, provisionamento automático de servidores, notificações e gateways de pagamento em uma única plataforma.

---

## Funcionalidades

### Área do Cliente
- Painel com resumo de serviços, faturas e notificações
- Auto login via token seguro (link enviado por e-mail e WhatsApp)
- Solicitação de acesso ao painel do servidor (cPanel / AAPanel)
- Histórico de acessos e gerenciamento de perfil

### Área Administrativa
- Gestão de clientes, pedidos, serviços e faturas
- Gestão de produtos e categorias
- Gestão de servidores com monitoramento em tempo real (CPU, RAM, Disco, Rede, Uptime)
- Health check automático via agendador com histórico em gráficos
- Sistema de temas (multi-tema) com suporte a override de views e assets
- CMS integrado para páginas e conteúdo estático

### Notificações
- Envio de e-mail com fila dedicada (rate limit 60/min, anti-spam headers)
- Envio de WhatsApp via **Evolution API** com fila dedicada (rate limit 20/hora, delay humanizado 5–15s)
- Logs de notificações com status e rastreamento de erros
- Suporte a templates personalizáveis com variáveis dinâmicas

### Gateways de Pagamento
| Gateway | Boleto | PIX | Cartão |
|---------|--------|-----|--------|
| PagHiper | ✅ | ✅ | — |
| MercadoPago | — | ✅ | ✅ |
| EfíPro (Gerencianet) | ✅ | ✅ | — |
| Banco Inter | — | ✅ | — |
| Banco do Brasil | ✅ | ✅ | — |
| PagBank | — | ✅ | ✅ |

### Módulos de Servidor
- **WHM/cPanel** — criação, suspensão, cancelamento, listagem de contas, test de conectividade
- **AAPanel** — provisionamento automatizado via API

### Status da Rede (Público)
- Página pública `/status` com status de todos os servidores
- API JSON em `/status/api` para integrações externas

---

## Stack Tecnológica

| Camada | Tecnologia |
|--------|-----------|
| Framework | Laravel 12 |
| Frontend | Blade + Alpine.js + Bootstrap 5 |
| Build | Vite |
| Banco de dados | MySQL / MariaDB |
| Filas | Laravel Queues (database ou Redis) |
| Agendamento | Laravel Scheduler |
| WhatsApp | Evolution API |
| Autenticação | Multi-guard (admin / cliente) |

---

## Instalação

### Requisitos
- PHP >= 8.2
- Composer 2
- Node.js >= 18
- MySQL 8 ou MariaDB 10.6+
- Extensões PHP: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `gd`, `zip`

### Passo a Passo

```bash
# 1. Clonar o repositório
git clone <url-do-repositorio> hostpanel
cd hostpanel

# 2. Instalar dependências PHP
composer install --no-dev --optimize-autoloader

# 3. Instalar dependências JS e compilar assets
npm install && npm run build

# 4. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 5. Configurar banco de dados no .env e executar migrações
php artisan migrate

# 6. Popular configurações e gateways iniciais
php artisan db:seed --class=SettingSeeder
php artisan db:seed --class=GatewaySeeder

# 7. Criar tabela de filas (se usar driver database)
php artisan queue:table && php artisan migrate

# 8. Criar link de storage
php artisan storage:link

# 9. Definir permissões (Linux)
chmod -R 775 storage bootstrap/cache
```

---

## Configuração do `.env`

```env
APP_NAME="HostPanel"
APP_URL=https://seudominio.com.br

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hostpanel
DB_USERNAME=root
DB_PASSWORD=senha

MAIL_MAILER=smtp
MAIL_HOST=smtp.seuprovedor.com
MAIL_PORT=587
MAIL_USERNAME=seu@email.com
MAIL_PASSWORD=senha
MAIL_FROM_ADDRESS=noreply@seudominion.com.br
MAIL_FROM_NAME="${APP_NAME}"

# Evolution API (WhatsApp)
EVOLUTION_API_URL=https://api.evolution.com
EVOLUTION_API_KEY=sua_chave_api
EVOLUTION_INSTANCE=nome_instancia

# Filas (recomendado Redis em produção)
QUEUE_CONNECTION=database
```

---

## Workers de Fila

As filas garantem envio seguro de e-mails e WhatsApp sem risco de blacklist ou banimento.

```bash
# Worker de e-mail (60 mensagens/min, 3 tentativas, backoff progressivo)
php artisan queue:work --queue=email --tries=3 --backoff=60,300,600 --sleep=3 --timeout=30

# Worker de WhatsApp (20 mensagens/hora, delay humanizado 5–15s)
php artisan queue:work --queue=whatsapp --tries=3 --backoff=120,600,1800 --sleep=10 --timeout=20
```

> Em produção, use **Supervisor** para manter os workers ativos. Consulte Admin → Cron Jobs para a configuração completa pronta para copiar.

---

## Agendador (Cron)

Adicione uma única entrada no crontab do servidor:

```cron
* * * * * php /caminho/para/hostpanel/artisan schedule:run >> /dev/null 2>&1
```

Tarefas agendadas incluídas:
- Verificação de saúde dos servidores (a cada 10 minutos)
- Geração de faturas (diariamente)
- Envio de notificações de vencimento (diariamente)
- Limpeza de tokens de auto login expirados (diariamente às 03:00)

---

## Sistema de Temas

```
resources/themes/{nome}/
    theme.json        ← manifesto obrigatório
    views/            ← override de views (opcional)
    assets/           ← CSS, JS, imagens
```

Ative um tema em **Admin → Temas** ou via `settings.active_theme`.

---

## Acesso Inicial

Após a instalação, crie o primeiro administrador:

```bash
php artisan tinker
\App\Models\Admin::create([
    'name'     => 'Administrador',
    'email'    => 'admin@seudominio.com.br',
    'password' => bcrypt('senha_segura'),
]);
```

Acesso admin: `/admin/login`  
Acesso cliente: `/cliente/login`

---

## Licença

Proprietário — todos os direitos reservados.  
Uso restrito ao titular da licença.

---

## Changelog

Consulte o arquivo [CHANGELOG.md](CHANGELOG.md) para o histórico completo de alterações.
