@extends('home.layouts.app')

@section('title', ($product->name ?? 'Plano') . ' — ' . config('app.name'))
@section('meta-description', $product->description ?? $product->tagline)

@push('head')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush

@section('content')
<div x-data="{ cycle: 'monthly' }">
    {{-- Hero --}}
    <section class="bg-gradient-to-br from-slate-900 to-blue-900 text-white py-16">
        <div class="max-w-4xl mx-auto px-4">
            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 text-blue-300 text-sm mb-4">
                <a href="{{ route('home') }}" class="hover:text-white">Início</a>
                <span>/</span>
                <a href="{{ route('plans') }}" class="hover:text-white">Planos</a>
                @if($product->group) <span>/</span><span>{{ $product->group->name }}</span> @endif
                <span>/</span>
                <span class="text-white">{{ $product->name }}</span>
            </div>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-8">
                <div>
                    @if($product->featured)
                    <div class="inline-flex items-center gap-1 bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 rounded-full mb-3">⭐ Mais Popular</div>
                    @endif
                    <h1 class="text-4xl font-extrabold mb-2">{{ $product->name }}</h1>
                    @if($product->tagline)
                    <p class="text-blue-200 text-lg">{{ $product->tagline }}</p>
                    @endif
                </div>

                {{-- Preço --}}
                <div class="bg-white/10 rounded-2xl p-6 min-w-[220px] text-center">
                    <div class="mb-3">
                        <div class="inline-flex bg-white/10 rounded-xl p-0.5 gap-0.5">
                            <button @click="cycle='monthly'" :class="cycle==='monthly'?'bg-white text-blue-700':'text-white hover:bg-white/10'"
                                    class="font-semibold text-xs px-3 py-1.5 rounded-lg transition">Mensal</button>
                            <button @click="cycle='annually'" :class="cycle==='annually'?'bg-white text-blue-700':'text-white hover:bg-white/10'"
                                    class="font-semibold text-xs px-3 py-1.5 rounded-lg transition">Anual</button>
                        </div>
                    </div>
                    @php
                        $prices = $product->pricing->keyBy('billing_cycle');
                        $monthly  = $prices->get('monthly');
                        $annually = $prices->get('annually');
                    @endphp
                    <div x-show="cycle==='monthly'">
                        @if($monthly)
                        <div class="text-3xl font-extrabold">R$ {{ number_format($monthly->price, 2, ',', '.') }}</div>
                        <div class="text-blue-200 text-sm">/mês</div>
                        @else <div class="text-lg text-blue-200">Consulte-nos</div> @endif
                    </div>
                    <div x-show="cycle==='annually'" x-cloak>
                        @if($annually)
                        <div class="text-3xl font-extrabold">R$ {{ number_format($annually->price / 12, 2, ',', '.') }}</div>
                        <div class="text-blue-200 text-sm">/mês cobrado anualmente</div>
                        @if($monthly) <div class="text-green-300 text-xs mt-1 font-semibold">Economize R$ {{ number_format(($monthly->price * 12) - $annually->price, 2, ',', '.') }}/ano</div> @endif
                        @elseif($monthly)
                        <div class="text-3xl font-extrabold">R$ {{ number_format($monthly->price, 2, ',', '.') }}</div>
                        <div class="text-blue-200 text-sm">/mês</div>
                        @else <div class="text-lg text-blue-200">Consulte-nos</div> @endif
                    </div>
                    <a href="{{ route('client.orders.catalog') }}?product={{ $product->id }}"
                       class="block mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-sm">
                        Contratar Agora
                    </a>
                    <p class="text-blue-300 text-xs mt-2">Ativação imediata após pagamento</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Descrição + Recursos --}}
    <section class="py-12 max-w-4xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Descrição --}}
            @if($product->description)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 text-xl mb-4">Sobre este Plano</h2>
                <div class="text-gray-600 text-sm leading-relaxed prose prose-sm max-w-none">
                    {!! nl2br(e($product->description)) !!}
                </div>
            </div>
            @endif

            {{-- Recursos --}}
            @if($product->features && count($product->features) > 0)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 text-xl mb-4">O que está incluído</h2>
                <ul class="space-y-2">
                    @foreach($product->features as $feature)
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <i class="bi bi-check-circle-fill text-green-500 flex-shrink-0 mt-0.5"></i>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        {{-- Todos os Ciclos de Cobrança --}}
        @if($product->pricing->isNotEmpty())
        <div class="mt-8 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h2 class="font-bold text-gray-900 text-xl mb-4">Ciclos de Cobrança Disponíveis</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left py-2 text-gray-500 font-semibold text-xs uppercase">Ciclo</th>
                            <th class="text-right py-2 text-gray-500 font-semibold text-xs uppercase">Preço</th>
                            <th class="text-right py-2 text-gray-500 font-semibold text-xs uppercase">Equiv./Mês</th>
                            <th class="text-right py-2 text-gray-500 font-semibold text-xs uppercase"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($product->pricing as $price)
                        @php
                            $months = ['monthly'=>1,'quarterly'=>3,'semiannually'=>6,'annually'=>12,'biennially'=>24,'triennially'=>36];
                            $m = $months[$price->billing_cycle] ?? 1;
                            $monthlyEq = $price->price / $m;
                            $cycleLabels = ['monthly'=>'Mensal','quarterly'=>'Trimestral','semiannually'=>'Semestral','annually'=>'Anual','biennially'=>'Bienal','triennially'=>'Trienal','free'=>'Grátis'];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 font-semibold text-gray-900">{{ $cycleLabels[$price->billing_cycle] ?? ucfirst($price->billing_cycle) }}</td>
                            <td class="py-3 text-right font-bold text-gray-900">R$ {{ number_format($price->price, 2, ',', '.') }}</td>
                            <td class="py-3 text-right text-gray-500">R$ {{ number_format($monthlyEq, 2, ',', '.') }}/mês</td>
                            <td class="py-3 text-right">
                                <a href="{{ route('client.orders.catalog') }}?product={{ $product->id }}&cycle={{ $price->billing_cycle }}"
                                   class="text-blue-600 hover:text-blue-700 font-semibold text-xs">Contratar →</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </section>
</div>

{{-- CTA Final --}}
<section class="py-14 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-center">
    <h2 class="text-3xl font-extrabold mb-3">Pronto para começar?</h2>
    <p class="text-blue-100 mb-6">Ative seu {{ $product->name }} em menos de 5 minutos.</p>
    <a href="{{ route('client.orders.catalog') }}?product={{ $product->id }}"
       class="bg-white text-blue-700 font-bold text-lg px-10 py-4 rounded-2xl hover:bg-blue-50 inline-block">
        Contratar {{ $product->name }}
    </a>
</section>
@endsection
