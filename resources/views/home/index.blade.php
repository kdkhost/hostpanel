@extends('home.layouts.app')

@section('title', config('app.name') . ' — Hospedagem Profissional')
@section('meta-description', $settings['meta_description'] ?? 'Hospedagem de sites profissional com suporte 24h.')

@push('head')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    .hero-bg { background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #1a56db 100%); }
    .carousel-track { scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; }
    .carousel-item { scroll-snap-align: start; }
    .carousel-track::-webkit-scrollbar { display: none; }
    .carousel-track { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush

@section('content')
{{-- Hero --}}
<section class="hero-bg text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur rounded-full px-4 py-1.5 text-sm font-medium mb-8">
            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
            Todos os servidores operacionais
        </div>
        <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-6">
            Hospedagem de Sites<br>
            <span class="text-blue-300">Profissional & Rápida</span>
        </h1>
        <p class="text-xl text-blue-100 max-w-2xl mx-auto mb-10">
            {{ $settings['tagline'] ?? 'Planos de hospedagem compartilhada, VPS e revenda com suporte técnico especializado 24/7.' }}
        </p>
        <div class="flex flex-wrap gap-4 justify-center">
            <a href="{{ route('plans') }}" class="bg-white text-blue-700 hover:bg-blue-50 font-bold text-lg px-8 py-4 rounded-2xl transition shadow-lg">
                Ver Planos e Preços
            </a>
            <a href="{{ route('client.register') }}" class="border-2 border-white/40 hover:border-white text-white font-bold text-lg px-8 py-4 rounded-2xl transition">
                Começar Grátis
            </a>
        </div>

        {{-- Stats --}}
        <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl mx-auto">
            @foreach([['99.9%','Uptime Garantido'],['24/7','Suporte Técnico'],['⚡','Servidores SSD NVMe'],['🔒','SSL Grátis']] as [$v,$l])
            <div class="bg-white/10 backdrop-blur rounded-2xl p-5 text-center">
                <div class="text-3xl font-extrabold mb-1">{{ $v }}</div>
                <div class="text-blue-200 text-sm">{{ $l }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Destaques em Carrossel --}}
@php
$destaques = collect();
$categorias = ['hospedagem', 'revenda', 'vps', 'servidor', 'cloud', 'streaming'];

if (isset($groups) && $groups->count()) {
    foreach ($groups as $group) {
        $catKey = strtolower($group->name);
        $foundCat = null;
        foreach ($categorias as $cat) {
            if (str_contains($catKey, $cat)) {
                $foundCat = $cat;
                break;
            }
        }

        if ($foundCat && !$destaques->has($foundCat)) {
            $produto = $group->products->first(function($p) {
                return $p->featured || $p->prices['monthly'] ?? null;
            }) ?? $group->products->first();

            if ($produto) {
                $destaques->put($foundCat, [
                    'categoria' => $group->name,
                    'slug' => $group->slug,
                    'produto' => $produto,
                    'tipo' => $foundCat,
                    'icone' => match($foundCat) {
                        'hospedagem' => 'bi-hdd-stack',
                        'revenda' => 'bi-shop',
                        'vps' => 'bi-server',
                        'servidor' => 'bi-pc-display',
                        'cloud' => 'bi-cloud',
                        'streaming' => 'bi-broadcast',
                        default => 'bi-box-seam'
                    }
                ]);
            }
        }
    }
}

if ($destaques->count() < 6 && isset($announcements) && $announcements->count()) {
    $anuncio = $announcements->first();
    $destaques->put('anuncio', [
        'categoria' => 'Novidade',
        'slug' => null,
        'anuncio' => $anuncio,
        'tipo' => 'anuncio',
        'icone' => 'bi-megaphone'
    ]);
}

$destaques = $destaques->take(6);
@endphp

@if($destaques->count())
<section id="destaques" class="py-16 bg-gray-50" x-data="{ scroll: 0, maxScroll: 0, updateScroll() {
    const el = this.$refs.track;
    this.scroll = el.scrollLeft;
    this.maxScroll = el.scrollWidth - el.clientWidth;
} }" x-init="$nextTick(() => updateScroll())">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900">Destaques</h2>
                <p class="text-gray-500 text-sm mt-1">Soluções selecionadas para você</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="$refs.track.scrollBy({ left: -280, behavior: 'smooth' }); setTimeout(() => updateScroll(), 300)"
                        class="w-10 h-10 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 transition disabled:opacity-30"
                        :disabled="scroll <= 10">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button @click="$refs.track.scrollBy({ left: 280, behavior: 'smooth' }); setTimeout(() => updateScroll(), 300)"
                        class="w-10 h-10 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 transition disabled:opacity-30"
                        :disabled="scroll >= maxScroll - 10">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

        {{-- Carrossel --}}
        <div x-ref="track" @scroll.throttle="updateScroll()"
             class="carousel-track flex gap-4 overflow-x-auto pb-4">
            @foreach($destaques as $tipo => $item)
            <div class="carousel-item flex-shrink-0 w-[260px]">
                @if($tipo === 'anuncio' && isset($item['anuncio']))
                {{-- Card de Anúncio --}}
                <div class="bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl p-5 text-white h-full flex flex-col">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                            <i class="bi {{ $item['icone'] }} text-white"></i>
                        </div>
                        <span class="text-xs font-semibold bg-white/20 px-2 py-1 rounded">{{ $item['categoria'] }}</span>
                    </div>
                    <h3 class="font-bold text-lg mb-2">{{ Str::limit($item['anuncio']->title, 40) }}</h3>
                    <p class="text-blue-100 text-sm mb-4 flex-1">{{ Str::limit(strip_tags($item['anuncio']->content), 80) }}</p>
                    <a href="{{ route('announcements') }}" class="text-sm font-semibold bg-white text-blue-600 px-4 py-2 rounded-lg text-center hover:bg-blue-50 transition">
                        Saiba mais
                    </a>
                </div>
                @else
                {{-- Card de Produto --}}
                @php $produto = $item['produto']; $preco = $produto->prices['monthly'] ?? $produto->pricing->first()?->price ?? null; @endphp
                <div class="bg-white rounded-xl border {{ $produto->featured ? 'border-blue-400 ring-2 ring-blue-100' : 'border-gray-100' }} shadow-sm p-5 h-full flex flex-col relative hover:shadow-md transition">
                    @if($produto->featured)
                    <div class="absolute -top-2 left-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">⭐ Destaque</div>
                    @endif
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                            <i class="bi {{ $item['icone'] }} text-blue-600"></i>
                        </div>
                        <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded">{{ $item['categoria'] }}</span>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1">{{ Str::limit($produto->name, 35) }}</h3>
                    @if($produto->tagline)
                    <p class="text-gray-500 text-xs mb-3 line-clamp-2">{{ $produto->tagline }}</p>
                    @else
                    <p class="text-gray-400 text-xs mb-3">{{ Str::limit($produto->description ?? 'Serviço profissional', 50) }}</p>
                    @endif
                    <div class="mt-auto pt-3 border-t border-gray-100">
                        @if($preco)
                        <div class="flex items-baseline gap-1 mb-3">
                            <span class="text-2xl font-extrabold text-gray-900">R$ {{ number_format($preco, 2, ',', '.') }}</span>
                            <span class="text-gray-400 text-xs">/mês</span>
                        </div>
                        @else
                        <div class="text-gray-500 text-sm font-semibold mb-3">Consulte</div>
                        @endif
                        @php
                        $checkoutItem = json_encode([['product_id' => $produto->id, 'cycle' => $produto->pricing->first()?->billing_cycle ?? 'monthly', 'domain' => '']]);
                        @endphp
                        <a href="{{ route('checkout') }}?items={{ urlencode($checkoutItem) }}" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm py-2.5 rounded-lg transition">
                            Contratar
                        </a>
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Dots --}}
        <div class="flex justify-center gap-2 mt-4">
            @foreach($destaques as $i => $item)
            <div class="w-2 h-2 rounded-full transition-all duration-300"
                 :class="Math.round(scroll / 270) === {{ $loop->index }} ? 'bg-blue-600 w-4' : 'bg-gray-300'"></div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Features --}}
