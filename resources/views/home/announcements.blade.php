<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avisos & Novidades — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

    <section class="bg-gradient-to-br from-slate-900 to-blue-900 text-white py-14">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-extrabold mb-2">Avisos & Novidades</h1>
            <p class="text-blue-200">Fique por dentro de tudo que acontece na {{ config('app.name') }}.</p>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-3xl mx-auto px-4">
            @forelse($announcements as $ann)
            @php
                $typeColors = ['info'=>'blue','warning'=>'amber','danger'=>'red','success'=>'green'];
                $typeIcons  = ['info'=>'bi-info-circle','warning'=>'bi-exclamation-triangle','danger'=>'bi-x-octagon','success'=>'bi-check-circle'];
                $type   = $ann->type ?? 'info';
                $color  = $typeColors[$type] ?? 'blue';
                $icon   = $typeIcons[$type] ?? 'bi-megaphone';
            @endphp
            <article class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-4 overflow-hidden">
                <div class="border-l-4 border-{{ $color }}-500 p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-{{ $color }}-50 flex items-center justify-center flex-shrink-0">
                            <i class="bi {{ $icon }} text-{{ $color }}-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-2">
                                <h2 class="font-bold text-gray-900 text-lg">{{ $ann->title }}</h2>
                                <span class="text-xs bg-{{ $color }}-100 text-{{ $color }}-700 px-2 py-0.5 rounded-full font-semibold capitalize">{{ $type }}</span>
                            </div>
                            <div class="text-gray-600 text-sm leading-relaxed prose prose-sm max-w-none">
                                {!! nl2br(e($ann->content)) !!}
                            </div>
                            <div class="flex items-center gap-3 mt-3 text-xs text-gray-400">
                                <span><i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($ann->created_at)->format('d/m/Y') }}</span>
                                @if($ann->expires_at)
                                <span><i class="bi bi-clock me-1"></i>Válido até {{ \Carbon\Carbon::parse($ann->expires_at)->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </article>
            @empty
            <div class="text-center py-20 text-gray-400">
                <i class="bi bi-megaphone display-3 d-block mb-3"></i>
                <p class="text-lg font-medium">Nenhum aviso no momento.</p>
            </div>
            @endforelse

            @if($announcements->hasPages())
            <div class="mt-6">{{ $announcements->links() }}</div>
            @endif
        </div>
    </section>

    <footer class="bg-slate-900 text-slate-400 py-8 text-center text-sm mt-4">
        &copy; {{ date('Y') }} {{ config('app.name') }}.
        &mdash; <a href="{{ route('home') }}" class="hover:text-white">Voltar ao início</a>
    </footer>
</body>
</html>
