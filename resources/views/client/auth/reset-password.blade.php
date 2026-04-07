<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha — {{ config('app.name') }}</title>
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

        <div class="bg-white rounded-2xl shadow-2xl p-8" x-data="resetPassword()">
            <div class="text-center mb-6">
                <div class="w-14 h-14 bg-green-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900">Redefinir Senha</h1>
                <p class="text-gray-500 text-sm mt-1">Crie uma nova senha segura para sua conta.</p>
            </div>

            <div x-show="success" class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4 text-center">
                <p class="text-green-700 text-sm font-medium">✅ Senha redefinida com sucesso!</p>
                <a href="{{ route('client.login') }}" class="text-green-700 underline text-sm font-semibold mt-1 block">Entrar agora →</a>
            </div>

            <form @submit.prevent="reset" x-show="!success">
                <input type="hidden" name="token" value="{{ $token ?? '' }}">

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">E-mail</label>
                    <input type="email" x-model="form.email" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 bg-gray-50"
                           value="{{ $email ?? '' }}" placeholder="seu@email.com">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nova Senha</label>
                    <input type="password" x-model="form.password" required minlength="8"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500"
                           placeholder="Mínimo 8 caracteres">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirmar Nova Senha</label>
                    <input type="password" x-model="form.password_confirmation" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500"
                           placeholder="Repita a nova senha">
                    <p class="text-xs text-red-500 mt-1" x-show="form.password && form.password_confirmation && form.password !== form.password_confirmation">
                        As senhas não coincidem.
                    </p>
                </div>

                <div x-show="error" class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-600" x-text="error"></div>

                <button type="submit"
                        :disabled="loading || (form.password !== form.password_confirmation)"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold py-3 px-4 rounded-xl transition text-sm">
                    <span x-show="loading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></span>
                    <span x-text="loading ? 'Redefinindo...' : 'Redefinir Senha'"></span>
                </button>
            </form>
        </div>
    </div>

    <script>
    function resetPassword() {
        return {
            form: { email: '{{ $email ?? "" }}', password: '', password_confirmation: '', token: '{{ $token ?? "" }}' },
            loading: false, success: false, error: '',

            async reset() {
                this.loading = true;
                this.error   = '';
                try {
                    const r = await fetch('{{ route("client.password.update") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify(this.form),
                    });
                    const d = await r.json();
                    if (r.ok) { this.success = true; }
                    else { this.error = d.message ?? 'Não foi possível redefinir a senha.'; }
                } catch { this.error = 'Erro de conexão. Tente novamente.'; }
                this.loading = false;
            }
        }
    }
    </script>
</body>
</html>
