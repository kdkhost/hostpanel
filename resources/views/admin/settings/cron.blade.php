@extends('admin.layouts.app')
@section('title', 'Cron Jobs')
@section('page-title', 'Cron Jobs — Infraestrutura e Automação')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Configurações</a></li>
    <li class="breadcrumb-item active">Cron Jobs</li>
@endsection

@section('content')
<div x-data="cronDashboard" class="cron-dashboard">
    <!-- Header de Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-dark text-white border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="status-indicator" :class="cronStatus === 'online' ? 'status-online' : 'status-offline'">
                            <i class="bi bi-cpu-fill fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">Scheduler HostPanel</h4>
                            <p class="mb-0 opacity-75 small text-uppercase ls-1">
                                <span x-text="cronStatus === 'online' ? 'Sistema Operacional' : 'Sistema Inativo'"></span>
                                <span class="mx-2">•</span>
                                Ciclo: <span x-text="lastHeartbeatHuman"></span>
                            </p>
                        </div>
                    </div>
                    <div class="d-none d-md-flex align-items-center gap-2 bg-white bg-opacity-10 px-3 py-2 rounded-pill border border-white border-opacity-10">
                        <i class="bi bi-clock-history"></i>
                        <span class="small fw-semibold">Batimento a cada 60s</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navegação por Abas -->
    <ul class="nav nav-tabs border-bottom mb-4 gap-2" role="tablist" style="border-width: 2px !important;">
        <li class="nav-item">
            <button class="nav-link px-4 py-2 fw-semibold" :class="activeTab === 'monitor' ? 'active border-primary border-bottom-0' : 'text-muted'" @click="activeTab = 'monitor'">
                <i class="bi bi-activity me-2"></i>Monitoramento
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-4 py-2 fw-semibold" :class="activeTab === 'setup' ? 'active border-primary border-bottom-0' : 'text-muted'" @click="activeTab = 'setup'">
                <i class="bi bi-gear-wide-connected me-2"></i>Configuração
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-4 py-2 fw-semibold" :class="activeTab === 'queues' ? 'active border-primary border-bottom-0' : 'text-muted'" @click="activeTab = 'queues'">
                <i class="bi bi-layers-half me-2"></i>Filas (Workers)
            </button>
        </li>
    </ul>

    <!-- Aba de Monitoramento -->
    <div x-show="activeTab === 'monitor'" x-transition:enter="fade-in">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-task me-2 text-primary"></i>Tarefas Agendadas</h6>
                <button class="btn btn-sm btn-light border" @click="window.location.reload()"><i class="bi bi-arrow-clockwise"></i> Atualizar</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="small text-uppercase fw-bold text-muted">
                            <th class="ps-4" style="width: 250px;">Tarefa</th>
                            <th>Frequência Real</th>
                            <th>Última Execução</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="task in tasks" :key="task.key">
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold" x-text="task.name"></div>
                                    <div class="small text-muted text-truncate" style="max-width: 200px;" x-text="task.description"></div>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-normal">
                                        <i class="bi bi-calendar-event me-1"></i> <span x-text="task.schedule"></span>
                                    </span>
                                </td>
                                <td class="small text-muted" x-text="task.last_run"></td>
                                <td class="text-center">
                                    <i class="bi bi-check-circle-fill text-success" x-show="task.status === 'ok'"></i>
                                    <i class="bi bi-clock-history text-warning" x-show="task.status === 'pending'"></i>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary px-3 rounded-pill" 
                                            :disabled="runningTask === task.key"
                                            @click="runTask(task.key)">
                                        <span x-show="runningTask !== task.key"><i class="bi bi-play-fill"></i> Iniciar</span>
                                        <span x-show="runningTask === task.key" class="spinner-border spinner-border-sm"></span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Verificação de Ambiente Reestilizada -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-shield-check me-2 text-success"></i>Diagnóstico de Infraestrutura</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <template x-for="check in environmentChecks" :key="check.label">
                        <div class="col-md-6 col-lg-4">
                            <div class="p-3 border rounded-3 d-flex align-items-center gap-3 h-100" 
                                 :class="check.ok ? 'bg-light border-light' : 'bg-danger bg-opacity-5 border-danger border-opacity-25'">
                                <div class="icon-circle shadow-sm" :class="check.ok ? 'bg-success text-white' : 'bg-danger text-white'">
                                    <i class="bi" :class="check.ok ? 'bi-check2' : 'bi-x-lg'"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="small fw-bold text-dark text-truncate" x-text="check.label"></div>
                                    <div class="text-muted" style="font-size: .7rem;" x-text="check.ok ? 'Em operação' : check.fix"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Aba de Setup -->
    <div x-show="activeTab === 'setup'" x-transition:enter="fade-in" style="display:none;">
        <div class="card border-0 shadow-sm mb-4 border-start border-primary border-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-terminal fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">Instrução Universal (Heartbeat)</h6>
                        <small class="text-muted">Cadastre esta linha no cPanel, SSH ou Painel da sua Hospedagem</small>
                    </div>
                </div>
                <div class="bg-dark p-3 rounded-2 position-relative mb-2">
                    <code class="text-success font-monospace small" id="cronCommandText">* * * * * cd {{ $basePath }} && {{ $phpBinary }} artisan schedule:run >> /dev/null 2>&1</code>
                    <button class="btn btn-sm btn-link text-white position-absolute top-50 end-0 translate-middle-y me-2" @click="copyToClipboard('cronCommandText')">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <div class="alert alert-info py-2 px-3 small border-0 mt-3 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>Você só precisa cadastrar **esta** linha. Ela gerencia todas as outras automaticamente.</span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2"><i class="bi bi-hdd-network text-primary"></i> Informações do Servidor</h6>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between p-2"><span>Diretório Raiz</span><code class="text-dark">{{ $basePath }}</code></li>
                            <li class="list-group-item d-flex justify-content-between p-2"><span>Binário PHP</span><code class="text-dark">{{ $phpBinary }}</code></li>
                            <li class="list-group-item d-flex justify-content-between p-2"><span>Usuário do Sistema</span><code class="text-primary">{{ $username }}</code></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-white"><i class="bi bi-shield-lock"></i> Verificação de Permissões</h6>
                        <div class="p-3 bg-white bg-opacity-10 rounded-3 border border-white border-opacity-10">
                            <p class="small mb-2">Se o Cron não rodar, talvez precise corrigir as permissões via SSH:</p>
                            <code class="text-white-50 d-block mb-1" style="font-size: .75rem;">chown -R {{ $username }}:{{ $username }} {{ $basePath }}</code>
                            <code class="text-white-50 d-block" style="font-size: .75rem;">chmod -R 775 storage bootstrap/cache</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Aba de Filas -->
    <div x-show="activeTab === 'queues'" x-transition:enter="fade-in" style="display:none;">
        <div class="alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
            <div>
                <strong class="d-block">O uso de Workers é opcional mas RECOMENDADO.</strong>
                <span class="small opacity-75">Filas permitem que emails e mensagens de WhatsApp sejam enviados instantaneamente em segundo plano.</span>
            </div>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <i class="bi bi-envelope-check text-primary fs-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Worker de Email</h6>
                                <span class="small text-muted">Acelera o envio de faturas e notificações</span>
                            </div>
                        </div>
                        <div class="bg-light p-3 rounded-2 font-monospace small mb-3 text-break border">
                            {{ $phpBinary }} {{ $basePath }}/artisan queue:work --queue=email --tries=3
                        </div>
                        <div class="d-grid"><button class="btn btn-outline-primary btn-sm" @click="copyTextAndToast('{{ $phpBinary }} {{ $basePath }}/artisan queue:work --queue=email --tries=3')">Copiar Comando</button></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <i class="bi bi-whatsapp text-success fs-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Worker de WhatsApp</h6>
                                <span class="small text-muted">Evita banimentos com envios cadenciados</span>
                            </div>
                        </div>
                        <div class="bg-light p-3 rounded-2 font-monospace small mb-3 text-break border">
                            {{ $phpBinary }} {{ $basePath }}/artisan queue:work --queue=whatsapp --tries=3
                        </div>
                        <div class="d-grid"><button class="btn btn-outline-success btn-sm" @click="copyTextAndToast('{{ $phpBinary }} {{ $basePath }}/artisan queue:work --queue=whatsapp --tries=3')">Copiar Comando</button></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white py-3">Configuração Supervisor (Opcional - Servidores Linux)</div>
            <div class="card-body p-0">
                <div class="bg-dark p-4 font-monospace small" style="color: #61afef; border-radius: 0 0 12px 12px;">
                    <pre class="mb-0" style="color: #d19a66;">[program:hostpanel-worker]
