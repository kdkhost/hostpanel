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
    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="bi bi-list"></i></a></li>
        </ul>
        <ul class="navbar-nav ms-auto">
            {{-- Notificações rápidas --}}
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.tickets.index') }}" title="Tickets abertos">
                    <i class="bi bi-headset"></i>
                </a>
            </li>
            {{-- Perfil do admin --}}
            <li class="nav-item dropdown">
                <a class="nav-link" data-bs-toggle="dropdown" href="#">
                    <i class="bi bi-person-circle fs-5"></i>
                    <span class="ms-1 d-none d-md-inline">{{ auth('admin')->user()->name }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <span class="dropdown-item-text text-muted small">{{ auth('admin')->user()->email }}</span>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-1"></i>Sair</button>
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
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-speedometer2"></i><p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header">CLIENTES & SERVIÇOS</li>

                    <li class="nav-item">
                        <a href="{{ route('admin.clients.index') }}" class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-people"></i><p>Clientes</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.services.index') }}" class="nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-hdd-stack"></i><p>Serviços</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ Route::has('admin.orders.index') ? route('admin.orders.index') : '#' }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-cart3"></i><p>Pedidos</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.domains.index') }}" class="nav-link {{ request()->routeIs('admin.domains.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-globe"></i><p>Domínios</p>
                        </a>
                    </li>

                    <li class="nav-header">FINANCEIRO</li>

                    <li class="nav-item">
                        <a href="{{ route('admin.invoices.index') }}" class="nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-receipt"></i><p>Faturas</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.gateways.index') }}" class="nav-link {{ request()->routeIs('admin.gateways.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-credit-card"></i><p>Gateways</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.coupons.index') }}" class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-tag"></i><p>Cupons</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.reports.revenue') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-bar-chart"></i><p>Relatórios</p>
                        </a>
                    </li>

                    <li class="nav-header">SUPORTE</li>

                    <li class="nav-item">
                        <a href="{{ route('admin.tickets.index') }}" class="nav-link {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-headset"></i><p>Tickets</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.kanban.board', 'tickets') }}" class="nav-link {{ request()->routeIs('admin.kanban.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-kanban"></i><p>Kanban</p>
                        </a>
                    </li>

                    <li class="nav-header">INFRAESTRUTURA</li>

                    <li class="nav-item">
                        <a href="{{ route('admin.servers.index') }}" class="nav-link {{ request()->routeIs('admin.servers.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-server"></i><p>Servidores</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('status.index') }}" target="_blank" class="nav-link">
                            <i class="nav-icon bi bi-broadcast"></i><p>Status da Rede <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.65rem"></i></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-box-seam"></i><p>Produtos</p>
                        </a>
                    </li>

                    <li class="nav-header">SISTEMA</li>

                    <li class="nav-item">
                        <a href="{{ route('admin.cms.pages') }}" class="nav-link {{ request()->routeIs('admin.cms.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-file-earmark-text"></i><p>CMS</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.notifications.email.templates') }}" class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-envelope"></i><p>Notificações</p>
                        </a>
                    </li>
                    @can('manage_admins')
                    <li class="nav-item">
                        <a href="{{ route('admin.admins.index') }}" class="nav-link {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-shield-person"></i><p>Admins</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.permissions') }}" class="nav-link {{ request()->routeIs('admin.permissions') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-key"></i><p>Permissões</p>
                        </a>
                    </li>
                    @endcan
                    <li class="nav-item">
                        <a href="{{ route('admin.logs.activity') }}" class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-journal-text"></i><p>Logs</p>
                        </a>
                    </li>
                    @can('manage_settings')
                    <li class="nav-item">
                        <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.index') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-gear"></i><p>Configurações</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.settings.cron') }}" class="nav-link {{ request()->routeIs('admin.settings.cron') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-clock-history"></i><p>Cron Jobs</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.themes.index') }}" class="nav-link {{ request()->routeIs('admin.themes.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-palette"></i><p>Temas</p>
                        </a>
                    </li>
                    @endcan
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

{{-- Bootstrap 5 + AdminLTE JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
{{-- Alpine.js para reatividade leve --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<script>
    window.HostPanel = {
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        async fetch(url, options = {}) {
            return fetch(url, {
                headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json', ...options.headers },
                ...options,
            }).then(r => r.json());
        },
        toast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed bottom-0 end-0 m-3 shadow`;
            toast.style.zIndex = 9999;
            toast.innerHTML = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    };
</script>
@stack('scripts')
</body>
</html>
