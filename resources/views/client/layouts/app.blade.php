<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Painel') — {{ config('app.name') }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1a56db">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    {{-- Tailwind CSS 4 via CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary:  { DEFAULT: '#1a56db', 50:'#eff6ff', 100:'#dbeafe', 500:'#3b82f6', 600:'#1a56db', 700:'#1e429f', 800:'#1e3a8a' },
                        purple:   { DEFAULT: '#7c3aed', 600:'#7c3aed', 700:'#6d28d9' },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        [x-cloak] { display: none !important; }
        .sidebar-transition { transition: transform .3s ease; }
        .notification-dot { width:8px; height:8px; background:#ef4444; border-radius:50%; position:absolute; top:4px; right:4px; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-900">

{{-- Impersonation Banner --}}
@if(session('impersonation'))
<div class="bg-amber-400 text-black text-center py-2 text-sm font-semibold sticky top-0 z-50">
    <i class="bi bi-person-fill-gear me-1"></i>
    Você está sendo visualizado como
    <strong>{{ session('impersonation.admin_name') }}</strong> —
    <form method="POST" action="{{ route('client.impersonation.stop') }}" class="inline">
        @csrf
        <button type="submit" class="underline font-bold">Encerrar</button>
    </form>
</div>
@endif

<div class="flex h-screen overflow-hidden" x-data="clientLayout()">

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        class="fixed md:static inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-slate-900 to-slate-800 text-white flex flex-col sidebar-transition">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-700">
            <div class="w-9 h-9 rounded-lg bg-primary-600 flex items-center justify-center">
                <i class="bi bi-server text-white"></i>
            </div>
            <span class="font-bold text-lg">{{ config('app.name') }}</span>
        </div>

        {{-- User info --}}
        <div class="px-4 py-3 border-b border-slate-700">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-primary-600 flex items-center justify-center font-bold text-sm">
                    {{ strtoupper(substr(auth('client')->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <div class="font-medium text-sm truncate">{{ auth('client')->user()->name }}</div>
                    <div class="text-slate-400 text-xs truncate">{{ auth('client')->user()->email }}</div>
                </div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <a href="{{ route('client.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('client.dashboard') ? 'bg-primary-600 text-white' : 'text-slate-300 hover:bg-slate-700' }}">
                <i class="bi bi-speedometer2 w-5 text-center"></i> Dashboard
            </a>
            <a href="{{ route('client.services.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('client.services.*') ? 'bg-primary-600 text-white' : 'text-slate-300 hover:bg-slate-700' }}">
                <i class="bi bi-hdd-stack w-5 text-center"></i> Meus Serviços
            </a>
            <a href="{{ route('client.invoices.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('client.invoices.*') ? 'bg-primary-600 text-white' : 'text-slate-300 hover:bg-slate-700' }}">
                <i class="bi bi-receipt w-5 text-center"></i> Faturas
            </a>
            <a href="{{ route('client.tickets.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('client.tickets.*') ? 'bg-primary-600 text-white' : 'text-slate-300 hover:bg-slate-700' }}">
                <i class="bi bi-headset w-5 text-center"></i> Suporte
            </a>
            <a href="{{ route('client.orders.catalog') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('client.orders.*') ? 'bg-primary-600 text-white' : 'text-slate-300 hover:bg-slate-700' }}">
                <i class="bi bi-cart3 w-5 text-center"></i> Contratar
            </a>

            <div class="pt-3 pb-1 px-3">
                <span class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Conta</span>
            </div>

            <a href="{{ route('client.profile.show') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('client.profile.*') ? 'bg-primary-600 text-white' : 'text-slate-300 hover:bg-slate-700' }}">
                <i class="bi bi-person w-5 text-center"></i> Meu Perfil
            </a>
            <a href="{{ route('client.profile.notifications') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('client.profile.notifications') ? 'bg-primary-600 text-white' : 'text-slate-300 hover:bg-slate-700' }}">
                <i class="bi bi-bell w-5 text-center"></i> Notificações
                @if($unreadNotifications ?? 0)
                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5">{{ $unreadNotifications }}</span>
                @endif
            </a>
        </nav>

        {{-- Saldo e Logout --}}
        <div class="p-4 border-t border-slate-700">
            @php $client = auth('client')->user(); @endphp
            @if($client->credit_balance > 0)
            <div class="bg-slate-700 rounded-lg p-3 mb-3">
                <div class="text-xs text-slate-400 mb-1">Saldo de Crédito</div>
                <div class="font-bold text-green-400">R$ {{ number_format($client->credit_balance, 2, ',', '.') }}</div>
            </div>
            @endif
            <form method="POST" action="{{ route('client.logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-red-400 hover:text-red-300 text-sm rounded-lg hover:bg-slate-700">
                    <i class="bi bi-box-arrow-right"></i> Sair da conta
                </button>
            </form>
        </div>
    </aside>

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" @click="sidebarOpen=false"
        class="fixed inset-0 bg-black/50 z-30 md:hidden" x-cloak></div>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        {{-- Top Bar --}}
        <header class="bg-white border-b border-gray-200 sticky top-0 z-20 flex items-center px-4 py-3 gap-4">
            <button @click="sidebarOpen=!sidebarOpen" class="md:hidden text-gray-500 hover:text-gray-700">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="flex-1">
                <h1 class="text-lg font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('client.profile.notifications') }}" class="relative text-gray-500 hover:text-primary-600">
                    <i class="bi bi-bell fs-5"></i>
                    @if($unreadNotifications ?? 0)
                        <span class="notification-dot"></span>
                    @endif
                </a>
                <a href="{{ route('client.orders.catalog') }}" class="hidden sm:flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                    <i class="bi bi-plus-lg"></i> Contratar
                </a>
            </div>
        </header>

        {{-- Page Content --}}
        <main class="flex-1 overflow-y-auto">
            <div class="max-w-7xl mx-auto px-4 py-6">
                @if(session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 mb-4 flex items-center gap-2">
                        <i class="bi bi-check-circle-fill text-green-500"></i> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 mb-4 flex items-center gap-2">
                        <i class="bi bi-exclamation-circle-fill text-red-500"></i> {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <footer class="bg-white border-t border-gray-100 py-3 px-4 text-center text-gray-400 text-xs">
            {{ config('app.name') }} &copy; {{ date('Y') }} — Todos os direitos reservados.
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<script>
function clientLayout() {
    return {
        sidebarOpen: false,
        init() {
            // Register Service Worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/service-worker.js').catch(() => {});
            }
        }
    }
}
window.HostPanel = {
    csrfToken: document.querySelector('meta[name=csrf-token]').content,
    async fetch(url, opts = {}) {
        return fetch(url, { headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json', ...opts.headers }, ...opts }).then(r => r.json());
    },
    toast(msg, type = 'success') {
        const el = document.createElement('div');
        const colors = { success:'bg-green-600', danger:'bg-red-600', info:'bg-blue-600', warning:'bg-amber-500' };
        el.className = `fixed bottom-4 right-4 z-50 ${colors[type]||'bg-gray-800'} text-white px-4 py-3 rounded-lg shadow-lg text-sm font-medium max-w-sm`;
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }
};
</script>
@stack('scripts')
</body>
</html>
