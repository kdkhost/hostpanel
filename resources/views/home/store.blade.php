<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loja — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none}</style>
</head>
<body class="bg-gray-50 text-gray-900">

{{-- Navbar --}}
<nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16 gap-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-extrabold text-gray-900 text-lg shrink-0">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center"><i class="bi bi-server text-white text-sm"></i></div>
            {{ config('app.name') }}
        </a>
        <div class="hidden md:flex items-center gap-6 text-sm font-semibold text-gray-600">
            <a href="{{ route('store') }}" class="text-blue-600">Loja</a>
            <a href="{{ route('plans') }}" class="hover:text-gray-900">Planos</a>
            <a href="{{ route('domain.search') }}" class="hover:text-gray-900">Domínios</a>
            <a href="{{ route('kb') }}" class="hover:text-gray-900">Suporte</a>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('cart') }}" class="relative p-2 text-gray-500 hover:text-blue-600">
                <i class="bi bi-cart3 text-xl"></i>
                <span id="cart-badge" class="hidden absolute -top-0.5 -right-0.5 w-4 h-4 bg-blue-600 text-white text-xs rounded-full flex items-center justify-center font-bold"></span>
            </a>
            @auth('client')
            <a href="{{ route('client.dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-2 rounded-lg">Painel</a>
            @else
            <a href="{{ route('client.login') }}" class="text-gray-600 hover:text-gray-900 text-sm font-semibold">Entrar</a>
            <a href="{{ route('client.register') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-2 rounded-lg">Cadastrar</a>
            @endauth
        </div>
    </div>
</nav>

{{-- Hero --}}
<section class="bg-gradient-to-br from-slate-900 to-blue-900 text-white py-14">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <h1 class="text-4xl font-extrabold mb-3">Escolha seu Plano</h1>
        <p class="text-blue-200 text-lg">Soluções de hospedagem para todos os tamanhos de projetos.</p>
    </div>
</section>

{{-- Categorias + Produtos --}}
<section class="py-10 max-w-7xl mx-auto px-4" x-data="{ activeGroup: '{{ $activeGroup ?? $groups->first()?->slug ?? '' }}' }">

    {{-- Tabs de categoria --}}
    @if($groups->count() > 1)
    <div class="flex flex-wrap gap-2 mb-8 border-b border-gray-200 pb-4">
        @foreach($groups as $group)
        <button @click="activeGroup = '{{ $group->slug }}'"
                :class="activeGroup === '{{ $group->slug }}'
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300'"
                class="flex items-center gap-2 px-5 py-2.5 rounded-full border text-sm font-semibold transition">
            <i class="bi bi-{{ $group->icon ?? 'box' }}"></i>
            {{ $group->name }}
            <span class="text-xs opacity-70">({{ $group->products->count() }})</span>
        </button>
        @endforeach
    </div>
    @endif

    {{-- Produtos por grupo --}}
    @foreach($groups as $group)
    <div x-show="activeGroup === '{{ $group->slug }}'" x-cloak>
        @if($group->description)
        <p class="text-gray-500 mb-6 text-sm max-w-2xl">{{ $group->description }}</p>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @forelse($group->products as $product)
            @php
                $lowestPrice = $product->pricing->first();
                $labels = ['monthly'=>'mês','quarterly'=>'trimestre','semiannually'=>'semestre','annually'=>'ano','biennially'=>'2 anos','free'=>''];
            @endphp
            <div class="bg-white rounded-2xl border {{ $product->featured ? 'border-blue-400 ring-2 ring-blue-200' : 'border-gray-100' }} shadow-sm hover:shadow-md transition flex flex-col relative overflow-hidden">
                @if($product->featured)
                <div class="absolute top-3 right-3">
                    <span class="bg-blue-600 text-white text-xs font-bold px-2.5 py-1 rounded-full">⭐ Popular</span>
                </div>
                @endif

                <div class="p-6 flex-1">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center mb-4">
                        <i class="bi bi-{{ $product->icon ?? 'server' }} text-blue-600 text-lg"></i>
                    </div>
                    <h3 class="text-lg font-extrabold text-gray-900 mb-1">{{ $product->name }}</h3>
                    @if($product->tagline)
                    <p class="text-gray-400 text-xs mb-3">{{ $product->tagline }}</p>
                    @endif

                    @if($lowestPrice)
                    <div class="flex items-baseline gap-1 mb-4">
                        <span class="text-2xl font-extrabold text-blue-700">R$ {{ number_format($lowestPrice->price, 2, ',', '.') }}</span>
                        <span class="text-xs text-gray-400">/ {{ $labels[$lowestPrice->billing_cycle] ?? $lowestPrice->billing_cycle }}</span>
                    </div>
                    @else
                    <div class="text-xl font-extrabold text-gray-400 mb-4">Consulte</div>
                    @endif

                    @if($product->features && count($product->features))
                    <ul class="space-y-1.5 mb-4">
                        @foreach(array_slice(is_array($product->features) ? $product->features : json_decode($product->features ?? '[]', true), 0, 5) as $feat)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <i class="bi bi-check-circle-fill text-green-500 text-xs shrink-0"></i>
                            {{ is_array($feat) ? ($feat['name'] ?? $feat['value'] ?? '') : $feat }}
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>

                <div class="px-6 pb-6">
                    <a href="{{ route('order.product', $product->slug) }}"
                       class="block w-full text-center {{ $product->featured ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-blue-50 hover:bg-blue-100 text-blue-700' }} font-bold py-3 rounded-xl text-sm transition">
                        Contratar agora →
                    </a>
                </div>
            </div>
            @empty
            <div class="col-span-4 text-center text-gray-400 py-12">Nenhum produto disponível nesta categoria.</div>
            @endforelse
        </div>
    </div>
    @endforeach
</section>

{{-- Banner domínios --}}
<section class="bg-slate-900 text-white py-14">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl font-extrabold mb-3">Registre seu Domínio</h2>
        <p class="text-slate-400 mb-6 text-sm">Verifique a disponibilidade do nome do seu site agora mesmo.</p>
        <form action="{{ route('domain.search') }}" method="GET" class="flex gap-2 max-w-xl mx-auto">
            <input type="text" name="dominio" placeholder=".com.br, .com, .net..."
                   class="flex-1 bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-slate-400 text-sm focus:outline-none focus:border-blue-400">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl text-sm transition">Buscar</button>
        </form>
    </div>
</section>

<footer class="bg-slate-950 text-slate-500 py-8 text-center text-sm">
    &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
    <span class="mx-2">·</span>
    <a href="{{ route('page', 'termos-de-uso') }}" class="hover:text-slate-300">Termos</a>
    <span class="mx-2">·</span>
    <a href="{{ route('page', 'privacidade') }}" class="hover:text-slate-300">Privacidade</a>
</footer>

<script>
// Sincronizar badge do carrinho com localStorage
const cart = JSON.parse(localStorage.getItem('hostpanel_cart') || '[]');
const badge = document.getElementById('cart-badge');
if (badge && cart.length > 0) {
    badge.textContent = cart.length;
    badge.classList.remove('hidden');
}
</script>
</body>
</html>
