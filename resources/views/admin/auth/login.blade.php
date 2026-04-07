<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Administrativo — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg, #1e3a8a 0%, #1a56db 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { background: #fff; border-radius: 1rem; box-shadow: 0 20px 60px rgba(0,0,0,.3); width: 100%; max-width: 420px; padding: 2.5rem; }
        .login-logo { text-align: center; margin-bottom: 2rem; }
        .login-logo i { font-size: 3rem; color: #1a56db; }
        .login-logo h1 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-top: .5rem; }
        .login-logo p { color: #64748b; font-size: .875rem; }
        .btn-primary { background: #1a56db; border: none; padding: .75rem; font-weight: 600; border-radius: .5rem; }
        .btn-primary:hover { background: #1e429f; }
        .form-control { border-radius: .5rem; padding: .75rem 1rem; }
        .form-control:focus { border-color: #1a56db; box-shadow: 0 0 0 .25rem rgba(26,86,219,.2); }
        .input-group-text { border-radius: .5rem 0 0 .5rem; background: #f8fafc; }
    </style>
</head>
<body>
<div class="login-card" x-data="loginForm()">
    <div class="login-logo">
        <i class="bi bi-shield-lock-fill"></i>
        <h1>{{ config('app.name') }}</h1>
        <p>Painel Administrativo</p>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-sm py-2">{{ session('error') }}</div>
    @endif

    <div id="error-msg" class="alert alert-danger d-none py-2"></div>

    <form id="login-form" @submit.prevent="submit">
        <div class="mb-3">
            <label class="form-label fw-semibold">E-mail</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="admin@empresa.com" required autofocus x-model="email">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Senha</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input :type="showPass ? 'text' : 'password'" name="password" class="form-control" placeholder="••••••••" required x-model="password">
                <button type="button" class="btn btn-outline-secondary" @click="showPass = !showPass">
                    <i :class="showPass ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
            </div>
        </div>
        <div class="mb-4 form-check">
            <input type="checkbox" class="form-check-input" id="remember" x-model="remember">
            <label class="form-check-label text-muted" for="remember">Lembrar-me</label>
        </div>
        <button type="submit" class="btn btn-primary w-100" :disabled="loading">
            <span x-show="loading" class="spinner-border spinner-border-sm me-1"></span>
            <span x-text="loading ? 'Entrando...' : 'Entrar no Painel'"></span>
        </button>
    </form>

    <div class="text-center mt-3">
        <small class="text-muted">{{ config('app.name') }} &copy; {{ date('Y') }}</small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function loginForm() {
    return {
        email: '', password: '', remember: false, loading: false, showPass: false,
        async submit() {
            this.loading = true;
            document.getElementById('error-msg').classList.add('d-none');
            const res = await fetch('{{ route("admin.login.post") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ email: this.email, password: this.password, remember: this.remember })
            });
            const data = await res.json();
            this.loading = false;
            if (data.redirect) { window.location.href = data.redirect; return; }
            if (data.two_factor_required) { window.location.href = '{{ url("/admin/2fa") }}'; return; }
            const err = document.getElementById('error-msg');
            err.textContent = data.message || 'Erro ao fazer login.';
            err.classList.remove('d-none');
        }
    }
}
</script>
</body>
</html>
