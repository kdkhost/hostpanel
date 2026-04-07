<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci a Senha — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 to-blue-900 flex items-center justify-center px-4">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-white font-bold text-xl">
                <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/></svg>
                </div>
                {{ config('app.name') }}
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl p-8" x-data="forgotPassword()">
            <div class="text-center mb-6">
                <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900">Esqueci a Senha</h1>
                <p class="text-gray-500 text-sm mt-1">Informe seu e-mail para receber o link de redefinição.</p>
            </div>

            <div x-show="sent" class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4 text-center">
                <p class="text-green-700 text-sm font-medium">✅ Link enviado! Verifique sua caixa de entrada.</p>
            </div>

            <form @submit.prevent="send" x-show="!sent">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">E-mail cadastrado</label>
                    <input type="email" x-model="email" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                           placeholder="seu@email.com">
                </div>

                <div x-show="error" class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-600" x-text="error"></div>

                <button type="submit"
                        :disabled="loading"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold py-3 px-4 rounded-xl transition text-sm">
                    <span x-show="loading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></span>
                    <span x-text="loading ? 'Enviando...' : 'Enviar Link de Redefinição'"></span>
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Lembrou a senha?
                <a href="{{ route('client.login') }}" class="text-blue-600 hover:text-blue-700 font-semibold">Entrar</a>
            </p>
        </div>
    </div>

    <script>
    function forgotPassword() {
        return {
            email: '', loading: false, sent: false, error: '',

            async send() {
                this.loading = true;
                this.error   = '';
                try {
                    const r = await fetch('{{ route("client.password.email") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ email: this.email }),
                    });
                    const d = await r.json();
                    if (r.ok) { this.sent = true; }
                    else { this.error = d.message ?? 'E-mail não encontrado.'; }
                } catch { this.error = 'Erro de conexão. Tente novamente.'; }
                this.loading = false;
            }
        }
    }
    </script>
</body>
</html>
