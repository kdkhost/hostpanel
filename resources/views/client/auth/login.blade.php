<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Entrar — {{ config('app.name') }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1a56db">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --hp-primary: #1a56db; }
        * { box-sizing: border-box; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #1a56db 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', system-ui, sans-serif; }
        .auth-card { background: #fff; border-radius: 1.25rem; box-shadow: 0 25px 70px rgba(0,0,0,.4); width: 100%; max-width: 440px; overflow: hidden; }
        .auth-header { background: linear-gradient(135deg, #1a56db, #7c3aed); padding: 2rem; text-align: center; color: white; }
        .auth-header h1 { font-size: 1.75rem; font-weight: 800; margin: .5rem 0 .25rem; }
        .auth-header p { opacity: .8; font-size: .9rem; margin: 0; }
        .auth-body { padding: 2rem; }
        .form-control { border-radius: .625rem; padding: .75rem 1rem; border: 1.5px solid #e5e7eb; }
        .form-control:focus { border-color: var(--hp-primary); box-shadow: 0 0 0 3px rgba(26,86,219,.15); }
        .btn-auth { background: linear-gradient(135deg, #1a56db, #7c3aed); border: none; color: white; padding: .875rem; border-radius: .625rem; font-weight: 700; font-size: 1rem; }
        .btn-auth:hover { opacity: .9; color: white; }
        .divider { text-align: center; position: relative; margin: 1.25rem 0; }
        .divider::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: #e5e7eb; }
        .divider span { background: white; padding: 0 .75rem; color: #9ca3af; font-size: .875rem; position: relative; }
        .social-links { font-size: .875rem; }
    </style>
</head>
<body>
<div class="auth-card" x-data="loginForm()">
    <div class="auth-header">
        <i class="bi bi-server fs-1"></i>
        <h1>{{ config('app.name') }}</h1>
        <p>Acesse sua área do cliente</p>
    </div>
    <div class="auth-body">
        @if(session('error'))
            <div class="alert alert-danger py-2 mb-3">{{ session('error') }}</div>
        @endif
        <div class="alert alert-danger py-2 mb-3 d-none" id="error-msg"></div>

        <form @submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark">E-mail</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" class="form-control border-start-0 ps-1" placeholder="seu@email.com" x-model="email" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <label class="form-label fw-semibold text-dark">Senha</label>
                    <a href="{{ route('client.password.forgot') }}" class="text-primary small">Esqueceu a senha?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input :type="showPass?'text':'password'" class="form-control border-start-0 border-end-0 ps-1" placeholder="••••••••" x-model="password" required>
                    <button type="button" class="input-group-text bg-white border-start-0" @click="showPass=!showPass">
                        <i :class="showPass?'bi bi-eye-slash':'bi bi-eye'" class="text-muted"></i>
                    </button>
                </div>
            </div>
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" x-model="remember">
                    <label class="form-check-label text-muted" for="remember">Lembrar-me</label>
                </div>
            </div>
            <button type="submit" class="btn btn-auth w-100 mb-3" :disabled="loading">
                <span x-show="loading" class="spinner-border spinner-border-sm me-1"></span>
                <span x-text="loading ? 'Entrando...' : 'Entrar na Conta'"></span>
            </button>
        </form>

        <div class="divider"><span>ou</span></div>

        <div class="text-center social-links">
            <p class="text-muted mb-0">Ainda não tem uma conta?
                <a href="{{ route('client.register') }}" class="text-primary fw-semibold">Criar conta grátis</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function loginForm() {
    return {
        email:'', password:'', remember:false, loading:false, showPass:false,
        async submit() {
            this.loading = true;
            document.getElementById('error-msg').classList.add('d-none');
            const res  = await fetch('{{ route("client.login.post") }}', {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' },
                body: JSON.stringify({ email: this.email, password: this.password, remember: this.remember })
            });
            const data = await res.json();
            this.loading = false;
            if (data.redirect) { window.location.href = data.redirect; return; }
            if (data.two_factor_required) { window.location.href = '{{ url("/cliente/2fa") }}'; return; }
            const el = document.getElementById('error-msg');
            el.textContent = data.message || 'Erro ao fazer login.';
            el.classList.remove('d-none');
        }
    }
}
</script>
</body>
</html>
