<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Busca de Domínios — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-900">

    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-gray-900">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center"><i class="bi bi-server text-white text-sm"></i></div>
                {{ config('app.name') }}
            </a>
            <a href="{{ route('client.login') }}" class="text-gray-600 hover:text-gray-900 text-sm font-semibold">Área do Cliente</a>
        </div>
    </nav>

    <section class="bg-gradient-to-br from-slate-900 to-blue-900 text-white py-16">
        <div class="max-w-3xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-extrabold mb-3">Registre seu Domínio</h1>
            <p class="text-blue-200 mb-8">Verifique a disponibilidade e comece seu projeto online hoje.</p>
            <form action="{{ route('domain.search') }}" method="GET" class="flex gap-2 max-w-2xl mx-auto">
                <div class="flex-1 flex bg-white rounded-xl overflow-hidden shadow-lg">
                    <span class="px-4 flex items-center text-gray-400"><i class="bi bi-globe2"></i></span>
                    <input type="text" name="dominio" value="{{ $domain ?? '' }}"
                           class="flex-1 py-4 text-gray-900 text-base focus:outline-none"
                           placeholder="seudominio.com.br">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-4 rounded-xl text-base">
                    Verificar
                </button>
            </form>
        </div>
    </section>

    <section class="py-12 max-w-4xl mx-auto px-4" x-data="domainSearch()">
        @if($domain)
        {{-- Resultado --}}
        <div class="mb-10">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Resultado para "<span class="text-blue-600">{{ $domain }}</span>"</h2>
            <div x-show="checking" class="text-center py-8">
                <div class="inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="mt-3 text-gray-500">Verificando disponibilidade...</p>
            </div>
            <div x-show="!checking" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                         :class="available ? 'bg-green-50' : 'bg-red-50'">
                        <i class="bi text-2xl" :class="available ? 'bi-check-circle-fill text-green-500' : 'bi-x-circle-fill text-red-500'"></i>
                    </div>
                    <div>
                        <div class="font-bold text-gray-900 text-lg">{{ $domain }}</div>
                        <div class="text-sm" :class="available ? 'text-green-600' : 'text-red-600'"
                             x-text="available ? 'Disponível para registro!' : 'Indisponível — já registrado.'"></div>
                    </div>
                </div>
                <div class="flex items-center gap-4" x-show="available">
                    <div class="text-center">
                        <div class="text-xs text-gray-400">Registro</div>
                        <div class="font-bold text-gray-900" x-text="price ? 'R$ ' + price : '—'"></div>
                    </div>
                    <a href="{{ route('client.orders.catalog') }}?domain={{ urlencode($domain ?? '') }}"
                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl text-sm">
                        Registrar Domínio
                    </a>
                </div>
            </div>
        </div>

        {{-- Sugestões de TLD --}}
        <div>
            <h3 class="text-lg font-bold text-gray-900 mb-4">Extensões disponíveis</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                @foreach($tlds->take(12) as $tld)
                @php $base = strtok($domain ?? '', '.'); @endphp
                <div class="bg-white rounded-xl border border-gray-100 p-4 text-center hover:border-blue-300 hover:shadow-sm transition">
                    <div class="font-bold text-gray-700 text-sm mb-1">{{ $base }}<span class="text-blue-600">{{ $tld->tld }}</span></div>
                    <div class="text-xs text-gray-400 mb-2">R$ {{ number_format($tld->register_price, 2, ',', '.') }}/ano</div>
                    <a href="{{ route('client.orders.catalog') }}?domain={{ urlencode($base . $tld->tld) }}"
                       class="text-blue-600 hover:text-blue-700 text-xs font-semibold">Registrar →</a>
                </div>
                @endforeach
            </div>
        </div>
        @else
        {{-- TLDs disponíveis --}}
        <div>
            <h2 class="text-2xl font-extrabold text-gray-900 text-center mb-8">Extensões Disponíveis</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach($tlds as $tld)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center hover:border-blue-300 hover:shadow-md transition">
                    <div class="font-bold text-blue-700 text-lg mb-1">{{ $tld->tld }}</div>
                    <div class="text-xs text-gray-500 font-semibold">R$ {{ number_format($tld->register_price, 2, ',', '.') }}<span class="text-gray-400 font-normal">/ano</span></div>
                    @if($tld->renew_price && $tld->renew_price !== $tld->register_price)
                    <div class="text-xs text-gray-400 mt-0.5">Renovação R$ {{ number_format($tld->renew_price, 2, ',', '.') }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </section>

    <footer class="bg-slate-900 text-slate-400 py-8 text-center text-sm mt-4">
        &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
    </footer>

    <script>
    function domainSearch() {
        return {
            checking: false, available: false, price: null,
            async init() {
                const domain = '{{ addslashes($domain ?? '') }}';
                if (!domain) return;
                this.checking = true;
                try {
                    const r = await fetch(`/api/v1/domain/check?domain=${encodeURIComponent(domain)}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const d = await r.json();
                    this.available = d.available ?? false;
                    this.price = d.price ?? null;
                } catch {}
                this.checking = false;
            }
        }
    }
    </script>
</body>
</html>
