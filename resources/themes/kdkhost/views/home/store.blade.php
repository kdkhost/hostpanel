@extends('layouts.guest')
@section('title', 'Loja — ' . config('app.name'))

@section('content')

{{-- Hero --}}
<section class="kdk-hero py-14">
    <div class="kdk-hero-content max-w-3xl mx-auto px-4 text-center">
        <h1 class="text-4xl font-extrabold text-white mb-3">Escolha seu Plano</h1>
        <p class="text-blue-200 text-lg">Soluções de hospedagem para todos os tamanhos de projetos.</p>
    </div>
</section>

{{-- Produtos --}}
<section class="kdk-section" x-data="{ activeGroup: '{{ $activeGroup ?? $groups->first()?->slug ?? '' }}' }">
    <div class="max-w-7xl mx-auto px-4">

        {{-- Tabs --}}
        @if($groups->count() > 1)
        <div class="flex flex-wrap gap-2 mb-8">
            @foreach($groups as $group)
            <button @click="activeGroup = '{{ $group->slug }}'"
                    :class="activeGroup === '{{ $group->slug }}' ? 'active' : ''"
                    class="kdk-tab">
                <i class="bi bi-{{ $group->icon ?? 'box' }}"></i>
                {{ $group->name }}
                <span class="text-xs opacity-60 ml-1">({{ $group->products->count() }})</span>
            </button>
            @endforeach
        </div>
        @endif

        {{-- Products per group --}}
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
                <div class="kdk-card {{ $product->featured ? 'featured' : '' }} relative">
                    @if($product->featured)
                    <div class="absolute top-3 right-3">
                        <span class="bg-blue-600 text-white text-xs font-bold px-2.5 py-1 rounded-full">⭐ Popular</span>
                    </div>
                    @endif

                    <div class="p-6 flex-1 flex flex-col">
                        <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center mb-4">
                            <i class="bi bi-{{ $product->icon ?? 'server' }} text-blue-600 text-lg"></i>
                        </div>

                        <h3 class="text-lg font-extrabold text-gray-900 mb-1">{{ $product->name }}</h3>
                        @if($product->tagline)
                        <p class="text-gray-400 text-xs mb-3">{{ $product->tagline }}</p>
                        @endif

                        @if($lowestPrice)
                        <div class="flex items-baseline gap-1 mb-4">
                            <span class="price">R$ {{ number_format($lowestPrice->price, 2, ',', '.') }}</span>
                            <span class="price-period">/ {{ $labels[$lowestPrice->billing_cycle] ?? $lowestPrice->billing_cycle }}</span>
                        </div>
                        @else
                        <div class="text-xl font-extrabold text-gray-400 mb-4">Consulte</div>
                        @endif

                        @if($product->features && count($product->features))
                        <ul class="kdk-feature-list mb-4 flex-1">
                            @foreach(array_slice(is_array($product->features) ? $product->features : json_decode($product->features ?? '[]', true), 0, 5) as $feat)
                            <li>
                                <i class="bi bi-check-circle-fill check"></i>
                                {{ is_array($feat) ? ($feat['name'] ?? $feat['value'] ?? '') : $feat }}
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>

                    <div class="px-6 pb-6">
                        <a href="{{ route('order.product', $product->slug) }}"
                           class="{{ $product->featured ? 'btn-kdk-primary' : 'btn-kdk-accent' }} w-full justify-center text-sm">
                            Contratar agora →
                        </a>
                    </div>
                </div>
                @empty
                <div class="col-span-4 text-center text-gray-400 py-16">
                    <i class="bi bi-inbox text-5xl opacity-30 block mb-3"></i>
                    Nenhum produto disponível nesta categoria.
                </div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</section>

{{-- Domain banner --}}
<section class="py-14 bg-slate-900 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl font-extrabold mb-3">Registre seu Domínio</h2>
        <p class="text-slate-400 mb-6 text-sm">Verifique a disponibilidade do nome do seu site agora mesmo.</p>
        <form action="{{ route('domain.search') }}" method="GET" class="flex gap-2 max-w-xl mx-auto">
            <input type="text" name="dominio" placeholder=".com.br, .com, .net..."
                   class="flex-1 bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-slate-400 text-sm focus:outline-none focus:border-blue-400">
            <button type="submit" class="btn-kdk-primary shrink-0">Buscar</button>
        </form>
    </div>
</section>

@endsection
