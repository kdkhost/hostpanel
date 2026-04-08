<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Painel') — {{ config('app.name') }} Admin</title>

    {{-- Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    {{-- AdminLTE 3 (layout/sidebar structure) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Summernote BS5 --}}
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs5.min.css" rel="stylesheet">
    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.hostpanel-ui-head')

    <style>
        :root {
            --hp-primary: #1a56db;
            --hp-secondary: #1e429f;
        }
        body { font-family: 'Inter', sans-serif; }
        .bg-primary { background-color: var(--hp-primary) !important; }
        .text-primary { color: var(--hp-primary) !important; }
        .btn-primary { background-color: var(--hp-primary); border-color: var(--hp-secondary); }
        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active { background-color: var(--hp-primary); }
        .main-header { border-bottom: 2px solid var(--hp-primary); }
        .card { box-shadow: 0 1px 3px rgba(0,0,0,.1); border: 1px solid #e5e7eb; border-radius: .75rem; }
        .card-header { border-radius: .75rem .75rem 0 0 !important; }
        .badge { font-size: .75em; }
        .table th { font-weight: 600; font-size: .8125rem; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
        .stats-card { border-left: 4px solid var(--hp-primary); }
        #impersonation-banner { background: #f59e0b; color: #000; padding: .5rem 1rem; font-weight: 600; text-align: center; position: sticky; top: 0; z-index: 9999; }
        /* AdminLTE treeview arrow fix for Bootstrap Icons */
        .nav-sidebar .nav-link p .right {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            transition: transform .3s;
            font-size: .7rem;
        }
        .nav-item.menu-open > .nav-link p .right {
            transform: translateY(-50%) rotate(-90deg);
        }
        .nav-treeview { padding-left: .5rem; }
        .nav-treeview .nav-link { padding: .4rem 1rem .4rem 1.5rem; font-size: .875rem; }
        .sidebar-dark-primary .nav-treeview > .nav-item > .nav-link.active { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar-dark-primary .nav-treeview > .nav-item > .nav-link { color: rgba(255,255,255,.7); }
        .sidebar-dark-primary .nav-treeview > .nav-item > .nav-link:hover { color: #fff; background: rgba(255,255,255,.05); }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">

    {{-- Impersonation Banner --}}
    @if(session('impersonation'))
        <div id="impersonation-banner">
            <i class="bi bi-person-fill-gear"></i>
            Você está visualizando o sistema como o cliente <strong>{{ session('impersonation.client_name') }}</strong>
            — <a href="{{ route('client.impersonation.stop') }}" class="text-dark fw-bold">Encerrar Impersonação</a>
        </div>
    @endif

    {{-- Navbar --}}
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="bi bi-list fs-5"></i></a>
            </li>
            <li class="nav-item d-none d-md-block">
                <a class="nav-link" href="{{ route('home') }}" target="_blank" title="Voltar ao site">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Ver Site
                </a>
            </li>
        </ul>
        <ul class="navbar-nav ms-auto">
            {{-- Busca rápida --}}
            <li class="nav-item">
                <a class="nav-link" data-widget="navbar-search" href="#" role="button" title="Buscar">
                    <i class="bi bi-search"></i>
                </a>
            </li>

            {{-- Alertas / Notificações --}}
            <li class="nav-item dropdown">
                <a class="nav-link" data-bs-toggle="dropdown" href="#" title="Notificações">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="badge bg-danger navbar-badge" id="notif-badge" style="display:none">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-lg" style="min-width:320px">
                    <span class="dropdown-header">Notificações</span>
                    <div id="notif-list">
                        <a href="#" class="dropdown-item text-muted text-center small py-3">Nenhuma notificação</a>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('admin.tickets.index') }}" class="dropdown-item dropdown-footer">Ver todos os tickets</a>
                </div>
            </li>

            {{-- Pedidos pendentes --}}
            <li class="nav-item">
                <a class="nav-link" href="{{ Route::has('admin.orders.index') ? route('admin.orders.index') : route('admin.invoices.index') }}" title="Pedidos/Faturas">
                    <i class="bi bi-cart3 fs-5"></i>
                    <span class="badge bg-warning text-dark navbar-badge" id="orders-badge" style="display:none">0</span>
                </a>
            </li>

            {{-- Tickets abertos --}}
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.tickets.index') }}" title="Tickets abertos">
                    <i class="bi bi-headset fs-5"></i>
                    <span class="badge bg-info navbar-badge" id="tickets-badge" style="display:none">0</span>
                </a>
            </li>

            {{-- Perfil do admin --}}
            <li class="nav-item dropdown">
                <a class="nav-link d-flex align-items-center" data-bs-toggle="dropdown" href="#">
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white fw-bold" style="width:32px;height:32px;font-size:.8rem">
                        {{ strtoupper(substr(auth('admin')->user()->name, 0, 2)) }}
                    </div>
                    <span class="ms-2 d-none d-md-inline fw-semibold">{{ auth('admin')->user()->name }}</span>
                    <i class="bi bi-chevron-down ms-1 d-none d-md-inline" style="font-size:.65rem"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow" style="min-width:220px">
                    <div class="px-3 py-2">
                        <div class="fw-semibold">{{ auth('admin')->user()->name }}</div>
                        <div class="text-muted small">{{ auth('admin')->user()->email }}</div>
                        <div class="text-muted small"><i class="bi bi-shield-check me-1"></i>{{ auth('admin')->user()->getRoleNames()->first() ?? 'Admin' }}</div>
                    </div>
                    <div class="dropdown-divider"></div>
                    @if(Route::has('admin.settings.index'))
                    <a href="{{ route('admin.settings.index') }}" class="dropdown-item"><i class="bi bi-gear me-2"></i>Configurações</a>
                    @endif
                    <a href="{{ route('admin.logs.activity') }}" class="dropdown-item"><i class="bi bi-journal-text me-2"></i>Logs de Atividade</a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sair</button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    {{-- Sidebar --}}
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('admin.dashboard') }}" class="brand-link px-3 py-2 d-flex align-items-center">
            <i class="bi bi-server text-white fs-4 me-2"></i>
            <span class="brand-text fw-bold text-white">{{ config('app.name') }}</span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image"><i class="bi bi-person-circle text-white fs-4 ms-2"></i></div>
                <div class="info">
                    <a href="#" class="d-block text-white">{{ auth('admin')->user()->name }}</a>
                    <span class="text-muted small">{{ auth('admin')->user()->getRoleNames()->first() ?? 'Admin' }}</span>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                    {{-- Dashboard --}}
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-speedometer2"></i><p>Dashboard</p>
                        </a>
                    </li>

                    {{-- ═══════ Clientes & Serviços ═══════ --}}
                    <li class="nav-item {{ request()->routeIs('admin.clients.*','admin.services.*','admin.orders.*','admin.domains.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.clients.*','admin.services.*','admin.orders.*','admin.domains.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-people"></i>
                            <p>Clientes & Serviços<i class="bi bi-chevron-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.clients.index') }}" class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Clientes</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.services.index') }}" class="nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Serviços</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ Route::has('admin.orders.index') ? route('admin.orders.index') : '#' }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Pedidos</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.domains.index') }}" class="nav-link {{ request()->routeIs('admin.domains.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Domínios</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- ═══════ Financeiro ═══════ --}}
                    <li class="nav-item {{ request()->routeIs('admin.invoices.*','admin.gateways.*','admin.coupons.*','admin.reports.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.invoices.*','admin.gateways.*','admin.coupons.*','admin.reports.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-wallet2"></i>
                            <p>Financeiro<i class="bi bi-chevron-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.invoices.index') }}" class="nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Faturas</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.gateways.index') }}" class="nav-link {{ request()->routeIs('admin.gateways.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Gateways</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.coupons.index') }}" class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Cupons</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.reports.revenue') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Relatórios</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- ═══════ Suporte ═══════ --}}
                    <li class="nav-item {{ request()->routeIs('admin.tickets.*','admin.kanban.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.tickets.*','admin.kanban.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-headset"></i>
                            <p>Suporte<i class="bi bi-chevron-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.tickets.index') }}" class="nav-link {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Tickets</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.kanban.board', 'tickets') }}" class="nav-link {{ request()->routeIs('admin.kanban.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Kanban</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- ═══════ Infraestrutura ═══════ --}}
                    <li class="nav-item {{ request()->routeIs('admin.servers.*','admin.products.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.servers.*','admin.products.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-server"></i>
                            <p>Infraestrutura<i class="bi bi-chevron-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.servers.index') }}" class="nav-link {{ request()->routeIs('admin.servers.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Servidores</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Produtos</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('status.index') }}" target="_blank" class="nav-link">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Status da Rede <i class="bi bi-box-arrow-up-right" style="font-size:.6rem"></i></p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- ═══════ Marketing ═══════ --}}
                    <li class="nav-item {{ request()->routeIs('admin.affiliates.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.affiliates.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-megaphone"></i>
                            <p>Marketing<i class="bi bi-chevron-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.affiliates.index') }}" class="nav-link {{ request()->routeIs('admin.affiliates.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Afiliados</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- ═══════ Conteúdo ═══════ --}}
                    <li class="nav-item {{ request()->routeIs('admin.cms.*','admin.notifications.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.cms.*','admin.notifications.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-layout-text-window-reverse"></i>
                            <p>Conteúdo<i class="bi bi-chevron-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.cms.pages') }}" class="nav-link {{ request()->routeIs('admin.cms.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>CMS / Páginas</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.notifications.email.templates') }}" class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>E-mail Templates</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- ═══════ Sistema & Configurações ═══════ --}}
                    <li class="nav-item {{ request()->routeIs('admin.settings.*','admin.themes.*','admin.admins.*','admin.permissions','admin.logs.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.settings.*','admin.themes.*','admin.admins.*','admin.permissions','admin.logs.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-gear"></i>
                            <p>Sistema<i class="bi bi-chevron-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('manage_settings')
                            <li class="nav-item">
                                <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.index') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Configurações</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.themes.index') }}" class="nav-link {{ request()->routeIs('admin.themes.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Temas</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.settings.cron') }}" class="nav-link {{ request()->routeIs('admin.settings.cron') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Cron Jobs</p>
                                </a>
                            </li>
                            @endcan
                            @can('manage_admins')
                            <li class="nav-item">
                                <a href="{{ route('admin.admins.index') }}" class="nav-link {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Administradores</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.permissions') }}" class="nav-link {{ request()->routeIs('admin.permissions') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Permissões</p>
                                </a>
                            </li>
                            @endcan
                            <li class="nav-item">
                                <a href="{{ route('admin.logs.activity') }}" class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon" style="font-size:.5rem"></i><p>Logs</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                </ul>
            </nav>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 fw-semibold">@yield('page-title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                            @yield('breadcrumb')
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                @endif

                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>{{ config('app.name') }}</strong> &copy; {{ date('Y') }} — Todos os direitos reservados.
        <div class="float-end d-none d-sm-block"><b>v1.0.0</b></div>
    </footer>
</div>

{{-- jQuery (required by AdminLTE 3) --}}
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
{{-- Bootstrap 5 + AdminLTE JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
{{-- Summernote --}}
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/lang/summernote-pt-BR.min.js"></script>
{{-- Alpine.js para reatividade leve --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

{{-- Treeview init + Navbar badges --}}
<script>
$(function(){
    // Force AdminLTE treeview init
    $('[data-widget="treeview"]').each(function(){ new AdminLte.Treeview(this, {animationSpeed:300, accordion:false}); });
    // Fallback: manual treeview toggle if AdminLTE widget fails
    $('.nav-sidebar .nav-link').on('click', function(e){
        const li = $(this).closest('.nav-item');
        if(li.find('.nav-treeview').length && $(this).attr('href') === '#'){
            e.preventDefault();
            li.toggleClass('menu-open');
            li.find('> .nav-treeview').slideToggle(200);
        }
    });
    // Init: hide closed treeviews
    $('.nav-sidebar .nav-item:not(.menu-open) > .nav-treeview').hide();
    $('.nav-sidebar .nav-item.menu-open > .nav-treeview').show();

    // Load navbar badges
    (async function(){
        try {
            const stats = await fetch('{{ route("admin.dashboard.stats") }}',{headers:{'Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}}).then(r=>r.json());
            if(stats.open_tickets > 0){ $('#tickets-badge').text(stats.open_tickets).show(); }
            if(stats.pending_invoices > 0){ $('#orders-badge').text(stats.pending_invoices).show(); }
            const total = (stats.open_tickets||0) + (stats.pending_invoices||0);
            if(total > 0){
                $('#notif-badge').text(total).show();
                let html = '';
                if(stats.open_tickets) html += `<a href="{{ route('admin.tickets.index') }}" class="dropdown-item"><i class="bi bi-headset me-2 text-info"></i>${stats.open_tickets} ticket(s) aberto(s)</a>`;
                if(stats.pending_invoices) html += `<a href="{{ route('admin.invoices.index') }}" class="dropdown-item"><i class="bi bi-receipt me-2 text-warning"></i>${stats.pending_invoices} fatura(s) pendente(s)</a>`;
                $('#notif-list').html(html);
            }
        } catch(e){}
    })();
});
</script>

@include('partials.hostpanel-ui-scripts')
{{-- Back to Top --}}
<button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})"
    class="btn btn-primary position-fixed shadow-lg"
    style="bottom:2rem;right:2rem;z-index:1050;display:none;width:42px;height:42px;border-radius:50%;padding:0">
    <i class="bi bi-arrow-up"></i>
</button>
<script>
window.addEventListener('scroll',()=>{document.getElementById('backToTop').style.display=window.scrollY>300?'block':'none'});
</script>
@stack('scripts')
</body>
</html>