command={{ $phpBinary }} {{ $basePath }}/artisan queue:work --sleep=3 --tries=3 --max-jobs=1000
directory={{ $basePath }}
autostart=true
autorestart=true
user={{ $username }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cron-dashboard .status-indicator {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}
.status-online {
    background: #10b981;
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
    animation: pulse-online 2s infinite;
}
.status-offline {
    background: #f43f5e;
    box-shadow: 0 0 20px rgba(244, 63, 94, 0.4);
}
.icon-circle {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.nav-tabs .nav-link.active {
    background: transparent !important;
    border: none !important;
    border-bottom: 3px solid var(--bs-primary) !important;
    color: var(--bs-primary) !important;
}
.ls-1 { letter-spacing: 1px; }

@keyframes pulse-online {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
.fade-in { animation: fadeIn 0.3s ease-in-out; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('cronDashboard', () => ({
        activeTab: 'monitor',
        cronStatus: '{{ $cronStatus }}',
        lastHeartbeat: {{ $lastHeartbeat ?? 0 }},
        lastHeartbeatHuman: '{{ $lastHeartbeat ? \Carbon\Carbon::createFromTimestamp($lastHeartbeat)->diffForHumans() : "Inativo" }}',
        runningTask: null,
        tasks: @json($tasks),
        environmentChecks: [
            { label: 'Binário PHP', ok: {{ is_executable($phpBinary) ? 'true' : 'false' }}, fix: 'Caminho incorreto.' },
            { label: 'Diretório Base', ok: {{ is_readable($basePath) ? 'true' : 'false' }}, fix: 'Sem permissão leitura.' },
            { label: 'Pasta Storage', ok: {{ is_writable(storage_path()) ? 'true' : 'false' }}, fix: 'Sem permissão escrita.' },
            { label: 'Cache Bootstrap', ok: {{ is_writable(base_path('bootstrap/cache')) ? 'true' : 'false' }}, fix: 'Precisa chmod 775.' },
            { label: 'Arquivo .env', ok: {{ file_exists(base_path('.env')) ? 'true' : 'false' }}, fix: 'Arquivo ausente.' },
            { label: 'Filas Configuradas', ok: {{ config('queue.default') !== 'sync' ? 'true' : 'false' }}, fix: 'Use database/redis.' }
        ],

        async runTask(key) {
            this.runningTask = key;
            try {
                const response = await HostPanel.fetch('{{ route("admin.settings.cron.run") }}', {
                    method: 'POST',
                    body: JSON.stringify({ task: key })
                });

                if (response.ok) {
                    HostPanel.toast('Tarefa executada!', 'success');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    HostPanel.toast(response.message || 'Erro ao executar', 'danger');
                }
            } catch (e) {
                HostPanel.toast('Erro na comunicacao', 'danger');
            } finally {
                this.runningTask = null;
            }
        },

        copyToClipboard(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text);
            HostPanel.toast('Comando copiado!', 'success');
        },

        copyTextAndToast(text) {
            navigator.clipboard.writeText(text);
            HostPanel.toast('Comando copiado!', 'success');
        }
    }));
});
</script>
@endpush
