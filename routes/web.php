<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Client;
use App\Http\Controllers\ImpersonationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhooks de Gateway (públicos, sem CSRF, sem auth)
| URL por fatura: /webhook/{driver}/{invoice_id}
|--------------------------------------------------------------------------
*/
Route::post('/webhook/{driver}/{invoice_id}',
    [\App\Http\Controllers\Webhook\GatewayWebhookController::class, 'handle']
)->name('webhook.gateway')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| Assets de Tema — serve CSS/JS/imagens dos temas instalados
|--------------------------------------------------------------------------
*/
Route::get('/tema-assets/{theme}/{path}',
    [\App\Http\Controllers\ThemeAssetController::class, 'serve']
)->where('path', '.*')->name('theme.asset');

/*
|--------------------------------------------------------------------------
| Status da Rede — página pública de status dos servidores
|--------------------------------------------------------------------------
*/
Route::get('/status',     [\App\Http\Controllers\StatusController::class, 'index'])->name('status.index');
Route::get('/status/api', [\App\Http\Controllers\StatusController::class, 'api'])->name('status.api');

/*
|--------------------------------------------------------------------------
| Auto Login Público — link enviado em faturas e por solicitação
| Token UUID com validade — sem autenticação necessária
|--------------------------------------------------------------------------
*/
Route::get('/acesso/{token}',
    [\App\Http\Controllers\PublicAutoLoginController::class, 'access']
)->name('autologin.access')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| Instalador
|--------------------------------------------------------------------------
*/
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/',         [\App\Http\Controllers\InstallerController::class, 'index'])->name('index');
    Route::post('/check',   [\App\Http\Controllers\InstallerController::class, 'check'])->name('check');
    Route::post('/run',     [\App\Http\Controllers\InstallerController::class, 'run'])->name('run');
    Route::get('/complete', [\App\Http\Controllers\InstallerController::class, 'complete'])->name('complete');
});

