# Changelog

Todas as alterações notáveis deste projeto serão documentadas neste arquivo.

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/).

---

## [Não Lançado]

### Segurança
- Credenciais de servidores (`api_key`, `api_hash`, `password`) agora armazenadas criptografadas na tabela `servers` via cast `encrypted` do Eloquent (AES-256-CBC + HMAC)
- Migration `2026_04_07_100000_encrypt_server_credentials` re-criptografa valores existentes em texto plano de forma idempotente (detecta se já é ciphertext antes de criptografar)
- Coluna `password` adicionada à tabela `servers` (estava no modelo mas ausente na migration)
- `api_hash` alterado de `VARCHAR(255)` para `TEXT` para suportar ciphertext AES
- `$hidden` do modelo `Server` expandido para incluir `password`
- Gateways de pagamento (PagHiper, MercadoPago, Efí, Banco Inter, Banco do Brasil, PagBank) confirmados como já armazenando credenciais criptografadas via `setSettingsEncryptedAttribute()` — sem leitura de `.env`
- `.env.example`: removidas todas as variáveis legadas de gateways (`PAYPAL_*`, `PIX_GATEWAY_*`, `STRIPE_*`, `WHM_*`) e credenciais de servidores, substituídas por aviso de migração
- Credenciais da Evolution API (URL, API Key, Instância) migradas do `.env` para a tabela `settings` no banco de dados, armazenadas com criptografia AES-256 via `Crypt::encryptString()`
- Eliminado o método `saveWhatsApp()` que gravava diretamente no arquivo `.env` em disco — vetor de exposição em caso de leitura indevida do sistema de arquivos
- `Setting::get()` agora descriptografa automaticamente campos do tipo `encrypted` na leitura
- `Setting::setEncrypted()` novo método que sempre armazena o valor criptografado
- `SettingController::update()` detecta campos `type=encrypted` e nunca sobrescreve com valor vazio (manter o atual se campo deixado em branco)
- `.env.example` atualizado com aviso de que as credenciais WhatsApp estão no banco

### Alterado
- `WhatsAppService`: lê URL, API Key e instância via `Setting::get('integration.whatsapp.*')` com fallback para `config()` para compatibilidade
- `SendWhatsAppJob`: idem, lê credenciais do banco no momento da execução do job
- `SettingController::testWhatsApp()`: lê credenciais do banco em vez do `config()`
- Rota `POST /admin/configuracoes/whatsapp/salvar` removida (era usada pelo método que gravava no `.env`)
- Admin → Configurações → Integrações: campos `encrypted` renderizados como `<input type="password">` com ícone de cadeado, toggle mostrar/ocultar, e indicador visual "armazenado criptografado"
- Botão "Testar Conexão WhatsApp" adicionado na aba Integrações das configurações

### Adicionado
- Página pública `/status` com status de todos os servidores em tempo real
- API JSON pública em `/status/api` para integrações externas
- Link "Status da Rede" no sidebar do admin (abre em nova aba)
- Painel completo de monitoramento de servidor: progress bars CPU/RAM/Disco, seção de rede com latência, packet loss, entrada/saída, barra de qualidade da conexão
- Gráficos em abas separadas: "Carga" (CPU/RAM/Disco) e "Rede" (Latência/Packet Loss) com eixos Y duais
- Auto-refresh a cada 30s no painel de servidor com countdown visual

---

## [1.4.0] — 2025-04-07

### Adicionado
- **Filas de Email** (`SendEmailJob`): fila dedicada `email`, rate limit de 60 mensagens/minuto, 3 tentativas com backoff progressivo 60s→300s→600s, headers anti-spam (`X-Mailer`, `X-Priority`, `Precedence: bulk`, `List-Unsubscribe`)
- **Filas de WhatsApp** (`SendWhatsAppJob`): fila dedicada `whatsapp`, rate limit de 20 mensagens/hora, delay humanizado aleatório 5–15s (texto) / 8–20s (mídia), 3 tentativas com backoff 2min→10min→30min
- `RateLimiter::for('email')` e `RateLimiter::for('whatsapp')` registrados no `AppServiceProvider`
- Seção "Queue Workers" na página Admin → Cron Jobs com comandos prontos, configuração Supervisor e `.env` mínimo

### Alterado
- `InvoiceNotificationService::sendMail()` refatorado para disparar `SendEmailJob` (era `Mail::send` síncrono)
- `WhatsAppService::sendText()` e `sendMedia()` refatorados para disparar `SendWhatsAppJob`
- `NotificationService::sendEmail()` e `sendWhatsApp()` refatorados para usar os respectivos jobs

---

## [1.3.0] — 2025-04-06

### Adicionado
- **Monitoramento de Servidores**: `ServerHealthLog` com campos `latency_ms`, `packet_loss_pct`, `network_in_mbps`, `network_out_mbps`, `network_status`, `uptime_seconds`
- `ServerHealthCheckJob` com suporte a múltiplos módulos via `ServerModuleManager`
- `healthStatus()` e `healthHistory()` no `ServerController` retornando dados completos de rede
- Agendamento automático do health check a cada 10 minutos
- Uptime formatado (`uptime_human`) como accessor no modelo `ServerHealthLog`

---

## [1.2.0] — 2025-04-05

### Adicionado
- **Auto Login Público**: tokens UUID com validade `expires_at=date_due` enviados em faturas por e-mail e WhatsApp
- `AutoLoginService`: métodos `forInvoice`, `onDemand`, `forAdmin`, `resolve`, `purgeExpired`
- `PublicAutoLoginController`: valida token e redireciona ou exibe tela de link inválido
- Rotas: `GET /acesso/{token}`, `POST /cliente/servicos/{service}/solicitar-acesso`, `POST /admin/servicos/{service}/enviar-acesso`
- Limpeza automática de tokens expirados diariamente às 03:00

---

## [1.1.0] — 2025-04-04

### Adicionado
- **Sistema de Temas Multi-tema**: `ThemeManager` com `all()`, `getActive()`, `activate()`, `assetUrl()`, `boot()`
- `ThemeServiceProvider` registrado em `bootstrap/providers.php`
- Diretivas Blade: `@themeAsset`, `@themeColor`, `@themeVar`
- Rota pública `GET /tema-assets/{theme}/{path}` para servir assets de temas
- Tema **kdkhost**: fiel ao site kdkhost.com.br com navbar dark, hero gradient, mega-dropdown e footer dark
- Painel Admin → Temas com grid de preview e ativação

---

## [1.0.0] — 2025-04-01

### Adicionado
- Estrutura base do projeto em Laravel 12 com multi-auth (admin / cliente)
- **Módulos de Servidor**: `WhmModule` (WHM/cPanel) e `AAPanelModule` com `ServerModuleManager`
- **Gateways de Pagamento**: PagHiper, MercadoPago, EfíPro, Banco Inter, Banco do Brasil, PagBank
- Provisionamento automático de serviços via `ProvisioningService`
- Gestão completa de clientes, pedidos, serviços, faturas e produtos
- `NotificationService` e `InvoiceNotificationService` com suporte a e-mail e WhatsApp
- `NotificationLog` para rastreamento de envios com status e erros
- Integração com **Evolution API** para envio de mensagens WhatsApp
- CMS para páginas estáticas
- Sidebar admin com navegação completa e seções organizadas
- Instalador via interface web (`/install`)
- Seeders iniciais: `SettingSeeder`, `GatewaySeeder`
</CodeContent>
</invoke>
