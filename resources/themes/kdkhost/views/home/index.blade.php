@extends('layouts.guest')
@section('title', config('app.name') . ' — Hospedagem Profissional')

@section('content')

{{-- Quick links --}}
<div class="kdk-quicklinks">
    <div class="max-w-7xl mx-auto px-4 flex items-center gap-1 overflow-x-auto">
        <a href="{{ route('announcements') }}"><i class="bi bi-megaphone"></i> Anúncios</a>
        <a href="{{ route('kb') }}"><i class="bi bi-book"></i> Suporte</a>
        <a href="{{ route('domain.search') }}"><i class="bi bi-globe"></i> Domínios</a>
        <a href="{{ route('client.tickets.create') }}"><i class="bi bi-headset"></i> Abrir Ticket</a>
    </div>
</div>

{{-- Hero --}}
<section class="kdk-hero py-20">
    <div class="kdk-hero-content max-w-3xl mx-auto px-4 text-center">
        <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 rounded-full px-4 py-1.5 text-white/80 text-sm mb-6">
            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
            Servidores operando normalmente
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-white leading-tight mb-4">
            Comece a busca por seu<br><span class="text-blue-300">nome de domínio perfeito</span>
        </h1>
        <p class="text-blue-200 text-lg mb-8">Hospedagem profissional com performance e suporte 24h.</p>
        <div class="kdk-domain-search max-w-2xl mx-auto">
            <form action="{{ route('domain.search') }}" method="GET" class="flex gap-3">
                <div class="flex-1 relative">
                    <i class="bi bi-globe absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="dominio" placeholder="seudominio.com.br" class="kdk-domain-input pl-11">
                </div>
                <button type="submit" class="btn-kdk-accent px-6 shrink-0"><i class="bi bi-search"></i> Buscar</button>
            </form>
            <div class="flex flex-wrap gap-2 mt-3 justify-center">
                @foreach(['.com.br', '.com', '.net', '.org', '.net.br'] as $tld)
                <span class="bg-white/10 text-white/70 text-xs px-3 py-1 rounded-full font-mono">{{ $tld }}</span>
                @endforeach
            </div>
        </div>
        <div class="flex items-center justify-center gap-4 mt-8">
            <a href="{{ route('store') }}" class="btn-kdk-primary">Ver Planos de Hospedagem →</a>
            <a href="{{ route('plans') }}" class="btn-kdk-outline">Ver Preços</a>
        </div>
    </div>
</section>

