<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', config('app.name') . ' — Hospedagem profissional com suporte 24h.')">
    <title>@yield('title', config('app.name'))</title>
    <link rel="manifest" href="/manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="@themeAsset('css/theme.css')">
    <style>[x-cloak]{display:none!important}</style>
    @stack('head')
</head>
<body class="bg-gray-50 text-gray-900" style="font-family:'Inter',system-ui,sans-serif">

{{-- ============================================================
     NAVBAR
     ============================================================ --}}
<nav class="kdk-navbar" x-data="{ mobileOpen: false, solutionsOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16 gap-4">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="kdk-logo shrink-0">
                @if(\App\Models\Setting::get('company_logo'))
                    <img src="{{ \App\Models\Setting::get('company_logo') }}" alt="{{ config('app.name') }}" class="h-9 w-auto">
                @else
                    <span class="flex items-center gap-2">
                        <span class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shrink-0">
                            <i class="bi bi-server text-white text-sm"></i>
                        </span>
                        KDK<span>HOST</span>
                    </span>
                @endif
            </a>

            {{-- Desktop Nav --}}
            <div class="hidden lg:flex items-center gap-1 flex-1">

                {{-- Soluções dropdown --}}
                <div class="relative" @mouseenter="solutionsOpen=true" @mouseleave="solutionsOpen=false">
                    <button class="kdk-navbar nav-link flex items-center gap-1">
                        Soluções <i class="bi bi-chevron-down text-xs transition-transform" :class="solutionsOpen ? 'rotate-180':''"></i>
                    </button>
                    <div x-show="solutionsOpen" x-cloak
                         class="absolute top-full left-0 kdk-dropdown p-2 min-w-56">
                        <a href="{{ route('store') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-blue-50 text-gray-700 text-sm font-medium transition">
                            <i class="bi bi-grid text-blue-600 w-5 text-center"></i> Procurar todos
                        </a>
                        <a href="{{ route('plans') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-blue-50 text-gray-700 text-sm font-medium transition">
                            <i class="bi bi-hdd-network text-blue-600 w-5 text-center"></i> Hospedagem
                        </a>
                        <a href="{{ route('store') }}?tipo=revenda" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-blue-50 text-gray-700 text-sm font-medium transition">
                            <i class="bi bi-diagram-3 text-blue-600 w-5 text-center"></i> Revenda
                        </a>
                        <a href="{{ route('store') }}?tipo=servidor" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-blue-50 text-gray-700 text-sm font-medium transition">
                            <i class="bi bi-cpu text-blue-600 w-5 text-center"></i> Gerenciamento de Servidor
                        </a>
                        <hr class="my-1 border-gray-100">
                        <a href="{{ route('domain.search') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-blue-50 text-gray-700 text-sm font-medium transition">
                            <i class="bi bi-globe text-green-600 w-5 text-center"></i> Registrar Domínio
                        </a>
                    </div>
                </div>

                <a href="{{ route('announcements') }}" class="kdk-navbar nav-link">Anúncios</a>
                <a href="{{ route('kb') }}" class="kdk-navbar nav-link">Base de Conhecimento</a>
                <a href="{{ route('domain.search') }}" class="kdk-navbar nav-link">Domínios</a>
            </div>

            {{-- Auth + Cart --}}
            <div class="hidden lg:flex items-center gap-2">
                <a href="{{ route('cart') }}" class="relative p-2 text-gray-400 hover:text-white transition">
                    <i class="bi bi-cart3 text-lg"></i>
                    <span id="kdk-cart-badge" class="kdk-cart-badge hidden">0</span>
                </a>
                @auth('client')
                    <a href="{{ route('client.dashboard') }}" class="btn-kdk-accent text-sm py-2 px-4">
                        <i class="bi bi-person-circle"></i> Meu Painel
                    </a>
                @else
                    <a href="{{ route('client.login') }}" class="kdk-navbar nav-link">Entrar</a>
                    <a href="{{ route('client.register') }}" class="btn-kdk-primary text-sm py-2 px-4">Cadastrar</a>
                @endauth
            </div>

            {{-- Mobile button --}}
            <button class="lg:hidden text-white p-2" @click="mobileOpen = !mobileOpen">
                <i class="bi text-xl" :class="mobileOpen ? 'bi-x-lg' : 'bi-list'"></i>
            </button>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="mobileOpen" x-cloak class="lg:hidden pb-4 border-t border-white/10 mt-2 pt-3">
            <div class="flex flex-col gap-1">
                <a href="{{ route('store') }}" class="kdk-navbar nav-link rounded-lg">Loja</a>
                <a href="{{ route('plans') }}" class="kdk-navbar nav-link rounded-lg">Planos</a>
                <a href="{{ route('announcements') }}" class="kdk-navbar nav-link rounded-lg">Anúncios</a>
                <a href="{{ route('kb') }}" class="kdk-navbar nav-link rounded-lg">Suporte</a>
                <a href="{{ route('domain.search') }}" class="kdk-navbar nav-link rounded-lg">Domínios</a>
                <hr class="border-white/10 my-1">
                @auth('client')
                    <a href="{{ route('client.dashboard') }}" class="btn-kdk-accent text-center mt-1">Meu Painel</a>
                @else
                    <a href="{{ route('client.login') }}" class="kdk-navbar nav-link rounded-lg">Entrar</a>
                    <a href="{{ route('client.register') }}" class="btn-kdk-primary text-center mt-1">Cadastrar Agora</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

