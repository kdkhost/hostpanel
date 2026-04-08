<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Instalador — HostPanel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @include('partials.hostpanel-ui-head')
    <style>
        :root { --hp-primary: #1a56db; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', system-ui, sans-serif; padding: 2rem 1rem; }
        .installer-card { background: #fff; border-radius: 1.5rem; box-shadow: 0 30px 80px rgba(0,0,0,.5); width: 100%; max-width: 700px; overflow: hidden; }
        .installer-header { background: linear-gradient(135deg, var(--hp-primary), #7c3aed); color: white; padding: 2rem; text-align: center; }
        .installer-header h1 { font-size: 1.75rem; font-weight: 800; margin: .5rem 0 .25rem; }
        .step-indicator { display: flex; justify-content: center; gap: 1.5rem; margin-top: 1.25rem; }
        .step-dot { width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,.2); color: white; display: flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 700; cursor: default; transition: .3s; }
        .step-dot.active { background: white; color: var(--hp-primary); }
        .step-dot.done { background: #10b981; }
        .installer-body { padding: 2rem; }
        .req-item { display: flex; align-items: center; gap: .75rem; padding: .5rem 0; border-bottom: 1px solid #f3f4f6; }
        .req-item:last-child { border: none; }
        .form-control, .form-select { border-radius: .625rem; border: 1.5px solid #e5e7eb; padding: .75rem 1rem; }
        .form-control:focus, .form-select:focus { border-color: var(--hp-primary); box-shadow: 0 0 0 3px rgba(26,86,219,.15); }
        .btn-primary { background: var(--hp-primary); border: none; border-radius: .625rem; padding: .75rem 2rem; font-weight: 700; }
        .btn-primary:hover { background: #1e429f; }
        .progress-bar-install { height: 6px; background: linear-gradient(90deg, var(--hp-primary), #7c3aed); border-radius: 3px; transition: width .5s ease; }
    </style>
</head>
<body>
<form autocomplete="off" onsubmit="return false;">
<div class="installer-card" x-data="installer()">
    <div class="installer-header">
        <i class="bi bi-server fs-1"></i>
        <h1>HostPanel — Instalador</h1>
        <p class="opacity-75 mb-0">Configure o sistema em poucos passos</p>
        <div class="step-indicator">
            <template x-for="(s, i) in steps" :key="i">
                <div :class="`step-dot ${i < step ? 'done' : (i === step ? 'active' : '')}`">
                    <span x-show="i < step"><i class="bi bi-check-lg"></i></span>
                    <span x-show="i >= step" x-text="i + 1"></span>
                </div>
            </template>
        </div>
    </div>

    <div class="installer-body">
        {{-- Progress --}}
        <div class="mb-4">
            <div class="d-flex justify-content-between mb-1">
                <span class="small fw-semibold" x-text="steps[step]"></span>
                <span class="small text-muted" x-text="`Passo ${step+1} de ${steps.length}`"></span>
            </div>
            <div class="bg-light rounded" style="height:6px">
                <div class="progress-bar-install" :style="`width: ${((step)/(steps.length-1))*100}%`"></div>
            </div>
        </div>

        {{-- STEP 0: Requisitos --}}
        <div x-show="step === 0">
            <h5 class="fw-bold mb-3"><i class="bi bi-check-circle me-2"></i>Verificação de Requisitos</h5>
            <div class="mb-4">
                @foreach($requirements as $req)
                <div class="req-item">
                    <i class="{{ $req['ok'] ? 'bi bi-check-circle-fill text-success' : 'bi bi-x-circle-fill text-danger' }} fs-5"></i>
                    <span class="{{ $req['ok'] ? '' : 'text-danger fw-semibold' }}">{{ $req['name'] }}</span>
                    <span class="ms-auto badge {{ $req['ok'] ? 'bg-success' : 'bg-danger' }}">{{ $req['ok'] ? 'OK' : 'Falhou' }}</span>
                </div>
                @endforeach
            </div>
            @php $allOk = collect($requirements)->every(fn($r) => $r['ok']); @endphp
            @if(!$allOk)
                <div class="alert alert-danger">Corrija os requisitos antes de continuar.</div>
            @endif
            <button class="btn btn-primary w-100" @click="step++" @if(!$allOk) disabled @endif>
                Continuar <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>

        {{-- STEP 1: Banco de Dados --}}
        <div x-show="step === 1">
            <h5 class="fw-bold mb-3"><i class="bi bi-database me-2"></i>Configuração do Banco de Dados</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-8"><label class="form-label fw-semibold">Host *</label><input type="text" class="form-control" x-model="form.db_host" placeholder="localhost" autocomplete="off" name="hp_dbhost"></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Porta *</label><input type="number" class="form-control" x-model="form.db_port" value="3306" autocomplete="off" name="hp_dbport"></div>
                <div class="col-12"><label class="form-label fw-semibold">Nome do Banco *</label><input type="text" class="form-control" x-model="form.db_database" placeholder="hostpanel" autocomplete="off" name="hp_dbname"></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Usuário *</label><input type="text" class="form-control" x-model="form.db_username" placeholder="root" autocomplete="off" name="hp_dbuser"></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Senha do Banco *</label><input type="text" class="form-control" x-model="form.db_password" placeholder="Senha do banco de dados" autocomplete="off" name="hp_dbpass" style="-webkit-text-security: disc;"></div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary" @click="step--">← Voltar</button>
                <button class="btn btn-primary flex-grow-1" @click="nextStep">Continuar <i class="bi bi-arrow-right ms-1"></i></button>
            </div>
        </div>

        {{-- STEP 2: Configurações do Sistema --}}
        <div x-show="step === 2">
            <h5 class="fw-bold mb-3"><i class="bi bi-gear me-2"></i>Configurações do Sistema</h5>
            <div class="row g-3 mb-4">
                <div class="col-12"><label class="form-label fw-semibold">Nome do Sistema *</label><input type="text" class="form-control" x-model="form.app_name" placeholder="HostPanel"></div>
                <div class="col-12"><label class="form-label fw-semibold">URL da Instalação *</label><input type="url" class="form-control" x-model="form.app_url" :placeholder="`https://seudominio.com`"></div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary" @click="step--">← Voltar</button>
                <button class="btn btn-primary flex-grow-1" @click="nextStep">Continuar <i class="bi bi-arrow-right ms-1"></i></button>
            </div>
        </div>

        {{-- STEP 3: Conta Administrador --}}
        <div x-show="step === 3">
            <h5 class="fw-bold mb-3"><i class="bi bi-shield-person me-2"></i>Conta Administrador</h5>
            <div class="row g-3 mb-4">
                <div class="col-12"><label class="form-label fw-semibold">Nome Completo *</label><input type="text" class="form-control" x-model="form.admin_name" placeholder="Administrador"></div>
                <div class="col-12"><label class="form-label fw-semibold">E-mail *</label><input type="email" class="form-control" x-model="form.admin_email" placeholder="admin@empresa.com"></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Senha *</label><input type="text" class="form-control" x-model="form.admin_pass" placeholder="Mín. 8 caracteres" autocomplete="new-password" name="hp_adminpass" style="-webkit-text-security: disc;"></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Confirmar Senha *</label><input type="text" class="form-control" x-model="form.admin_pass_confirm" placeholder="Repita a senha" autocomplete="new-password" name="hp_adminpass2" style="-webkit-text-security: disc;"></div>
            </div>
            <div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle me-1"></i>Anote suas credenciais em local seguro.</div>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary" @click="step--">← Voltar</button>
                <button class="btn btn-primary flex-grow-1" @click="nextStep">Continuar <i class="bi bi-arrow-right ms-1"></i></button>
            </div>
        </div>

        {{-- STEP 4: Instalação --}}
        <div x-show="step === 4" class="text-center py-3">
            <template x-if="!installing && !done && !error">
                <div>
                    <i class="bi bi-rocket-takeoff fs-1 text-primary d-block mb-3"></i>
                    <h5 class="fw-bold mb-2">Tudo pronto para instalar!</h5>
                    <p class="text-muted mb-4">Clique no botão abaixo para iniciar a instalação do HostPanel.<br>Este processo pode levar alguns minutos.</p>
                    <button class="btn btn-primary px-5" @click="install">
                        <i class="bi bi-rocket-takeoff me-2"></i>Instalar Agora
                    </button>
                </div>
            </template>
            <template x-if="installing">
                <div>
                    <div class="spinner-border text-primary mb-3" style="width:3rem;height:3rem"></div>
                    <h5 class="fw-semibold" x-text="installStep"></h5>
                    <p class="text-muted small">Aguarde, não feche esta janela...</p>
                    <div class="progress mt-3" style="height:8px"><div class="progress-bar bg-primary" :style="`width:${installProgress}%`" style="transition:.5s"></div></div>
                </div>
            </template>
            <template x-if="done">
                <div>
                    <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-3"></i>
                    <h5 class="fw-bold text-success">Instalação concluída!</h5>
                    <p class="text-muted mb-4">O HostPanel foi instalado com sucesso.</p>
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="/admin/entrar" class="btn btn-primary"><i class="bi bi-shield-person me-1"></i>Painel Admin</a>
                        <a href="/cliente/entrar" class="btn btn-outline-primary"><i class="bi bi-person me-1"></i>Área do Cliente</a>
                    </div>
                </div>
            </template>
            <template x-if="error">
                <div>
                    <i class="bi bi-x-circle-fill text-danger fs-1 d-block mb-3"></i>
                    <h5 class="fw-bold text-danger">Erro na instalação</h5>
                    <p class="text-muted mb-3" x-text="error"></p>
                    <button class="btn btn-outline-danger" @click="error=null;installing=false">Tentar Novamente</button>
                </div>
            </template>
        </div>
    </div>
</div>
</form>

@include('partials.hostpanel-ui-scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function installer() {
    return {
        step: 0,
        steps: ['Requisitos', 'Banco de Dados', 'Sistema', 'Administrador', 'Instalação'],
        installing: false, done: false, error: null,
        installStep: 'Preparando...', installProgress: 0,
        form: {
            db_host: 'localhost', db_port: 3306, db_database: '', db_username: '', db_password: '',
            app_name: 'HostPanel', app_url: window.location.origin,
            admin_name: '', admin_email: '', admin_pass: '', admin_pass_confirm: '',
        },

        async nextStep() {
            if (this.step === 3 && this.form.admin_pass !== this.form.admin_pass_confirm) {
                await HostPanel.alert({ text: 'As senhas nao conferem.', icon: 'error' });
                return;
            }
            this.step++;
        },

        async install() {
            // Verificar se senha do DB foi preenchida
            if (!this.form.db_password || this.form.db_password.trim() === '') {
                this.error = 'Preencha a senha do banco de dados.'; return;
            }
            console.log('Enviando dados:', JSON.stringify({...this.form, db_password: '***', admin_pass: '***'}));

            this.installing = true; this.installProgress = 10;
            this.installStep = 'Conectando ao banco de dados...';
            await this.sleep(800); this.installProgress = 25;
            this.installStep = 'Executando migrations...';

            try {
                const payload = JSON.parse(JSON.stringify(this.form));
                const res = await fetch('{{ route("install.run") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                this.installProgress = 60; this.installStep = 'Criando dados iniciais...';
                await this.sleep(1000); this.installProgress = 80;
                this.installStep = 'Finalizando...';

                const data = await res.json();
                this.installProgress = 100; await this.sleep(500);
                this.installing = false;

                if (data.success) { this.done = true; }
                else { this.error = data.message || 'Erro desconhecido durante a instalação.'; }
            } catch (e) {
                this.installing = false;
                this.error = 'Erro de comunicação com o servidor: ' + e.message;
            }
        },

        sleep(ms) { return new Promise(r => setTimeout(r, ms)); }
    }
}
</script>
</body>
</html>
