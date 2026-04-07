<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Base de Conhecimento — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-50 text-gray-900">

    {{-- Navbar --}}
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-gray-900">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center"><i class="bi bi-server text-white text-sm"></i></div>
                {{ config('app.name') }}
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ route('client.login') }}" class="text-gray-600 hover:text-gray-900 text-sm font-semibold">Área do Cliente</a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-slate-900 to-blue-900 text-white py-16">
        <div class="max-w-3xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-extrabold mb-3">Base de Conhecimento</h1>
            <p class="text-blue-200 mb-8">Encontre respostas rápidas para as suas dúvidas.</p>
            <form method="GET" action="{{ route('kb') }}" class="flex gap-2 max-w-xl mx-auto">
                <input type="text" name="q" value="{{ request('q') }}"
                       class="flex-1 px-4 py-3 rounded-xl text-gray-900 text-sm focus:outline-none"
                       placeholder="Buscar artigos... (ex: instalar WordPress)">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold text-sm">Buscar</button>
            </form>
        </div>
    </section>

    {{-- Artigos --}}
    <section class="py-12">
        <div class="max-w-4xl mx-auto px-4">
            @if(request('q'))
            <p class="text-gray-500 mb-6 text-sm">
                {{ $articles->total() }} resultado(s) para "<strong>{{ request('q') }}</strong>"
                — <a href="{{ route('kb') }}" class="text-blue-600 hover:text-blue-700">Limpar busca</a>
            </p>
            @endif

            @forelse($articles as $article)
            <a href="{{ route('kb.article', $article->slug) }}"
               class="block bg-white rounded-xl border border-gray-100 hover:border-blue-300 hover:shadow-md p-5 mb-3 transition">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="bi bi-file-text text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base mb-1 group-hover:text-blue-600">{{ $article->title }}</h3>
                            @if($article->excerpt)
                            <p class="text-gray-500 text-sm line-clamp-2">{{ $article->excerpt }}</p>
                            @endif
                            <div class="flex items-center gap-3 mt-2">
                                @if($article->category)
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $article->category }}</span>
                                @endif
                                <span class="text-xs text-gray-400"><i class="bi bi-eye me-1"></i>{{ $article->views ?? 0 }} visualizações</span>
                                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($article->updated_at)->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-gray-300 flex-shrink-0 mt-1"></i>
                </div>
            </a>
            @empty
            <div class="text-center py-20 text-gray-400">
                <i class="bi bi-search display-3 d-block mb-3"></i>
                <p class="text-lg font-medium">Nenhum artigo encontrado.</p>
                @if(request('q'))
                <p class="text-sm mt-2">Tente outros termos ou <a href="{{ route('client.tickets.index') }}" class="text-blue-600">abra um chamado</a>.</p>
                @endif
            </div>
            @endforelse

            <div class="mt-6">{{ $articles->links() }}</div>
        </div>
    </section>

    <footer class="bg-slate-900 text-slate-400 py-8 text-center text-sm">
        &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
        &mdash; <a href="{{ route('home') }}" class="hover:text-white">Voltar ao início</a>
    </footer>
</body>
</html>
