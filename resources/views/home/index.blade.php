<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $settings['meta_description'] ?? 'Hospedagem de sites profissional com suporte 24h.' }}">
    <title>{{ config('app.name') }} — Hospedagem Profissional</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1a56db">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { primary: { 600:'#1a56db', 700:'#1e429f' } } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        html { scroll-behavior: smooth; }
        .hero-bg { background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #1a56db 100%); }
        .card-hover { transition: transform .2s, box-shadow .2s; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,.12); }
        .gradient-text { background: linear-gradient(135deg, #1a56db, #7c3aed); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="bg-white text-gray-900" x-data="homePage()">

    {{-- Navbar --}}
    <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" :class="scrolled ? 'bg-white shadow-md' : 'bg-transparent'">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center">
                        <i class="bi bi-server text-white text-sm"></i>
                    </div>
                    <span :class="scrolled ? 'text-gray-900' : 'text-white'" class="font-bold text-lg">{{ config('app.name') }}</span>
                </div>
                <div class="hidden md:flex items-center gap-6">
                    <a href="{{ route('store') }}" :class="scrolled ? 'text-gray-600 hover:text-gray-900' : 'text-white/80 hover:text-white'" class="text-sm font-medium">Loja</a>
                    <a href="#plans" :class="scrolled ? 'text-gray-600 hover:text-gray-900' : 'text-white/80 hover:text-white'" class="text-sm font-medium">Planos</a>
                    <a href="{{ route('domain.search') }}" :class="scrolled ? 'text-gray-600 hover:text-gray-900' : 'text-white/80 hover:text-white'" class="text-sm font-medium">Domínios</a>
                    <a href="{{ route('kb') }}" :class="scrolled ? 'text-gray-600 hover:text-gray-900' : 'text-white/80 hover:text-white'" class="text-sm font-medium">Suporte</a>
                    <a href="{{ route('contact') }}" :class="scrolled ? 'text-gray-600 hover:text-gray-900' : 'text-white/80 hover:text-white'" class="text-sm font-medium">Contato</a>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('client.login') }}" :class="scrolled ? 'text-gray-700 hover:text-gray-900' : 'text-white/90 hover:text-white'" class="text-sm font-semibold">Entrar</a>
                    <a href="{{ route('client.register') }}" class="bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">Criar conta</a>
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="hero-bg text-white min-h-screen flex items-center pt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
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
                <a href="#plans" class="bg-white text-blue-700 hover:bg-blue-50 font-bold text-lg px-8 py-4 rounded-2xl transition shadow-lg">
                    Ver Planos e Preços
                </a>
                <a href="{{ route('client.register') }}" class="border-2 border-white/40 hover:border-white text-white font-bold text-lg px-8 py-4 rounded-2xl transition">
                    Começar Grátis
                </a>
            </div>

            {{-- Stats --}}
            <div class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl mx-auto">
                @foreach([['99.9%','Uptime Garantido'],['24/7','Suporte Técnico'],['⚡','Servidores SSD NVMe'],['🔒','SSL Grátis']] as [$v,$l])
                <div class="bg-white/10 backdrop-blur rounded-2xl p-5 text-center">
                    <div class="text-3xl font-extrabold mb-1">{{ $v }}</div>
                    <div class="text-blue-200 text-sm">{{ $l }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Planos --}}
    <section id="plans" class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-4xl font-extrabold text-gray-900">Planos de Hospedagem</h2>
                <p class="text-gray-500 mt-3 text-lg">Escolha o plano ideal para o seu projeto</p>
            </div>

            @forelse($productGroups ?? [] as $group)
            <div class="mb-16">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">{{ $group->name }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($group->products as $product)
                    @php $price = $product->prices['monthly'] ?? null; @endphp
                    <div class="bg-white rounded-2xl border {{ $product->featured ? 'border-blue-400 shadow-2xl ring-2 ring-blue-200' : 'border-gray-100 shadow-sm' }} overflow-hidden card-hover flex flex-col relative">
                        @if($product->featured)
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white text-center py-2 text-xs font-bold tracking-widest uppercase">⭐ Mais Popular</div>
                        @endif
                        <div class="p-7 flex-1">
                            <h4 class="font-bold text-xl text-gray-900 mb-1">{{ $product->name }}</h4>
                            @if($product->tagline) <p class="text-gray-500 text-sm mb-4">{{ $product->tagline }}</p> @endif
                            <div class="mb-6">
                                @if($price !== null)
                                <div class="flex items-baseline gap-1">
                                    <span class="text-4xl font-extrabold text-gray-900">R$ {{ number_format($price, 2, ',', '.') }}</span>
                                    <span class="text-gray-400 text-sm">/mês</span>
                                </div>
                                @else
                                <div class="text-2xl font-bold text-gray-400">Consulte</div>
                                @endif
                            </div>
                            @if($product->features)
                            <ul class="space-y-2.5">
                                @foreach($product->features as $f)
                                <li class="flex items-start gap-2 text-sm text-gray-700">
                                    <i class="bi bi-check-circle-fill text-green-500 flex-shrink-0 mt-0.5"></i> {{ $f }}
                                </li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                        <div class="p-7 pt-0">
                            <a href="{{ route('client.orders.catalog') }}" class="block w-full text-center {{ $product->featured ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white' : 'border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white' }} font-bold py-3.5 rounded-xl transition">
                                Contratar Agora
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @empty
            <p class="text-center text-gray-400">Planos em breve.</p>
            @endforelse
        </div>
    </section>

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

    {{-- Footer --}}
    <footer class="bg-slate-900 text-slate-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h4 class="font-bold text-white mb-4">{{ config('app.name') }}</h4>
                    <p class="text-sm leading-relaxed">Hospedagem profissional com suporte especializado e infraestrutura de alta performance.</p>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-4">Serviços</h4>
                    <ul class="space-y-2 text-sm"><li><a href="#plans" class="hover:text-white">Hospedagem</a></li><li><a href="#plans" class="hover:text-white">VPS</a></li><li><a href="#plans" class="hover:text-white">Revenda</a></li><li><a href="#plans" class="hover:text-white">Domínios</a></li></ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-4">Suporte</h4>
                    <ul class="space-y-2 text-sm"><li><a href="{{ route('client.login') }}" class="hover:text-white">Área do Cliente</a></li><li><a href="/suporte" class="hover:text-white">Base de Conhecimento</a></li><li><a href="/contato" class="hover:text-white">Contato</a></li></ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm"><li><a href="/termos" class="hover:text-white">Termos de Serviço</a></li><li><a href="/privacidade" class="hover:text-white">Privacidade</a></li><li><a href="/sla" class="hover:text-white">SLA</a></li></ul>
                </div>
            </div>
            <div class="border-t border-slate-800 pt-6 text-center text-sm">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
    function homePage() {
        return {
            scrolled: false,
            init() { window.addEventListener('scroll', () => { this.scrolled = window.scrollY > 20; }); }
        }
    }
    </script>
</body>
</html>
