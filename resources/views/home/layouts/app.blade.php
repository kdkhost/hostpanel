<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta-description', 'Hospedagem de sites profissional com suporte 24h.')">
    <title>@yield('title', config('app.name'))</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1a56db">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { primary: { 600:'#1a56db', 700:'#1e429f' } } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @stack('head')
    <style>
        html { scroll-behavior: smooth; }
        .card-hover { transition: transform .2s, box-shadow .2s; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,.12); }
        .gradient-text { background: linear-gradient(135deg, #1a56db, #7c3aed); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 flex flex-col min-h-screen">

    {{-- Navbar --}}
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                        <i class="bi bi-server text-white text-sm"></i>
                    </div>
                    <span class="font-bold text-gray-900">{{ config('app.name') }}</span>
                </a>

                {{-- Menu Central --}}
                <div class="hidden md:flex items-center gap-6 text-sm font-semibold text-gray-600">
                    <a href="{{ route('store') }}" class="hover:text-blue-600 transition @if(request()->routeIs('store')) text-blue-600 @endif">Loja</a>
                    <a href="{{ route('plans') }}" class="hover:text-blue-600 transition @if(request()->routeIs('plans')) text-blue-600 @endif">Planos</a>
                    <a href="{{ route('domain.search') }}" class="hover:text-blue-600 transition @if(request()->routeIs('domain.search')) text-blue-600 @endif">Domínios</a>
                    <a href="{{ route('kb') }}" class="hover:text-blue-600 transition @if(request()->routeIs('kb')) text-blue-600 @endif">Suporte</a>
                    <a href="{{ route('contact') }}" class="hover:text-blue-600 transition @if(request()->routeIs('contact')) text-blue-600 @endif">Contato</a>
                </div>

                {{-- Ações --}}
                <div class="flex items-center gap-3">
                    {{-- Carrinho --}}
                    <a href="{{ route('cart') }}" class="relative p-2 text-gray-500 hover:text-blue-600 transition">
                        <i class="bi bi-cart3 text-xl"></i>
                        <span id="cart-badge" class="hidden absolute -top-0.5 -right-0.5 w-4 h-4 bg-blue-600 text-white text-xs rounded-full flex items-center justify-center font-bold"></span>
                    </a>

                    @auth('client')
                        <a href="{{ route('client.dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-2 rounded-lg transition">
                            Painel
                        </a>
                    @else
                        <a href="{{ route('client.login') }}" class="text-gray-600 hover:text-gray-900 font-semibold text-sm">Entrar</a>
                        <a href="{{ route('client.register') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-4 py-2 rounded-lg transition">
                            Começar
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Conteúdo Principal --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-slate-900 text-slate-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                {{-- Brand --}}
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                            <i class="bi bi-server text-white text-sm"></i>
                        </div>
                        <span class="font-bold text-white">{{ config('app.name') }}</span>
                    </div>
                    <p class="text-sm">Hospedagem profissional com suporte 24/7.</p>
                </div>

                {{-- Produtos --}}
                <div>
                    <h4 class="font-semibold text-white mb-4">Produtos</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('plans') }}" class="hover:text-white transition">Hospedagem</a></li>
                        <li><a href="{{ route('domain.search') }}" class="hover:text-white transition">Domínios</a></li>
                        <li><a href="{{ route('store') }}" class="hover:text-white transition">Revenda</a></li>
                        <li><a href="{{ route('order.product', 'vps') }}" class="hover:text-white transition">VPS</a></li>
                    </ul>
                </div>

                {{-- Suporte --}}
                <div>
                    <h4 class="font-semibold text-white mb-4">Suporte</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('kb') }}" class="hover:text-white transition">Base de Conhecimento</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-white transition">Contato</a></li>
                        <li><a href="{{ route('status.index') }}" class="hover:text-white transition">Status da Rede</a></li>
                        <li><a href="{{ route('client.tickets.index') }}" class="hover:text-white transition">Abrir Ticket</a></li>
                    </ul>
                </div>

                {{-- Conta --}}
                <div>
                    <h4 class="font-semibold text-white mb-4">Conta</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('client.login') }}" class="hover:text-white transition">Entrar</a></li>
                        <li><a href="{{ route('client.register') }}" class="hover:text-white transition">Criar conta</a></li>
                        <li><a href="{{ route('client.dashboard') }}" class="hover:text-white transition">Área do Cliente</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-800 mt-8 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-sm">&copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ route('page', 'termos-de-uso') }}" class="hover:text-white transition">Termos</a>
                    <a href="{{ route('page', 'privacidade') }}" class="hover:text-white transition">Privacidade</a>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')

    {{-- Carrinho Badge Sync --}}
    <script>
        (function() {
            const cart = JSON.parse(localStorage.getItem('hostpanel_cart') || '[]');
            const badge = document.getElementById('cart-badge');
            if (badge && cart.length > 0) {
                badge.textContent = cart.length;
                badge.classList.remove('hidden');
            }
        })();
    </script>
</body>
</html>