{{-- ============================================================
     CONTENT
     ============================================================ --}}
@yield('content')

{{-- ============================================================
     FOOTER
     ============================================================ --}}
<footer class="kdk-footer">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">

            <div class="col-span-2 md:col-span-1">
                <div class="kdk-logo mb-3 text-base">KDK<span>HOST</span></div>
                <p class="text-sm text-white/50 leading-relaxed">
                    Hospedagem profissional com infraestrutura de alta performance e suporte especializado.
                </p>
            </div>

            <div>
                <h6>Produtos</h6>
                <a href="{{ route('plans') }}">Hospedagem</a>
                <a href="{{ route('store') }}">Revenda</a>
                <a href="{{ route('store') }}">Servidor VPS</a>
                <a href="{{ route('domain.search') }}">Domínios</a>
            </div>

            <div>
                <h6>Suporte</h6>
                <a href="{{ route('kb') }}">Base de Conhecimento</a>
                <a href="{{ route('announcements') }}">Anúncios</a>
                <a href="{{ route('client.tickets.create') }}">Abrir Ticket</a>
                <a href="{{ route('home') }}">Status da Rede</a>
            </div>

            <div>
                <h6>Sua Conta</h6>
                @auth('client')
                    <a href="{{ route('client.dashboard') }}">Painel</a>
                    <a href="{{ route('client.services.index') }}">Meus Serviços</a>
                    <a href="{{ route('client.invoices.index') }}">Faturas</a>
                @else
                    <a href="{{ route('client.login') }}">Entrar</a>
                    <a href="{{ route('client.register') }}">Criar Conta</a>
                @endauth
                <a href="{{ route('cart') }}">Carrinho</a>
            </div>
        </div>

        <div class="kdk-footer-bottom">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</span>
            <div class="flex gap-4">
                <a href="{{ route('page', 'termos-de-uso') }}">Termos de Uso</a>
                <a href="{{ route('page', 'privacidade') }}">Privacidade</a>
            </div>
        </div>
    </div>
</footer>

<script>
(function() {
    const cart  = JSON.parse(localStorage.getItem('hostpanel_cart') || '[]');
    const badge = document.getElementById('kdk-cart-badge');
    if (badge && cart.length > 0) {
        badge.textContent = cart.length;
        badge.classList.remove('hidden');
    }
})();
</script>

@stack('scripts')
</body>
</html>