<section id="features" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-4xl font-extrabold text-gray-900">Por que escolher a {{ config('app.name') }}?</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach([
                ['bi-speedometer2','Performance Superior','Servidores SSD NVMe com CDN global, garantindo carregamento ultrarrápido.','blue'],
                ['bi-shield-check','Segurança Avançada','Firewall WAF, certificado SSL/TLS gratuito, proteção DDoS e backups diários.','green'],
                ['bi-headset','Suporte Especializado','Equipe técnica disponível 24h por dia, 7 dias por semana via ticket, e-mail e WhatsApp.','purple'],
                ['bi-hdd-stack','cPanel Incluso','Painel de controle cPanel completo com 1-click installers e gerenciador de arquivos.','orange'],
                ['bi-arrow-repeat','Backups Diários','Seus dados protegidos com backups automáticos diários e restauração fácil.','red'],
                ['bi-graph-up','99.9% Uptime','Garantia de disponibilidade com infraestrutura redundante e monitoramento contínuo.','teal'],
            ] as [$icon, $title, $desc, $color])
            <div class="group p-6 rounded-2xl border border-gray-100 hover:border-blue-100 hover:bg-blue-50/30 transition">
                <div class="w-12 h-12 rounded-xl bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center mb-4 transition">
                    <i class="bi {{ $icon }} text-blue-600 text-xl"></i>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">{{ $title }}</h3>
                <p class="text-gray-500 text-sm leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Anúncios --}}
@if(isset($announcements) && $announcements->count())
<section id="news" class="py-20 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold text-gray-900 mb-8 text-center">Novidades</h2>
        <div class="space-y-4">
            @foreach($announcements as $ann)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0"><i class="bi bi-megaphone text-blue-600"></i></div>
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $ann->title }}</h3>
                        <p class="text-gray-500 text-sm mt-1">{{ Str::limit($ann->content, 180) }}</p>
                        <span class="text-xs text-gray-400 mt-2 block">{{ \Carbon\Carbon::parse($ann->published_at)->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- CTA Final --}}
<section class="py-24 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <h2 class="text-4xl font-extrabold mb-4">Pronto para começar?</h2>
        <p class="text-blue-100 text-lg mb-8">Crie sua conta agora e tenha seu site no ar em minutos.</p>
        <a href="{{ route('client.register') }}" class="bg-white text-blue-700 font-bold text-lg px-10 py-4 rounded-2xl hover:bg-blue-50 transition shadow-lg inline-block">
            Criar Conta Grátis
        </a>
    </div>
</section>
@endsection
