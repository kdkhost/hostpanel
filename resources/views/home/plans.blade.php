<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { primary: { 600:'#1a56db', 700:'#1e429f' } } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        html { scroll-behavior: smooth; }
        .card-hover { transition: transform .2s, box-shadow .2s; }
        .card-hover:hover { transform: translateY(-6px); box-shadow: 0 24px 48px rgba(0,0,0,.12); }
    </style>
</head>
<body class="bg-gray-50 text-gray-900" x-data="plansPage()">

    {{-- Navbar --}}
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center"><i class="bi bi-server text-white text-sm"></i></div>
                    <span class="font-bold text-gray-900">{{ config('app.name') }}</span>
                </a>
            </div>
            <div class="hidden md:flex items-center gap-5 text-sm font-semibold text-gray-600">
                <a href="{{ route('store') }}" class="hover:text-gray-900">Loja</a>
                <a href="{{ route('domain.search') }}" class="hover:text-gray-900">Domínios</a>
                <a href="{{ route('kb') }}" class="hover:text-gray-900">Suporte</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('client.login') }}" class="text-gray-600 hover:text-gray-900 font-semibold text-sm">Entrar</a>
                <a href="{{ route('client.register') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-4 py-2 rounded-lg">Começar</a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-slate-900 to-blue-900 text-white py-16">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4">Planos & Preços</h1>
            <p class="text-blue-200 text-lg mb-8">Escolha o plano perfeito para o seu projeto. Sem taxas ocultas.</p>

            {{-- Filtro de Ciclo --}}
            <div class="inline-flex bg-white/10 rounded-2xl p-1 gap-1">
                @foreach(['monthly'=>'Mensal','annually'=>'Anual (-20%)'] as $key => $lbl)
                <button @click="cycle='{{ $key }}'"
                        :class="cycle==='{{ $key }}' ? 'bg-white text-blue-700' : 'text-white hover:bg-white/10'"
                        class="font-semibold text-sm px-5 py-2 rounded-xl transition">{{ $lbl }}</button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Planos --}}
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @forelse($productGroups ?? [] as $group)
            <div class="mb-16">
                <h2 class="text-3xl font-extrabold text-gray-900 text-center mb-2">{{ $group->name }}</h2>
                @if($group->description)
                <p class="text-gray-500 text-center mb-8 text-lg">{{ $group->description }}</p>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($group->activeProducts as $product)
                    @php
                        $monthlyPrice  = $product->prices['monthly']  ?? null;
                        $annualPrice   = $product->prices['annually']  ?? null;
                        $displayPrice  = null;
                    @endphp
                    <div class="bg-white rounded-2xl border {{ $product->featured ? 'border-blue-400 shadow-2xl ring-2 ring-blue-200' : 'border-gray-100 shadow-sm' }} overflow-hidden card-hover flex flex-col">
                        @if($product->featured)
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white text-center py-2 text-xs font-bold tracking-widest uppercase">⭐ Mais Popular</div>
                        @endif
                        <div class="p-6 flex-1">
                            <h3 class="font-bold text-xl text-gray-900 mb-1">{{ $product->name }}</h3>
                            @if($product->tagline) <p class="text-gray-500 text-sm mb-4">{{ $product->tagline }}</p> @endif

                            <div class="mb-6" x-show="cycle === 'monthly'">
                                @if($monthlyPrice !== null)
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-3xl font-extrabold text-gray-900">R$ {{ number_format($monthlyPrice, 2, ',', '.') }}</span>
                                        <span class="text-gray-400 text-sm">/mês</span>
                                    </div>
                                @else <span class="text-gray-400">Consulte</span> @endif
                            </div>
                            <div class="mb-6" x-show="cycle === 'annually'" x-cloak>
                                @if($annualPrice !== null)
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-3xl font-extrabold text-gray-900">R$ {{ number_format($annualPrice / 12, 2, ',', '.') }}</span>
                                        <span class="text-gray-400 text-sm">/mês</span>
                                    </div>
                                    <div class="text-xs text-green-600 font-semibold mt-0.5">
                                        R$ {{ number_format($annualPrice, 2, ',', '.') }}/ano
                                        @if($monthlyPrice) &bull; Economize R$ {{ number_format(($monthlyPrice * 12) - $annualPrice, 2, ',', '.') }} @endif
                                    </div>
                                @elseif($monthlyPrice !== null)
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-3xl font-extrabold text-gray-900">R$ {{ number_format($monthlyPrice, 2, ',', '.') }}</span>
                                        <span class="text-gray-400 text-sm">/mês</span>
                                    </div>
                                @else <span class="text-gray-400">Consulte</span> @endif
                            </div>

                            @if($product->features)
                            <ul class="space-y-2">
                                @foreach($product->features as $f)
                                <li class="flex items-start gap-2 text-sm text-gray-700">
                                    <i class="bi bi-check-circle-fill text-green-500 flex-shrink-0 mt-0.5"></i> {{ $f }}
                                </li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                        <div class="p-6 pt-0">
                            <a href="{{ route('client.orders.catalog') }}"
                               class="block w-full text-center font-bold py-3 rounded-xl transition
                                      {{ $product->featured ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:opacity-90'
                                                            : 'border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white' }}">
                                Contratar
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @empty
            <div class="text-center py-20 text-gray-400">
                <div class="text-5xl mb-4">📦</div>
                <p class="text-lg">Planos em breve.</p>
            </div>
            @endforelse
        </div>
    </section>

    {{-- FAQ --}}
    @if(isset($faqs) && $faqs->count())
    <section class="py-16 bg-white">
        <div class="max-w-3xl mx-auto px-4">
            <h2 class="text-3xl font-extrabold text-gray-900 text-center mb-10">Perguntas Frequentes</h2>
            <div class="space-y-3" x-data="{open: null}">
                @foreach($faqs as $faq)
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <button @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}"
                            class="w-full flex items-center justify-between px-5 py-4 text-left font-semibold text-gray-900 hover:bg-gray-50">
                        {{ $faq->question }}
                        <i class="bi flex-shrink-0 text-gray-400" :class="open === {{ $loop->index }} ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>
                    <div x-show="open === {{ $loop->index }}" x-collapse class="px-5 pb-4 text-gray-600 text-sm leading-relaxed border-t border-gray-100">
                        {{ $faq->answer }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- CTA --}}
    <section class="py-16 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-center">
        <h2 class="text-3xl font-extrabold mb-4">Pronto para começar?</h2>
        <p class="text-blue-100 mb-8 text-lg">Ative seu serviço em menos de 5 minutos.</p>
        <a href="{{ route('client.register') }}" class="bg-white text-blue-700 font-bold text-lg px-10 py-4 rounded-2xl hover:bg-blue-50 transition inline-block">
            Criar Conta Grátis
        </a>
    </section>

    <footer class="bg-slate-900 text-slate-400 py-8 text-center text-sm">
        &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
    function plansPage() { return { cycle: 'monthly' } }
    </script>
</body>
</html>