/*
|--------------------------------------------------------------------------
| Frontend Público
|--------------------------------------------------------------------------
*/
Route::get('/',            [\App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/planos',      [\App\Http\Controllers\HomeController::class, 'plans'])->name('plans');
Route::get('/planos/{slug}', [\App\Http\Controllers\HomeController::class, 'planDetail'])->name('plan.detail');
Route::get('/dominio',     [\App\Http\Controllers\HomeController::class, 'domainSearch'])->name('domain.search');
Route::get('/paginas/{slug}', [\App\Http\Controllers\HomeController::class, 'page'])->name('page');
Route::get('/base-conhecimento', [\App\Http\Controllers\HomeController::class, 'knowledgeBase'])->name('kb');
Route::get('/base-conhecimento/{slug}', [\App\Http\Controllers\HomeController::class, 'kbArticle'])->name('kb.article');
Route::get('/anuncios',    [\App\Http\Controllers\HomeController::class, 'announcements'])->name('announcements');

/*
|--------------------------------------------------------------------------
| Loja / Fluxo de Vendas Público (estilo WHMCS)
|--------------------------------------------------------------------------
*/
Route::get('/loja',              [\App\Http\Controllers\HomeController::class, 'store'])->name('store');
Route::get('/pedir/{slug}',      [\App\Http\Controllers\HomeController::class, 'orderProduct'])->name('order.product');
Route::get('/carrinho',          [\App\Http\Controllers\HomeController::class, 'cart'])->name('cart');
Route::post('/carrinho/validar-cupom', [\App\Http\Controllers\Client\OrderController::class, 'validateCoupon'])->name('cart.coupon.validate')->withoutMiddleware(['auth.client']);

/*
|--------------------------------------------------------------------------
| ViaCEP (público / cliente)
|--------------------------------------------------------------------------
*/
Route::get('/api/viacep/{cep}', [\App\Http\Controllers\Client\ProfileController::class, 'lookupCep'])->name('viacep');
Route::get('/api/dominio/verificar', [\App\Http\Controllers\HomeController::class, 'checkDomain'])->name('domain.check');

/*
|--------------------------------------------------------------------------
| Autenticação de Clientes
|--------------------------------------------------------------------------
*/
Route::prefix('cliente')->name('client.')->group(function () {
    Route::middleware('guest:client')->group(function () {
        Route::get('/entrar',                [Auth\ClientAuthController::class, 'showLogin'])->name('login');
        Route::post('/entrar',               [Auth\ClientAuthController::class, 'login'])->name('login.post');
        Route::get('/cadastrar',             [Auth\ClientAuthController::class, 'showRegister'])->name('register');
        Route::post('/cadastrar',            [Auth\ClientAuthController::class, 'register'])->name('register.post');
        Route::get('/esqueci-senha',         [Auth\ClientAuthController::class, 'showForgotPassword'])->name('password.forgot');
        Route::post('/esqueci-senha',        [Auth\ClientAuthController::class, 'sendResetLink'])->name('password.email');
        Route::get('/redefinir-senha',       [Auth\ClientAuthController::class, 'showResetPassword'])->name('password.reset');
        Route::post('/redefinir-senha',      [Auth\ClientAuthController::class, 'resetPassword'])->name('password.update');
    });

    Route::post('/sair', [Auth\ClientAuthController::class, 'logout'])->name('logout');

    /*
    |------- Área Autenticada do Cliente -------
    */
    Route::middleware(['auth.client', 'impersonation.banner'])->group(function () {
        Route::get('/dashboard',            [Client\DashboardController::class, 'index'])->name('dashboard');

        // Serviços
        Route::prefix('servicos')->name('services.')->group(function () {
            Route::get('/',              [Client\ServiceController::class, 'index'])->name('index');
            Route::get('/{service}',     [Client\ServiceController::class, 'show'])->name('show');
            Route::post('/{service}/cpanel-login', [Client\ServiceController::class, 'cpanelLogin'])->name('cpanel.login');
            Route::post('/{service}/alterar-senha', [Client\ServiceController::class, 'changePassword'])->name('change.password');
            Route::post('/{service}/cancelar',      [Client\ServiceController::class, 'cancelRequest'])->name('cancel');
            Route::post('/{service}/upgrade',       [Client\ServiceController::class, 'upgradeRequest'])->name('upgrade');
            Route::get('/{service}/autologin',          [Client\AutoLoginController::class, 'login'])->name('autologin');
            Route::get('/{service}/uso',                 [Client\AutoLoginController::class, 'usage'])->name('usage');
            Route::post('/{service}/solicitar-acesso',   [Client\AutoLoginController::class, 'requestAccess'])->name('request.access');
        });

        // Faturas
        Route::prefix('faturas')->name('invoices.')->group(function () {
            Route::get('/',                    [Client\InvoiceController::class, 'index'])->name('index');
            Route::get('/{invoice}',           [Client\InvoiceController::class, 'show'])->name('show');
            Route::get('/{invoice}/pdf',       [Client\InvoiceController::class, 'pdf'])->name('pdf');
            Route::post('/{invoice}/pagar',    [Client\InvoiceController::class, 'pay'])->name('pay');
            Route::post('/{invoice}/credito',  [Client\InvoiceController::class, 'applyCredit'])->name('credit');
        });

        // Tickets
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/',              [Client\TicketController::class, 'index'])->name('index');
            Route::get('/criar',         [Client\TicketController::class, 'create'])->name('create');
            Route::post('/',             [Client\TicketController::class, 'store'])->name('store');
            Route::get('/{ticket}',      [Client\TicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/responder', [Client\TicketController::class, 'reply'])->name('reply');
            Route::post('/{ticket}/fechar',    [Client\TicketController::class, 'close'])->name('close');
            Route::post('/{ticket}/avaliar',   [Client\TicketController::class, 'rate'])->name('rate');
        });

        // Pedidos
        Route::prefix('pedidos')->name('orders.')->group(function () {
            Route::get('/',          [Client\OrderController::class, 'index'])->name('index');
            Route::get('/catalogo',  [Client\OrderController::class, 'catalog'])->name('catalog');
            Route::get('/checkout',  [Client\OrderController::class, 'checkout'])->name('checkout');
            Route::post('/finalizar',[Client\OrderController::class, 'place'])->name('place');
            Route::post('/do-carrinho', [Client\OrderController::class, 'placeFromCart'])->name('place.cart');
            Route::post('/validar-cupom', [Client\OrderController::class, 'validateCoupon'])->name('coupon.validate');
            Route::get('/produto/{product}', [Client\OrderController::class, 'product'])->name('product');
            Route::get('/{order}',   [Client\OrderController::class, 'show'])->name('show');
        });

        // Afiliados
        Route::prefix('afiliados')->name('affiliates.')->group(function () {
            Route::get('/',                [Client\AffiliateController::class, 'index'])->name('index');
            Route::post('/inscrever',      [Client\AffiliateController::class, 'enroll'])->name('enroll');
            Route::post('/saque',          [Client\AffiliateController::class, 'requestPayout'])->name('payout');
            Route::put('/dados-pagamento', [Client\AffiliateController::class, 'updatePaymentInfo'])->name('payment.info');
        });

        // Perfil
        Route::prefix('perfil')->name('profile.')->group(function () {
            Route::get('/',                [Client\ProfileController::class, 'show'])->name('show');
            Route::put('/',                [Client\ProfileController::class, 'update'])->name('update');
            Route::put('/senha',           [Client\ProfileController::class, 'changePassword'])->name('password');
            Route::get('/notificacoes',    [Client\ProfileController::class, 'notifications'])->name('notifications');
            Route::post('/notificacoes/{id}/lida', [Client\ProfileController::class, 'markNotificationRead'])->name('notification.read');
            Route::post('/notificacoes/todas-lidas', [Client\ProfileController::class, 'markAllNotificationsRead'])->name('notifications.read.all');
            Route::get('/historico-acesso', [Client\ProfileController::class, 'loginHistory'])->name('login.history');
        });
    });

    // Impersonation stop (accessible during impersonation)
    Route::post('/encerrar-impersonation', [ImpersonationController::class, 'stop'])->name('impersonation.stop')->middleware('auth.client');
});

/*
|--------------------------------------------------------------------------
| Painel Administrativo
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Auth
    Route::middleware('guest:admin')->group(function () {
        Route::get('/entrar',  [Auth\AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/entrar', [Auth\AdminAuthController::class, 'login'])->name('login.post');
    });
    Route::post('/sair', [Auth\AdminAuthController::class, 'logout'])->name('logout');

    /*
    |------- Admin Autenticado -------
    */
    Route::middleware('auth.admin')->group(function () {
        Route::get('/dashboard',        [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats',  [Admin\DashboardController::class, 'stats'])->name('dashboard.stats');

        // Clientes
        Route::prefix('clientes')->name('clients.')->group(function () {
            Route::get('/',                        [Admin\ClientController::class, 'index'])->name('index');
            Route::post('/',                       [Admin\ClientController::class, 'store'])->name('store');
            Route::get('/{client}',                [Admin\ClientController::class, 'show'])->name('show');
            Route::put('/{client}',                [Admin\ClientController::class, 'update'])->name('update');
            Route::delete('/{client}',             [Admin\ClientController::class, 'destroy'])->name('destroy');
            Route::post('/{client}/impersonar',    [Admin\ClientController::class, 'impersonate'])->name('impersonate');
            Route::post('/{client}/credito',       [Admin\ClientController::class, 'addCredit'])->name('credit');
            Route::post('/{client}/status',        [Admin\ClientController::class, 'toggleStatus'])->name('status');
        });

        // Serviços
        Route::prefix('servicos')->name('services.')->group(function () {
            Route::get('/',                            [Admin\ServiceController::class, 'index'])->name('index');
            Route::get('/{service}',                   [Admin\ServiceController::class, 'show'])->name('show');
            Route::post('/{service}/suspender',        [Admin\ServiceController::class, 'suspend'])->name('suspend');
            Route::post('/{service}/reativar',         [Admin\ServiceController::class, 'reactivate'])->name('reactivate');
            Route::post('/{service}/encerrar',         [Admin\ServiceController::class, 'terminate'])->name('terminate');
            Route::post('/{service}/reprovisionamento',[Admin\ServiceController::class, 'reprovision'])->name('reprovision');
            Route::post('/{service}/cpanel-login',     [Admin\ServiceController::class, 'cpanelLogin'])->name('cpanel.login');
            Route::post('/{service}/alterar-senha',    [Admin\ServiceController::class, 'changePassword'])->name('change.password');
            Route::get('/{service}/editar',            [Admin\ServiceController::class, 'edit'])->name('edit');
            Route::put('/{service}',                   [Admin\ServiceController::class, 'update'])->name('update');
            Route::post('/{service}/provisionar',      [Admin\ServiceController::class, 'provision'])->name('provision');
            Route::post('/{service}/notas',            [Admin\ServiceController::class, 'saveNotes'])->name('notes');
            Route::get('/{service}/autologin',         [Admin\AutoLoginController::class, 'login'])->name('autologin');
            Route::get('/{service}/uso',               [Admin\AutoLoginController::class, 'usage'])->name('usage');
            Route::post('/{service}/testar-conexao',   [Admin\AutoLoginController::class, 'test'])->name('test.connection');
            Route::post('/{service}/enviar-acesso',    [Admin\AutoLoginController::class, 'sendLink'])->name('send.access');
        });

        // Faturas
        Route::prefix('faturas')->name('invoices.')->group(function () {
            Route::get('/',                    [Admin\InvoiceController::class, 'index'])->name('index');
            Route::post('/',                   [Admin\InvoiceController::class, 'store'])->name('store');
            Route::get('/{invoice}',           [Admin\InvoiceController::class, 'show'])->name('show');
            Route::post('/{invoice}/pagar',    [Admin\InvoiceController::class, 'markPaid'])->name('paid');
            Route::post('/{invoice}/pay',      [Admin\InvoiceController::class, 'markPaid'])->name('pay');
            Route::post('/{invoice}/cancelar', [Admin\InvoiceController::class, 'cancel'])->name('cancel');
            Route::get('/{invoice}/pdf',       [Admin\InvoiceController::class, 'pdf'])->name('pdf');
            Route::post('/{invoice}/enviar',   [Admin\InvoiceController::class, 'sendEmail'])->name('send');
            Route::post('/{invoice}/multa',    [Admin\InvoiceController::class, 'applyLateFees'])->name('late.fees');
        });

        // Tickets
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/',                       [Admin\TicketController::class, 'index'])->name('index');
            Route::get('/kanban',                 [Admin\TicketController::class, 'kanban'])->name('kanban');
            Route::get('/{ticket}',               [Admin\TicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/responder',    [Admin\TicketController::class, 'reply'])->name('reply');
            Route::put('/{ticket}/status',        [Admin\TicketController::class, 'updateStatus'])->name('status');
            Route::post('/{ticket}/status',       [Admin\TicketController::class, 'updateStatus'])->name('status.post');
            Route::put('/{ticket}/atribuir',      [Admin\TicketController::class, 'assign'])->name('assign');
            Route::post('/{ticket}/atribuir',     [Admin\TicketController::class, 'assign'])->name('assign.post');
            Route::put('/{ticket}/transferir',    [Admin\TicketController::class, 'transfer'])->name('transfer');
        });

        // Servidores
        Route::prefix('servidores')->name('servers.')->group(function () {
            Route::get('/',                        [Admin\ServerController::class, 'index'])->name('index');
            Route::post('/',                       [Admin\ServerController::class, 'store'])->name('store');
            Route::get('/grupos',                  [Admin\ServerController::class, 'groups'])->name('groups');
            Route::post('/grupos',                 [Admin\ServerController::class, 'storeGroup'])->name('groups.store');
            Route::get('/{server}',                [Admin\ServerController::class, 'show'])->name('show');
            Route::put('/{server}',                [Admin\ServerController::class, 'update'])->name('update');
            Route::delete('/{server}',             [Admin\ServerController::class, 'destroy'])->name('destroy');
            Route::post('/{server}/health-check',  [Admin\ServerController::class, 'healthCheck'])->name('health.check');
            Route::get('/{server}/health-status',  [Admin\ServerController::class, 'healthStatus'])->name('health.status');
            Route::get('/{server}/health-history', [Admin\ServerController::class, 'healthHistory'])->name('health.history');
            Route::post('/{server}/status',        [Admin\ServerController::class, 'toggleStatus'])->name('status');
            Route::post('/{server}/testar',        [Admin\ServerController::class, 'testConnectivity'])->name('test');
        });

        // Produtos
        Route::prefix('produtos')->name('products.')->group(function () {
            Route::get('/',                [Admin\ProductController::class, 'index'])->name('index');
            Route::post('/',               [Admin\ProductController::class, 'store'])->name('store');
            Route::get('/grupos',          [Admin\ProductController::class, 'groups'])->name('groups');
            Route::post('/grupos',         [Admin\ProductController::class, 'storeGroup'])->name('groups.store');
            Route::get('/{product}',       [Admin\ProductController::class, 'show'])->name('show');
            Route::put('/{product}',       [Admin\ProductController::class, 'update'])->name('update');
            Route::post('/{product}/status', [Admin\ProductController::class, 'toggleStatus'])->name('status');
            Route::delete('/{product}',   [Admin\ProductController::class, 'destroy'])->name('destroy');
        });

        // Configurações
        Route::prefix('configuracoes')->name('settings.')->group(function () {
            Route::get('/',      [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('index');
            Route::post('/',     [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('update');
            Route::get('/cron',  [\App\Http\Controllers\Admin\SettingController::class, 'cron'])->name('cron');
        });

        // Temas
        Route::prefix('temas')->name('themes.')->group(function () {
            Route::get('/',                      [\App\Http\Controllers\Admin\ThemeController::class, 'index'])->name('index');
            Route::get('/{theme}',               [\App\Http\Controllers\Admin\ThemeController::class, 'show'])->name('show');
            Route::post('/{theme}/ativar',       [\App\Http\Controllers\Admin\ThemeController::class, 'activate'])->name('activate');
        });

        // Kanban
        Route::prefix('kanban')->name('kanban.')->group(function () {
            Route::get('/{type}',           [\App\Http\Controllers\Admin\KanbanController::class, 'board'])->name('board');
            Route::post('/tarefas',         [\App\Http\Controllers\Admin\KanbanController::class, 'storeTask'])->name('task.store');
            Route::put('/tarefas/{task}/mover', [\App\Http\Controllers\Admin\KanbanController::class, 'moveTask'])->name('task.move');
            Route::delete('/tarefas/{task}',    [\App\Http\Controllers\Admin\KanbanController::class, 'destroyTask'])->name('task.destroy');
        });

        // Admins / Permissões
        Route::prefix('administradores')->name('admins.')->group(function () {
            Route::get('/',         [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('index');
            Route::post('/',        [\App\Http\Controllers\Admin\AdminUserController::class, 'store'])->name('store');
            Route::put('/{admin}',  [\App\Http\Controllers\Admin\AdminUserController::class, 'update'])->name('update');
            Route::delete('/{admin}',[\App\Http\Controllers\Admin\AdminUserController::class, 'destroy'])->name('destroy');
        });

        Route::get('/permissoes',        [\App\Http\Controllers\Admin\PermissionController::class, 'index'])->name('permissions');
        Route::post('/permissoes',       [\App\Http\Controllers\Admin\PermissionController::class, 'assign'])->name('permissions.assign');
        Route::get('/permissoes/roles',  [\App\Http\Controllers\Admin\PermissionController::class, 'roles'])->name('permissions.roles');

        // Logs
        Route::get('/logs/impersonacao', [\App\Http\Controllers\Admin\LogController::class, 'impersonation'])->name('logs.impersonation');
        Route::get('/logs/atividade',    [\App\Http\Controllers\Admin\LogController::class, 'activity'])->name('logs.activity');
        Route::get('/logs/autenticacao', [\App\Http\Controllers\Admin\LogController::class, 'auth'])->name('logs.auth');

        // Relatórios
        Route::prefix('relatorios')->name('reports.')->group(function () {
            Route::get('/receita',     [\App\Http\Controllers\Admin\ReportController::class, 'revenue'])->name('revenue');
            Route::get('/servicos',    [\App\Http\Controllers\Admin\ReportController::class, 'services'])->name('services');
            Route::get('/inadimplencia',[\App\Http\Controllers\Admin\ReportController::class, 'overdue'])->name('overdue');
        });

        // Domínios
        Route::prefix('dominios')->name('domains.')->group(function () {
            Route::get('/',        [\App\Http\Controllers\Admin\DomainController::class, 'index'])->name('index');
            Route::get('/tlds',    [\App\Http\Controllers\Admin\DomainController::class, 'tlds'])->name('tlds');
            Route::post('/tlds',   [\App\Http\Controllers\Admin\DomainController::class, 'storeTld'])->name('tlds.store');
        });

        // Coupons
        Route::prefix('cupons')->name('coupons.')->group(function () {
            Route::get('/',        [\App\Http\Controllers\Admin\CouponController::class, 'index'])->name('index');
            Route::post('/',       [\App\Http\Controllers\Admin\CouponController::class, 'store'])->name('store');
            Route::put('/{coupon}',[\App\Http\Controllers\Admin\CouponController::class, 'update'])->name('update');
            Route::delete('/{coupon}',[\App\Http\Controllers\Admin\CouponController::class, 'destroy'])->name('destroy');
        });

        // CMS / Conteúdo
        Route::prefix('cms')->name('cms.')->group(function () {
            Route::get('/paginas',             [\App\Http\Controllers\Admin\CmsController::class, 'pages'])->name('pages');
            Route::post('/paginas',            [\App\Http\Controllers\Admin\CmsController::class, 'storePage'])->name('pages.store');
            Route::put('/paginas/{page}',      [\App\Http\Controllers\Admin\CmsController::class, 'updatePage'])->name('pages.update');
            Route::delete('/paginas/{page}',   [\App\Http\Controllers\Admin\CmsController::class, 'destroyPage'])->name('pages.destroy');
            Route::get('/banners',             [\App\Http\Controllers\Admin\CmsController::class, 'banners'])->name('banners');
            Route::post('/banners',            [\App\Http\Controllers\Admin\CmsController::class, 'storeBanner'])->name('banners.store');
            Route::put('/banners/{banner}',    [\App\Http\Controllers\Admin\CmsController::class, 'updateBanner'])->name('banners.update');
            Route::delete('/banners/{banner}', [\App\Http\Controllers\Admin\CmsController::class, 'destroyBanner'])->name('banners.destroy');
            Route::get('/faqs',                [\App\Http\Controllers\Admin\CmsController::class, 'faqs'])->name('faqs');
            Route::post('/faqs',               [\App\Http\Controllers\Admin\CmsController::class, 'storeFaq'])->name('faqs.store');
            Route::put('/faqs/{faq}',          [\App\Http\Controllers\Admin\CmsController::class, 'updateFaq'])->name('faqs.update');
            Route::delete('/faqs/{faq}',       [\App\Http\Controllers\Admin\CmsController::class, 'destroyFaq'])->name('faqs.destroy');
            Route::get('/anuncios',            [\App\Http\Controllers\Admin\CmsController::class, 'announcements'])->name('announcements');
            Route::post('/anuncios',           [\App\Http\Controllers\Admin\CmsController::class, 'storeAnnouncement'])->name('announcements.store');
            Route::put('/anuncios/{announcement}',    [\App\Http\Controllers\Admin\CmsController::class, 'updateAnnouncement'])->name('announcements.update');
            Route::delete('/anuncios/{announcement}', [\App\Http\Controllers\Admin\CmsController::class, 'destroyAnnouncement'])->name('announcements.destroy');
        });

        // Notificações / Templates
        Route::prefix('notificacoes')->name('notifications.')->group(function () {
            Route::get('/templates-email',     [\App\Http\Controllers\Admin\NotificationController::class, 'emailTemplates'])->name('email.templates');
            Route::put('/templates-email/{t}', [\App\Http\Controllers\Admin\NotificationController::class, 'updateEmailTemplate'])->name('email.templates.update');
            Route::get('/logs',                [\App\Http\Controllers\Admin\NotificationController::class, 'logs'])->name('logs');
        });

        // Gateways
        Route::prefix('gateways')->name('gateways.')->group(function () {
            Route::get('/',                     [\App\Http\Controllers\Admin\GatewayController::class, 'index'])->name('index');
            Route::put('/{gateway}',            [\App\Http\Controllers\Admin\GatewayController::class, 'update'])->name('update');
            Route::get('/{gateway}/configurar', [\App\Http\Controllers\Admin\GatewayController::class, 'configure'])->name('configure');
            Route::put('/{gateway}/configurar', [\App\Http\Controllers\Admin\GatewayController::class, 'configureSave'])->name('configure.save');
            Route::post('/{gateway}/testar',    [\App\Http\Controllers\Admin\GatewayController::class, 'test'])->name('test');
            Route::post('/reembolso/{transaction}', [\App\Http\Controllers\Admin\GatewayController::class, 'refund'])->name('refund');
        });

        // Afiliados
        Route::prefix('afiliados')->name('affiliates.')->group(function () {
            Route::get('/',                          [Admin\AffiliateController::class, 'index'])->name('index');
            Route::get('/stats',                     [Admin\AffiliateController::class, 'stats'])->name('stats');
            Route::get('/comissoes',                 [Admin\AffiliateController::class, 'commissions'])->name('commissions');
            Route::post('/comissoes/{commission}/aprovar', [Admin\AffiliateController::class, 'approveCommission'])->name('commissions.approve');
            Route::post('/comissoes/{commission}/rejeitar',[Admin\AffiliateController::class, 'rejectCommission'])->name('commissions.reject');
            Route::get('/saques',                    [Admin\AffiliateController::class, 'payouts'])->name('payouts');
            Route::post('/saques/{payout}/processar',[Admin\AffiliateController::class, 'processPayout'])->name('payouts.process');
            Route::put('/{affiliate}',               [Admin\AffiliateController::class, 'updateAffiliate'])->name('update');
        });

        // WhatsApp (Evolution API) — salvar e testar conexão
        Route::post('configuracoes/whatsapp/salvar', [\App\Http\Controllers\Admin\SettingController::class, 'saveWhatsApp'])
            ->name('settings.whatsapp.save');
        Route::post('configuracoes/whatsapp/testar', [\App\Http\Controllers\Admin\SettingController::class, 'testWhatsApp'])
            ->name('settings.whatsapp.test');

        // Webhooks recebidos de gateways (legado)
        Route::post('/webhook/{gateway}', [\App\Http\Controllers\Admin\GatewayController::class, 'webhook'])
            ->name('gateway.webhook')->withoutMiddleware(['auth.admin']);
    });
});

/*
|--------------------------------------------------------------------------
| PWA Manifest + Service Worker
|--------------------------------------------------------------------------
*/
Route::get('/manifest.json',    [\App\Http\Controllers\PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/service-worker.js',[\App\Http\Controllers\PwaController::class, 'serviceWorker'])->name('pwa.sw');
