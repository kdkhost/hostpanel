@extends('admin.layouts.app')

@section('title', 'Configurações de Cron')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Configurações de Cron
                    </h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-{{ $cronStatus === 'online' ? 'success' : 'danger' }} me-2">
                            {{ $cronStatus === 'online' ? 'Online' : 'Offline' }}
                        </span>
                        @if($lastHeartbeat)
                            <small class="text-muted">
                                Último heartbeat: {{ \Carbon\Carbon::createFromTimestamp($lastHeartbeat)->diffForHumans() }}
                            </small>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <!-- Instruções de Instalação -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Configuração do Cron</h6>
                        <p class="mb-2">Para que as tarefas automáticas funcionem, adicione esta linha ao crontab do seu servidor:</p>
                        <div class="bg-dark text-light p-2 rounded font-monospace">
                            * * * * * {{ $phpBinary }} {{ $basePath }}/artisan cron:master >> /dev/null 2>&1
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <strong>cPanel:</strong> Painel de Controle → Cron Jobs → Adicionar nova tarefa<br>
                            <strong>SSH:</strong> <code>crontab -e</code> (usuário: {{ $username }})
                        </small>
                    </div>

                    <!-- Status das Tarefas -->
                    <div class="row">
                        @foreach($cronTasks as $taskKey => $task)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-{{ $task['enabled'] ? 'success' : 'secondary' }}">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">{{ $task['name'] }}</h6>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input cron-toggle" 
                                               type="checkbox" 
                                               data-task="{{ $taskKey }}"
                                               {{ $task['enabled'] ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-2">{{ $task['description'] }}</p>
                                    
                                    <div class="mb-2">
                                        <label class="form-label small">Agendamento (Cron):</label>
                                        <input type="text" 
                                               class="form-control form-control-sm cron-schedule" 
                                               data-task="{{ $taskKey }}"
                                               value="{{ $task['schedule'] }}"
                                               placeholder="0 8 * * *">
                                    </div>

                                    @if($task['last_run'])
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">Última execução:</small>
                                        <small>{{ \Carbon\Carbon::createFromTimestamp($task['last_run'])->format('d/m/Y H:i') }}</small>
                                    </div>
                                    @endif

                                    @if($task['last_status'])
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">Status:</small>
                                        <span class="badge bg-{{ $task['last_status'] === 'success' ? 'success' : 'danger' }}">
                                            {{ $task['last_status'] === 'success' ? 'Sucesso' : 'Erro' }}
                                        </span>
                                    </div>
                                    @endif

                                    @if($task['last_duration'])
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">Duração:</small>
                                        <small>{{ $task['last_duration'] }}ms</small>
                                    </div>
                                    @endif

                                    @if($task['last_error'])
                                    <div class="alert alert-danger alert-sm p-2 mb-2">
                                        <small>{{ $task['last_error'] }}</small>
                                    </div>
                                    @endif
                                </div>
                                <div class="card-footer">
                                    <button class="btn btn-sm btn-outline-primary run-task" data-task="{{ $taskKey }}">
                                        <i class="fas fa-play me-1"></i>Executar Agora
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Referência de Cron -->
                    <div class="mt-4">
                        <h6>Referência de Formato Cron</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Campo</th>
                                        <th>Valores</th>
                                        <th>Exemplo</th>
                                        <th>Descrição</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Minuto</td>
                                        <td>0-59</td>
                                        <td>*/5</td>
                                        <td>A cada 5 minutos</td>
                                    </tr>
                                    <tr>
                                        <td>Hora</td>
                                        <td>0-23</td>
                                        <td>8</td>
                                        <td>Às 8h</td>
                                    </tr>
                                    <tr>
                                        <td>Dia</td>
                                        <td>1-31</td>
                                        <td>*</td>
                                        <td>Todos os dias</td>
                                    </tr>
                                    <tr>
                                        <td>Mês</td>
                                        <td>1-12</td>
                                        <td>*</td>
                                        <td>Todos os meses</td>
                                    </tr>
                                    <tr>
                                        <td>Dia da Semana</td>
                                        <td>0-7 (0=Dom)</td>
                                        <td>*</td>
                                        <td>Todos os dias da semana</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle de ativação/desativação
    $('.cron-toggle').change(function() {
        const task = $(this).data('task');
        const enabled = $(this).is(':checked');
        
        updateCronTask(task, { enabled: enabled });
    });

    // Alteração de agendamento
    $('.cron-schedule').on('blur', function() {
        const task = $(this).data('task');
        const schedule = $(this).val();
        
        if (schedule) {
            updateCronTask(task, { schedule: schedule });
        }
    });

    // Executar tarefa manualmente
    $('.run-task').click(function() {
        const task = $(this).data('task');
        const btn = $(this);
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Executando...');
        
        $.post('/admin/settings/cron/run', { task: task })
            .done(function(response) {
                toastr.success(response.message);
                setTimeout(() => location.reload(), 2000);
            })
            .fail(function(xhr) {
                const error = xhr.responseJSON?.error || 'Erro ao executar tarefa';
                toastr.error(error);
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-play me-1"></i>Executar Agora');
            });
    });

    function updateCronTask(task, data) {
        $.post('/admin/settings/cron/update', { task: task, ...data })
            .done(function(response) {
                toastr.success(response.message);
            })
            .fail(function(xhr) {
                const error = xhr.responseJSON?.error || 'Erro ao atualizar configuração';
                toastr.error(error);
            });
    }
});
</script>
@endpush
@endsection