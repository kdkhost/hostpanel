<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $article->title }} — {{ config('app.name') }}</title>
    <meta name="description" content="{{ $article->excerpt ?? strip_tags(substr($article->content ?? '', 0, 160)) }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .prose h2 { font-size:1.25rem;font-weight:700;margin:1.5rem 0 .75rem;color:#1e293b }
        .prose h3 { font-size:1.1rem;font-weight:700;margin:1.25rem 0 .5rem;color:#1e293b }
        .prose p  { margin-bottom:1rem;color:#475569;line-height:1.8 }
        .prose ul,.prose ol { padding-left:1.5rem;margin-bottom:1rem;color:#475569 }
        .prose li { margin-bottom:.4rem }
        .prose pre { background:#f1f5f9;border-radius:.5rem;padding:1rem;overflow-x:auto;margin:1rem 0 }
        .prose code { background:#f1f5f9;padding:.15rem .35rem;border-radius:.25rem;font-size:.875rem }
        .prose pre code { background:none;padding:0 }
        .prose a { color:#1a56db;text-decoration:underline }
        .prose blockquote { border-left:4px solid #1a56db;padding:.5rem 1rem;background:#eff6ff;margin:1rem 0;border-radius:0 .5rem .5rem 0 }
        .prose img { max-width:100%;border-radius:.75rem;margin:1rem 0 }
        .prose table { width:100%;border-collapse:collapse;margin:1rem 0 }
        .prose th,.prose td { border:1px solid #e2e8f0;padding:.5rem .75rem;text-align:left }
        .prose th { background:#f8fafc;font-weight:600 }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

    {{-- Navbar --}}
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-gray-900">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center"><i class="bi bi-server text-white text-sm"></i></div>
                {{ config('app.name') }}
            </a>
            <a href="{{ route('client.login') }}" class="text-gray-600 hover:text-gray-900 text-sm font-semibold">Área do Cliente</a>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-4 py-10">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('home') }}" class="hover:text-blue-600">Início</a>
            <span>/</span>
            <a href="{{ route('kb') }}" class="hover:text-blue-600">Base de Conhecimento</a>
            @if($article->category)
            <span>/</span>
            <span>{{ $article->category }}</span>
            @endif
        </nav>

        {{-- Artigo --}}
        <article class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-8">
                {{-- Meta --}}
                <div class="flex items-center gap-3 mb-4">
                    @if($article->category)
                    <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-3 py-1 rounded-full">{{ $article->category }}</span>
                    @endif
                    <span class="text-gray-400 text-xs"><i class="bi bi-eye me-1"></i>{{ $article->views }} visualizações</span>
                    <span class="text-gray-400 text-xs">Atualizado {{ \Carbon\Carbon::parse($article->updated_at)->format('d/m/Y') }}</span>
                </div>

                <h1 class="text-2xl font-extrabold text-gray-900 mb-6">{{ $article->title }}</h1>

                {{-- Conteúdo --}}
                <div class="prose">
                    {!! $article->content !!}
                </div>
            </div>

            {{-- Footer do Artigo --}}
            <div class="border-t border-gray-100 px-8 py-5 bg-gray-50 flex items-center justify-between flex-wrap gap-3">
                <div class="text-sm text-gray-500">Esta resposta foi útil?</div>
                <div class="flex gap-2">
                    <button class="border border-gray-200 text-gray-600 hover:bg-green-50 hover:border-green-300 hover:text-green-700 text-sm font-semibold px-4 py-2 rounded-lg flex items-center gap-1">
                        <i class="bi bi-hand-thumbs-up"></i> Sim
                    </button>
                    <button class="border border-gray-200 text-gray-600 hover:bg-red-50 hover:border-red-300 hover:text-red-700 text-sm font-semibold px-4 py-2 rounded-lg flex items-center gap-1">
                        <i class="bi bi-hand-thumbs-down"></i> Não
                    </button>
                </div>
            </div>
        </article>

        {{-- CTA Suporte --}}
        <div class="mt-6 bg-blue-50 border border-blue-100 rounded-2xl p-6 text-center">
            <p class="text-gray-700 font-medium mb-3">Não encontrou o que procurava?</p>
            <a href="{{ route('client.tickets.index') }}"
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl">
                <i class="bi bi-headset"></i> Abrir Chamado de Suporte
            </a>
        </div>

        {{-- Voltar --}}
        <div class="mt-6 text-center">
            <a href="{{ route('kb') }}" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                ← Voltar para a Base de Conhecimento
            </a>
        </div>
    </div>

    <footer class="bg-slate-900 text-slate-400 py-8 text-center text-sm mt-8">
        &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
    </footer>
</body>
</html>
