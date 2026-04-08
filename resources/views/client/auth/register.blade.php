<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Criar Conta — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @include('partials.hostpanel-ui-head')
    <style>
        body { background: linear-gradient(135deg,#0f172a 0%,#1e3a8a 50%,#1a56db 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Segoe UI',system-ui,sans-serif; padding:2rem 1rem; }
        .auth-card { background:#fff; border-radius:1.25rem; box-shadow:0 25px 70px rgba(0,0,0,.4); width:100%; max-width:520px; overflow:hidden; }
        .auth-header { background:linear-gradient(135deg,#1a56db,#7c3aed); padding:1.75rem; text-align:center; color:white; }
        .auth-body { padding:2rem; }
        .form-control, .form-select { border-radius:.625rem; padding:.75rem 1rem; border:1.5px solid #e5e7eb; }
        .form-control:focus, .form-select:focus { border-color:#1a56db; box-shadow:0 0 0 3px rgba(26,86,219,.15); }
        .btn-auth { background:linear-gradient(135deg,#1a56db,#7c3aed); border:none; color:white; padding:.875rem; border-radius:.625rem; font-weight:700; }
        .btn-auth:hover { opacity:.9; color:white; }
    </style>
</head>
<body>
<div class="auth-card" x-data="registerForm()">
    <div class="auth-header">
        <i class="bi bi-person-plus-fill fs-2 d-block mb-1"></i>
        <h2 class="fw-800 mb-0">Criar sua conta</h2>
        <p class="opacity-75 mb-0 mt-1 small">{{ config('app.name') }}</p>
    </div>
    <div class="auth-body">
        <div class="alert alert-danger py-2 d-none" id="error-msg"></div>
        <form @submit.prevent="submit">
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Nome Completo *</label>
                    <input type="text" class="form-control" x-model="form.name" required autofocus placeholder="Seu nome">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">E-mail *</label>
                    <input type="email" class="form-control" x-model="form.email" required placeholder="seu@email.com">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Tipo</label>
                    <select class="form-select" x-model="form.document_type">
                        <option value="cpf">CPF</option>
                        <option value="cnpj">CNPJ</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label fw-semibold" x-text="form.document_type === 'cpf' ? 'CPF' : 'CNPJ'"> </label>
                    <input type="text" class="form-control" x-model="form.document_number" :placeholder="form.document_type === 'cpf' ? '000.000.000-00' : '00.000.000/0001-00'">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Telefone</label>
                    <input type="text" class="form-control" x-model="form.phone" placeholder="(11) 99999-9999">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">WhatsApp</label>
                    <input type="text" class="form-control" x-model="form.whatsapp" placeholder="(11) 99999-9999">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Senha *</label>
                    <input type="password" class="form-control" x-model="form.password" required placeholder="Mín. 8 caracteres">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Confirmar Senha *</label>
                    <input type="password" class="form-control" x-model="form.password_confirmation" required placeholder="Repita a senha">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" x-model="form.terms_accepted" required>
                        <label class="form-check-label text-muted" for="terms">
                            Li e aceito os <a href="/termos" target="_blank" class="text-primary">Termos de Serviço</a> e a <a href="/privacidade" target="_blank" class="text-primary">Política de Privacidade</a>
                        </label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-auth w-100 mb-3" :disabled="loading">
                <span x-show="loading" class="spinner-border spinner-border-sm me-1"></span>
                <span x-text="loading ? 'Criando conta...' : 'Criar Conta Grátis'"></span>
            </button>
        </form>
        <div class="text-center">
            <small class="text-muted">Já tem uma conta? <a href="{{ route('client.login') }}" class="text-primary fw-semibold">Entrar</a></small>
        </div>
    </div>
</div>
@include('partials.hostpanel-ui-scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function registerForm() {
    return {
        loading: false,
        form: { name:'', email:'', document_type:'cpf', document_number:'', phone:'', whatsapp:'', password:'', password_confirmation:'', terms_accepted:false },
        async submit() {
            this.loading = true;
            document.getElementById('error-msg').classList.add('d-none');
            const res  = await fetch('{{ route("client.register.post") }}', {
                method:'POST',
                headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' },
                body: JSON.stringify(this.form)
            });
            const data = await res.json();
            this.loading = false;
            if (data.redirect) { window.location.href = data.redirect; return; }
            const el = document.getElementById('error-msg');
            el.innerHTML = typeof data.errors === 'object'
                ? Object.values(data.errors).flat().join('<br>')
                : (data.message || 'Erro ao criar conta.');
            el.classList.remove('d-none');
        }
    }
}
</script>
</body>
</html>
