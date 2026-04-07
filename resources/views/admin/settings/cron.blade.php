@extends('admin.layouts.app')
@section('title', 'Cron Jobs')
@section('page-title', 'Cron Jobs — Agendamentos do Sistema')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Configurações</a></li>
    <li class="breadcrumb-item active">Cron Jobs</li>
@endsection

@section('content')
@php
    $mainCron = "* * * * * cd {$basePath} && {$phpBinary} artisan schedule:run >> /dev/null 2>&1";
@endphp

{{-- Entrada Principal --}}
<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
        <i class="bi bi-terminal-fill fs-5"></i>
        <div>
            <div class="fw-bold">Entrada Principal do Cron Job</div>
            <small class="opacity-75">Cadastre apenas ESTA linha no painel de cron da sua hospedagem. Ela executa todos os agendamentos abaixo.</small>
        </div>
    </div>
    <div class="card-body">

        {{-- Dados detectados --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="border rounded-3 p-3 bg-light">
                    <div class="text-muted small mb-1"><i class="bi bi-person-circle me-1"></i>Usuário Detectado</div>
                    <code class="fs-6 text-primary fw-bold">{{ $username ?? '(não detectado)' }}</code>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 bg-light">
                    <div class="text-muted small mb-1"><i class="bi bi-folder2 me-1"></i>Caminho da Instalação</div>
                    <code class="small text-break text-dark">{{ $basePath }}</code>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 bg-light">
                    <div class="text-muted small mb-1"><i class="bi bi-cpu me-1"></i>Binário PHP</div>
                    <code class="small text-dark">{{ $phpBinary }}</code>
                </div>
            </div>
        </div>

        {{-- Linha do cron --}}
        <label class="form-label fw-semibold mb-2">Linha para cadastrar no Cron Job:</label>
        <div class="input-group mb-2" id="mainCronGroup">
            <input type="text" class="form-control font-monospace" id="mainCronInput"
                   value="{{ $mainCron }}" readonly style="font-size:.82rem;background:#f8f9fa">
            <button class="btn btn-outline-primary" onclick="copyText('mainCronInput', this)" title="Copiar">
                <i class="bi bi-clipboard"></i> Copiar
            </button>
        </div>
        <div class="alert alert-warning py-2 px-3 small mb-0">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Frequência obrigatória:</strong> A expressão <code>* * * * *</code> (todo minuto) é necessária para que o Laravel Scheduler funcione corretamente. Os agendamentos individuais controlam a frequência real de cada tarefa.
        </div>
    </div>
</div>

{{-- Como cadastrar --}}
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-display me-2 text-primary"></i>cPanel — Cron Jobs</div>
            <div class="card-body small">
                <ol class="mb-0 ps-3">
                    <li class="mb-2">Acesse o <strong>cPanel</strong> da sua hospedagem.</li>
                    <li class="mb-2">Clique em <strong>"Cron Jobs"</strong> na seção Avançado.</li>
                    <li class="mb-2">Em <em>Common Settings</em>, selecione <strong>"Every Minute (* * * * *)"</strong>.</li>
                    <li class="mb-2">No campo <strong>Command</strong>, cole exatamente:</li>
                </ol>
                <div class="input-group mt-2">
                    <input type="text" class="form-control font-monospace" id="cpanelCronInput"
                           value="cd {{ $basePath }} && {{ $phpBinary }} artisan schedule:run >> /dev/null 2>&1"
                           readonly style="font-size:.75rem;background:#f8f9fa">
                    <button class="btn btn-outline-primary btn-sm" onclick="copyText('cpanelCronInput', this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <div class="mt-2 text-muted">5. Clique em <strong>"Add New Cron Job"</strong>.</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-grid me-2 text-success"></i>AAPanel / BT Panel</div>
            <div class="card-body small">
                <ol class="mb-0 ps-3">
                    <li class="mb-2">Acesse o <strong>AAPanel</strong> da sua hospedagem.</li>
                    <li class="mb-2">Clique em <strong>"Cron"</strong> no menu lateral.</li>
                    <li class="mb-2">Clique em <strong>"Add Task"</strong>.</li>
                    <li class="mb-2">Configure:
                        <ul class="mt-1">
                            <li><strong>Task Type:</strong> Shell Script</li>
                            <li><strong>Cycle:</strong> N Minutes → <code>1</code></li>
                            <li><strong>Script content:</strong></li>
                        </ul>
                    </li>
                </ol>
                <div class="input-group mt-2">
                    <input type="text" class="form-control font-monospace" id="aapanelCronInput"
                           value="cd {{ $basePath }} && {{ $phpBinary }} artisan schedule:run >> /dev/null 2>&1"
                           readonly style="font-size:.75rem;background:#f8f9fa">
                    <button class="btn btn-outline-success btn-sm" onclick="copyText('aapanelCronInput', this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <div class="mt-2 text-muted">5. Clique em <strong>"Add"</strong> para salvar.</div>
            </div>
        </div>
    </div>
</div>

{{-- Tabela de Tarefas Agendadas --}}
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i>Lista Completa de Tarefas Agendadas</span>
        <span class="badge bg-primary">{{ count($tasks) }} tarefas</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:220px">Tarefa</th>
                    <th>Descrição</th>
                    <th style="width:180px">Frequência</th>
                    <th style="width:140px">Expressão Cron</th>
                    <th style="width:48px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $task)
                <tr>
                    <td>
                        <div class="fw-semibold small">{{ $task['name'] }}</div>
                        <div class="text-muted" style="font-size:.7rem">{{ $task['job'] }}</div>
                    </td>
                    <td class="small text-muted">{{ $task['description'] }}</td>
                    <td>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small fw-normal">
                            <i class="bi bi-clock me-1"></i>{{ $task['schedule'] }}
                        </span>
                    </td>
                    <td><code class="small">{{ $task['cron_expr'] }}</code></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                                onclick="copyText(null, this, '{{ $task['cron_expr'] }}')"
                                title="Copiar expressão">
                            <i class="bi bi-clipboard" style="font-size:.8rem"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        <div class="alert alert-info py-2 px-3 small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Importante:</strong> Estas tarefas são gerenciadas internamente pelo Laravel Scheduler. Você <strong>não precisa</strong> cadastrar cada uma individualmente — apenas a entrada principal acima é suficiente para executar todas elas nos horários corretos.
        </div>
    </div>
</div>

{{-- Verificação de Ambiente --}}
<div class="card mt-4">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-patch-check me-2 text-success"></i>Verificação do Ambiente</div>
    <div class="card-body">
        <div class="row g-3">
            @php
                $checks = [
                    ['label' => 'PHP Binary executável',    'ok' => is_executable($phpBinary),              'fix' => 'Verifique o caminho correto do PHP com: which php'],
                    ['label' => 'Diretório base acessível', 'ok' => is_readable($basePath),                 'fix' => 'Verifique as permissões do diretório da instalação.'],
                    ['label' => 'storage/ gravável',        'ok' => is_writable(storage_path()),            'fix' => 'Execute: chmod -R 775 storage/ && chown -R USER:GROUP storage/'],
                    ['label' => 'bootstrap/cache/ gravável','ok' => is_writable(base_path('bootstrap/cache')), 'fix' => 'Execute: chmod -R 775 bootstrap/cache/'],
                    ['label' => '.env existe',              'ok' => file_exists(base_path('.env')),         'fix' => 'Crie o arquivo .env com as configurações do sistema.'],
                    ['label' => 'Queue driver configurado', 'ok' => config('queue.default') !== 'sync',     'fix' => 'Configure QUEUE_CONNECTION=database ou redis no .env para jobs assíncronos.'],
                ];
            @endphp
            @foreach($checks as $c)
            <div class="col-md-6">
                <div class="d-flex align-items-start gap-2 p-2 border rounded-2 {{ $c['ok'] ? 'border-success bg-success bg-opacity-5' : 'border-warning bg-warning bg-opacity-5' }}">
                    <i class="bi {{ $c['ok'] ? 'bi-check-circle-fill text-success' : 'bi-exclamation-circle-fill text-warning' }} mt-0.5 flex-shrink-0"></i>
                    <div>
                        <div class="small fw-semibold {{ $c['ok'] ? 'text-success' : 'text-warning' }}">{{ $c['label'] }}</div>
                        @if(!$c['ok'])
                        <div class="text-muted" style="font-size:.75rem">{{ $c['fix'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4">
            <label class="form-label fw-semibold small">Testar agendador manualmente (execute no terminal do servidor):</label>
            <div class="input-group">
                <input type="text" class="form-control font-monospace" id="testCronInput"
                       value="cd {{ $basePath }} && {{ $phpBinary }} artisan schedule:run --verbose"
                       readonly style="font-size:.82rem;background:#f8f9fa">
                <button class="btn btn-outline-secondary" onclick="copyText('testCronInput', this)">
                    <i class="bi bi-clipboard"></i> Copiar
                </button>
            </div>
        </div>

        <div class="mt-3">
            <label class="form-label fw-semibold small">Listar todas as tarefas agendadas:</label>
            <div class="input-group">
                <input type="text" class="form-control font-monospace" id="listCronInput"
                       value="cd {{ $basePath }} && {{ $phpBinary }} artisan schedule:list"
                       readonly style="font-size:.82rem;background:#f8f9fa">
                <button class="btn btn-outline-secondary" onclick="copyText('listCronInput', this)">
                    <i class="bi bi-clipboard"></i> Copiar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Queue Workers --}}
<div class="card mt-4">
    <div class="card-header bg-white d-flex align-items-center gap-2">
        <i class="bi bi-stack text-warning fs-5"></i>
        <div>
            <div class="fw-semibold">Queue Workers — Filas de Email e WhatsApp</div>
            <small class="text-muted">Necessário para processamento assíncrono com rate limiting anti-spam e anti-banimento</small>
        </div>
    </div>
    <div class="card-body">

        <div class="alert alert-warning py-2 px-3 small mb-4">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Importante:</strong> Os workers abaixo devem ficar rodando continuamente. Use <strong>Supervisor</strong> no servidor para mantê-los ativos após reinicializações ou falhas.
        </div>

        <div class="row g-4 mb-4">
            {{-- Worker Email --}}
            <div class="col-md-6">
                <div class="border rounded-3 p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-envelope-fill text-primary fs-5"></i>
                        <div>
                            <div class="fw-semibold">Fila: <code>email</code></div>
                            <small class="text-muted">Máx. 60 emails/minuto — backoff 60s→300s→600s</small>
                        </div>
                    </div>
                    <label class="form-label small fw-semibold">Iniciar worker de email:</label>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control font-monospace" id="emailWorkerInput"
                               value="{{ $phpBinary }} {{ $basePath }}/artisan queue:work --queue=email --tries=3 --backoff=60,300,600 --sleep=3 --timeout=30"
                               readonly style="font-size:.75rem;background:#f8f9fa">
                        <button class="btn btn-outline-primary btn-sm" onclick="copyText('emailWorkerInput', this)">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <div class="text-muted" style="font-size:.72rem">
                        <i class="bi bi-info-circle me-1"></i>3 tentativas, delay progressivo, timeout 30s por email.
                    </div>
                </div>
            </div>

            {{-- Worker WhatsApp --}}
            <div class="col-md-6">
                <div class="border rounded-3 p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-whatsapp text-success fs-5"></i>
                        <div>
                            <div class="fw-semibold">Fila: <code>whatsapp</code></div>
                            <small class="text-muted">Máx. 20 msgs/hora — delay 5-15s entre envios</small>
                        </div>
                    </div>
                    <label class="form-label small fw-semibold">Iniciar worker de WhatsApp:</label>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control font-monospace" id="waWorkerInput"
                               value="{{ $phpBinary }} {{ $basePath }}/artisan queue:work --queue=whatsapp --tries=3 --backoff=120,600,1800 --sleep=10 --timeout=20"
                               readonly style="font-size:.75rem;background:#f8f9fa">
                        <button class="btn btn-outline-success btn-sm" onclick="copyText('waWorkerInput', this)">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <div class="text-muted" style="font-size:.72rem">
                        <i class="bi bi-info-circle me-1"></i>3 tentativas, backoff 2min→10min→30min. O job aplica delay humanizado adicional.
                    </div>
                </div>
            </div>
        </div>

        {{-- Worker unificado --}}
        <div class="mb-4">
            <label class="form-label fw-semibold small">Worker unificado (todas as filas — para ambientes simples):</label>
            <div class="input-group">
                <input type="text" class="form-control font-monospace" id="allWorkerInput"
                       value="{{ $phpBinary }} {{ $basePath }}/artisan queue:work --queue=email,whatsapp,default --tries=3 --sleep=3 --timeout=60"
                       readonly style="font-size:.82rem;background:#f8f9fa">
                <button class="btn btn-outline-secondary" onclick="copyText('allWorkerInput', this)">
                    <i class="bi bi-clipboard"></i> Copiar
                </button>
            </div>
        </div>

        {{-- Supervisor config --}}
        <div>
            <label class="form-label fw-semibold small d-flex align-items-center gap-2">
                <i class="bi bi-file-code text-secondary"></i>
                Configuração do Supervisor (<code>/etc/supervisor/conf.d/hostpanel-worker.conf</code>):
                <button class="btn btn-outline-secondary btn-sm py-0 px-2 ms-1" onclick="copyText('supervisorInput', this)">
                    <i class="bi bi-clipboard"></i> Copiar
                </button>
            </label>
            <textarea class="form-control font-monospace" id="supervisorInput" rows="28" readonly
                      style="font-size:.78rem;background:#1e1e2e;color:#cdd6f4;border-color:#313244;resize:none">[program:hostpanel-email]
command={{ $phpBinary }} {{ $basePath }}/artisan queue:work --queue=email --tries=3 --backoff=60,300,600 --sleep=3 --timeout=30 --max-jobs=500
directory={{ $basePath }}
user={{ $username ?? 'www-data' }}
numprocs=2
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stderr_logfile={{ $basePath }}/storage/logs/worker-email.log
stderr_logfile_maxbytes=5MB
stdout_logfile={{ $basePath }}/storage/logs/worker-email-out.log
stdout_logfile_maxbytes=5MB

[program:hostpanel-whatsapp]
command={{ $phpBinary }} {{ $basePath }}/artisan queue:work --queue=whatsapp --tries=3 --backoff=120,600,1800 --sleep=10 --timeout=20 --max-jobs=200
directory={{ $basePath }}
user={{ $username ?? 'www-data' }}
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stderr_logfile={{ $basePath }}/storage/logs/worker-whatsapp.log
stderr_logfile_maxbytes=5MB
stdout_logfile={{ $basePath }}/storage/logs/worker-whatsapp-out.log
stdout_logfile_maxbytes=5MB</textarea>
            <div class="text-muted mt-1" style="font-size:.75rem">
                Após criar o arquivo: <code>sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start all</code>
            </div>
        </div>

        {{-- Configuração .env --}}
        <div class="mt-4">
            <label class="form-label fw-semibold small"><i class="bi bi-gear me-1"></i>Configuração mínima no <code>.env</code>:</label>
            <div class="input-group">
                <textarea class="form-control font-monospace" id="envQueueInput" rows="4" readonly
                          style="font-size:.78rem;background:#f8f9fa;resize:none">QUEUE_CONNECTION=database
# ou QUEUE_CONNECTION=redis (recomendado para produção)

# Para Redis:
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379</textarea>
                <button class="btn btn-outline-secondary" onclick="copyText('envQueueInput', this)" style="height:auto">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
            <div class="text-muted mt-1" style="font-size:.75rem">
                <i class="bi bi-info-circle me-1"></i>Com <code>database</code>, execute: <code>{{ $phpBinary }} {{ $basePath }}/artisan queue:table && {{ $phpBinary }} {{ $basePath }}/artisan migrate</code>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function copyText(inputId, btn, directText = null) {
    const text = directText ?? document.getElementById(inputId).value;
    navigator.clipboard.writeText(text).then(() => {
        const icon = btn.querySelector('i');
        if (icon) { icon.className = 'bi bi-check-lg text-success'; }
        else       { btn.innerHTML = '<i class="bi bi-check-lg text-success"></i> Copiado!'; }
        setTimeout(() => {
            if (icon) { icon.className = 'bi bi-clipboard'; }
            else      { btn.innerHTML = '<i class="bi bi-clipboard"></i> Copiar'; }
        }, 2000);
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    });
}
</script>
@endpush
