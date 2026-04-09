@extends('home.layouts.app')

@section('title', 'Busca de Domínios — ' . config('app.name'))
@section('meta-description', 'Verifique a disponibilidade de domínios e registre seu endereço online.')

@push('head')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush

@section('content')
{{-- Hero --}}
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

{{-- Resultados --}}
<section class="py-12 max-w-4xl mx-auto px-4" x-data="{ checking: false }">
    @if($domain)
    <div class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Resultado para "<span class="text-blue-600">{{ $domain }}</span>"</h2>

        @if(isset($available))
        <div class="bg-white rounded-2xl border {{ $available ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} shadow-sm p-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 {{ $available ? 'bg-green-100' : 'bg-red-100' }} rounded-xl flex items-center justify-center">
                        <i class="bi {{ $available ? 'bi-check-circle text-green-600' : 'bi-x-circle text-red-600' }} text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">{{ $domain }}</h3>
                        <p class="text-sm {{ $available ? 'text-green-600' : 'text-red-600' }}">
                            {{ $available ? 'Domínio disponível!' : 'Domínio indisponível.' }}
                        </p>
                    </div>
                </div>
                @if($available)
                <a href="{{ route('cart') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl transition">
                    Adicionar ao Carrinho
                </a>
                @endif
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Preços TLD --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4">Preços por Extensão</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach(['com.br' => 'R$ 49,90', 'com' => 'R$ 69,90', 'net' => 'R$ 69,90', 'org' => 'R$ 79,90'] as $tld => $price)
            <div class="text-center p-4 border border-gray-100 rounded-xl">
                <div class="font-bold text-gray-900">.{{ $tld }}</div>
                <div class="text-sm text-gray-500">a partir de</div>
                <div class="text-blue-600 font-bold">{{ $price }}/ano</div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