{{-- Produtos por categoria --}}
<section class="kdk-section bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-10">
            <h2 class="kdk-section-title">Veja os Produtos/Serviços</h2>
            <p class="kdk-section-subtitle">Escolha a solução ideal para o seu projeto</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @php
                $cats = [
                    ['icon'=>'hdd-network','color'=>'blue','title'=>'HOSPEDAGEM','desc'=>'Sites rápidos e seguros com cPanel e SSL grátis.','link'=>route('plans')],
                    ['icon'=>'diagram-3','color'=>'indigo','title'=>'REVENDA','desc'=>'Venda hospedagem com sua marca. Suporte incluído.','link'=>route('store')],
                    ['icon'=>'cpu','color'=>'violet','title'=>'GERENCIAMENTO DE SERVIDOR','desc'=>'Gerenciamento completo do seu servidor dedicado/VPS.','link'=>route('store')],
                    ['icon'=>'play-circle','color'=>'green','title'=>'PLAYER DE VÍDEO','desc'=>'30 dias de acesso ao Player de Vídeo profissional.','link'=>route('store')],
                ];
            @endphp
            @foreach($cats as $cat)
            <div class="kdk-card p-6 hover:shadow-lg">
                <div class="w-12 h-12 bg-{{ $cat['color'] }}-100 rounded-xl flex items-center justify-center mb-4">
                    <i class="bi bi-{{ $cat['icon'] }} text-{{ $cat['color'] }}-600 text-xl"></i>
                </div>
                <h3 class="font-extrabold text-gray-900 text-base mb-2">{{ $cat['title'] }}</h3>
                <p class="text-gray-500 text-sm mb-4 flex-1">{{ $cat['desc'] }}</p>
                <a href="{{ $cat['link'] }}" class="text-blue-600 text-sm font-bold hover:underline">
                    Navegar pelos Produtos →
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Domain pricing --}}
<section class="kdk-section-sm bg-white">
    <div class="max-w-5xl mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-10 items-center">
            <div>
                <h2 class="kdk-section-title mb-3">Proteja seu nome de domínio</h2>
                <p class="text-gray-500 mb-6">Registre agora e garanta sua presença online com segurança.</p>
                <div class="space-y-2">
                    @foreach(['.com.br'=>'R$ 60,00', '.com'=>'R$ 65,00', '.net'=>'R$ 75,00', '.org'=>'R$ 70,00'] as $ext => $price)
                    <div class="kdk-domain-row">
                        <span class="font-bold text-gray-800 font-mono">{{ $ext }}</span>
                        <div class="flex items-center gap-3">
                            <span class="font-extrabold text-blue-700">{{ $price }}</span>
                            <a href="{{ route('domain.search') }}" class="btn-kdk-primary text-xs py-1.5 px-3">Registrar</a>
                        </div>
                    </div>
                    @endforeach
                </div>
                <a href="{{ route('domain.search') }}" class="inline-block mt-4 text-blue-600 text-sm font-semibold hover:underline">Ver todos os preços →</a>
            </div>
            <div class="flex flex-col gap-4">
                <div class="bg-blue-50 border border-blue-100 rounded-2xl p-6">
                    <h4 class="font-bold text-blue-900 mb-2"><i class="bi bi-globe2 me-2"></i>Registrar um Novo Domínio</h4>
                    <p class="text-blue-700 text-sm mb-3">Proteja seu nome de domínio registrando-o hoje</p>
                    <a href="{{ route('domain.search') }}" class="btn-kdk-primary text-sm">Pesquisa de Domínio →</a>
                </div>
                <div class="bg-green-50 border border-green-100 rounded-2xl p-6">
                    <h4 class="font-bold text-green-900 mb-2"><i class="bi bi-arrow-repeat me-2"></i>Transferir Seu Domínio</h4>
                    <p class="text-green-700 text-sm mb-3">Transfira agora para estender seu domínio por 1 ano</p>
                    <a href="{{ route('domain.search') }}" class="btn-kdk-accent text-sm">Transferir Seu Domínio →</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Depoimentos --}}
<section class="kdk-section bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="kdk-section-title text-center mb-10">O que nossos clientes dizem</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach([
                ['text'=>'Ótima empresa! Produtos bem definidos, plataforma fácil de usar, entrega super rápida e suporte excelente.','name'=>'Carlos M.'],
                ['text'=>'Upgrade e migração muito simples e rápidos. Em 15 anos testei muitas hospedagens, esta é a melhor até hoje.','name'=>'Ana P.'],
                ['text'=>'Comunicação fácil, sempre rápidos para responder. Ótima experiência desde o primeiro contato.','name'=>'Roberto S.'],
            ] as $t)
            <div class="kdk-testimonial">
                <div class="stars mb-3">★★★★★</div>
                <p class="text-gray-600 text-sm leading-relaxed mb-4">"{{ $t['text'] }}"</p>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-person text-blue-600 text-sm"></i>
                    </div>
                    <span class="font-semibold text-gray-800 text-sm">{{ $t['name'] }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA Final --}}
<section class="py-16 bg-gradient-to-br from-slate-900 to-blue-900 text-white text-center">
    <div class="max-w-2xl mx-auto px-4">
        <h2 class="text-3xl font-extrabold mb-3">Pronto para começar?</h2>
        <p class="text-blue-200 mb-8">Crie sua conta agora e tenha sua hospedagem ativa em minutos.</p>
        <div class="flex items-center justify-center gap-4 flex-wrap">
            <a href="{{ route('store') }}" class="btn-kdk-primary">Ver Planos →</a>
            <a href="{{ route('client.register') }}" class="btn-kdk-outline">Criar Conta Grátis</a>
        </div>
    </div>
</section>

@endsection
