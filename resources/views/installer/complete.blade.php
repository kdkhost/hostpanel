<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação Concluída — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 to-green-900 flex items-center justify-center px-4">
    <div class="w-full max-w-lg text-center">

        <div class="bg-white rounded-2xl shadow-2xl p-10">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Instalação Concluída!</h1>
            <p class="text-gray-500 mb-8">
                O <strong>{{ config('app.name') }}</strong> foi instalado com sucesso.<br>
                Você já pode acessar o painel administrativo.
            </p>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-8 text-left">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="text-amber-800 font-semibold text-sm">Importante: Segurança</p>
                        <p class="text-amber-700 text-xs mt-1">
                            Remova ou bloqueie o acesso à pasta <code class="bg-amber-100 px-1 rounded">/install</code>
                            após concluir a configuração para proteger sua instalação.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <a href="{{ route('admin.login') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition text-sm">
                    Acessar Painel Admin
                </a>
                <a href="{{ route('home') }}"
                   class="border border-gray-200 text-gray-700 hover:bg-gray-50 font-bold py-3 px-6 rounded-xl transition text-sm">
                    Ver Site Público
                </a>
            </div>
        </div>

        <p class="text-slate-400 text-xs mt-6">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
        </p>
    </div>
</body>
</html>
