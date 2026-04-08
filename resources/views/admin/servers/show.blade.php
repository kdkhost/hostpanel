@extends('admin.layouts.app')
@section('title', $server->name . ' — ' . $server->hostname)
@section('page-title', $server->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.servers.index') }}">Servidores</a></li>
    <li class="breadcrumb-item active">{{ $server->hostname }}</li>
@endsection

@section('content')
<div x-data="serverShow()" x-init="init()" class="row g-4">

    {{-- Coluna Principal --}}
    <div class="col-lg-8">

        {{-- Cabeçalho de Status --}}
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative">
                        <span x-show="health?.network_status === 'online'"
                              style="width:14px;height:14px;border-radius:50%;background:#22c55e;display:inline-block;box-shadow:0 0 0 4px rgba(34,197,94,.2)"></span>
                        <span x-show="health?.network_status === 'degraded'"
                              style="width:14px;height:14px;border-radius:50%;background:#f59e0b;display:inline-block;box-shadow:0 0 0 4px rgba(245,158,11,.2)"></span>
                        <span x-show="health?.network_status === 'unknown'"
                              style="width:14px;height:14px;border-radius:50%;background:#94a3b8;display:inline-block;box-shadow:0 0 0 4px rgba(148,163,184,.2)"></span>
                        <span x-show="!health || health?.network_status === 'offline'"
                              style="width:14px;height:14px;border-radius:50%;background:#ef4444;display:inline-block;box-shadow:0 0 0 4px rgba(239,68,68,.2)"></span>
                    </div>
                    <div>
                        <div class="fw-bold">{{ $server->name ?: $server->hostname }}</div>
                        <div class="text-muted small">{{ $server->ip_address }} &mdash; Módulo: <strong>{{ strtoupper($server->module ?? $server->type) }}</strong></div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check form-switch mb-0" title="Ativar/Desativar Servidor">
                        <input class="form-check-input" type="checkbox" :checked="!!{{ $server->active ? 'true' : 'false' }}" @change="toggleActive()">
                    </div>
                    <span class="badge rounded-pill small"
                          :class="health?.network_status === 'online' ? 'bg-success' : (health?.network_status === 'degraded' ? 'bg-warning text-dark' : (health?.network_status === 'unknown' ? 'bg-secondary' : 'bg-danger'))"
                          x-text="{'online':'Operacional','degraded':'Degradado','offline':'Offline','unknown':'Pendente'}[health?.network_status] ?? 'Verificando...'"></span>
                    <small class="text-muted" x-text="health?.checked_at ? 'Atualizado ' + health.checked_at : ''"></small>
                    <button class="btn btn-sm btn-outline-primary" @click="healthCheck()" :disabled="checkingHealth">
                        <span x-show="checkingHealth" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-arrow-repeat" x-show="!checkingHealth"></i>
                        <span x-text="checkingHealth ? 'Verificando...' : 'Atualizar'"></span>
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="alert alert-warning py-2 px-3 small mb-4" x-show="health?.network_status === 'unknown' || health?.is_stale || !health?.last_checked_at">
                    <div class="fw-semibold mb-1">O monitoramento automatico deste servidor esta pendente ou atrasado.</div>
                    <div>
                        Configure o cron principal do sistema em <a href="{{ route('admin.settings.cron') }}" class="alert-link">Configuracoes > Cron Jobs</a>
                        e rode um health check manual apos salvar os dados do servidor.
                    </div>
                </div>

                {{-- Métricas de Carga --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <span class="small fw-semibold text-muted"><i class="bi bi-cpu me-1"></i>CPU</span>
                                <span class="fw-bold" :class="(health?.cpu||0)>90?'text-danger':(health?.cpu||0)>70?'text-warning':'text-success'"
                                      x-text="health?.cpu != null ? health.cpu + '%' : '—'"></span>
                            </div>
                            <div class="progress" style="height:8px">
                                <div class="progress-bar transition-all"
                                     :class="(health?.cpu||0)>90?'bg-danger':(health?.cpu||0)>70?'bg-warning':'bg-success'"
                                     :style="'width:' + (health?.cpu || 0) + '%'"></div>
                            </div>
                            <div class="text-muted mt-1" style="font-size:.72rem">
                                Load: <span x-text="[health?.load_avg_1, health?.load_avg_5, health?.load_avg_15].filter(Boolean).join(' / ') || '—'"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <span class="small fw-semibold text-muted"><i class="bi bi-memory me-1"></i>RAM</span>
                                <span class="fw-bold" :class="(health?.ram||0)>90?'text-danger':(health?.ram||0)>70?'text-warning':'text-success'"
                                      x-text="health?.ram != null ? health.ram + '%' : '—'"></span>
                            </div>
                            <div class="progress" style="height:8px">
                                <div class="progress-bar transition-all"
                                     :class="(health?.ram||0)>90?'bg-danger':(health?.ram||0)>70?'bg-warning':'bg-success'"
                                     :style="'width:' + (health?.ram || 0) + '%'"></div>
                            </div>
                            <div class="text-muted mt-1" style="font-size:.72rem">Uptime: <span x-text="health?.uptime || '—'"></span></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <span class="small fw-semibold text-muted"><i class="bi bi-hdd me-1"></i>Disco</span>
                                <span class="fw-bold" :class="(health?.disk||0)>90?'text-danger':(health?.disk||0)>70?'text-warning':'text-success'"
                                      x-text="health?.disk != null ? health.disk + '%' : '—'"></span>
                            </div>
                            <div class="progress" style="height:8px">
                                <div class="progress-bar transition-all"
                                     :class="(health?.disk||0)>90?'bg-danger':(health?.disk||0)>70?'bg-warning':'bg-success'"
                                     :style="'width:' + (health?.disk || 0) + '%'"></div>
                            </div>
                            <div class="text-muted mt-1" style="font-size:.72rem">
                                Contas: <span x-text="health?.health?.account_count ?? '—'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status da Rede --}}
                <div class="border rounded-3 p-3">
                    <div class="fw-semibold small mb-3"><i class="bi bi-diagram-3 me-2 text-primary"></i>Status da Rede</div>
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <div class="text-muted small mb-1">Latência</div>
                                <div class="fw-bold fs-5"
                                     :class="!health?.latency_ms ? 'text-muted' : (health.latency_ms > 500 ? 'text-danger' : (health.latency_ms > 200 ? 'text-warning' : 'text-success'))"
                                     x-text="health?.latency_ms != null ? health.latency_ms + ' ms' : '—'"></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <div class="text-muted small mb-1">Packet Loss</div>
                                <div class="fw-bold fs-5"
                                     :class="!health?.packet_loss ? 'text-muted' : (health.packet_loss > 30 ? 'text-danger' : (health.packet_loss > 10 ? 'text-warning' : 'text-success'))"
                                     x-text="health?.packet_loss != null ? health.packet_loss + '%' : '—'"></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <div class="text-muted small mb-1">Entrada</div>
                                <div class="fw-bold fs-5 text-info"
                                     x-text="health?.network_in != null ? health.network_in + ' Mb/s' : '—'"></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <div class="text-muted small mb-1">Saída</div>
                                <div class="fw-bold fs-5 text-purple"
                                     x-text="health?.network_out != null ? health.network_out + ' Mb/s' : '—'"
                                     style="color:#7c3aed"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Barra de qualidade da rede --}}
                    <div class="mt-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Qualidade da conexão</span>
                            <span x-text="networkQualityLabel()"></span>
                        </div>
                        <div class="progress" style="height:6px">
                            <div class="progress-bar transition-all"
                                 :class="networkQualityClass()"
                                 :style="'width:' + networkQualityPct() + '%'"></div>
                        </div>
                    </div>
                </div>

                {{-- Capacidade de Contas --}}
                <div class="mt-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-semibold">Capacidade de Contas Hospedadas</span>
                        <span class="text-muted">{{ $server->services_count ?? 0 }} / {{ $server->max_accounts ?? '∞' }}</span>
                    </div>
                    @if($server->max_accounts)
                    @php $pct = min(100, (($server->services_count ?? 0) / $server->max_accounts) * 100); @endphp
                    <div class="progress" style="height:8px">
                        <div class="progress-bar {{ $pct > 90 ? 'bg-danger' : ($pct > 70 ? 'bg-warning' : 'bg-primary') }}"
                             style="width:{{ $pct }}%"></div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Gráficos --}}
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-graph-up me-2 text-primary"></i>Histórico (últimas 24h)</span>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary" :class="chartTab==='load'?'active':''" @click="switchChart('load')">Carga</button>
                    <button type="button" class="btn btn-outline-secondary" :class="chartTab==='network'?'active':''" @click="switchChart('network')">Rede</button>
                </div>
            </div>
            <div class="card-body">
                <div x-show="chartTab === 'load'">
                    <canvas id="loadChart" height="110"></canvas>
                </div>
                <div x-show="chartTab === 'network'">
                    <canvas id="networkChart" height="110"></canvas>
                </div>
            </div>
        </div>

        {{-- Contas Hospedadas --}}
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-hdd-stack me-2"></i>Contas Hospedadas</span>
                <input type="text" class="form-control form-control-sm" style="width:200px" placeholder="Buscar..." x-model.debounce.400="search">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Domínio</th><th>Cliente</th><th>Status</th><th>Venc.</th><th class="text-center">Ações</th></tr>
                    </thead>
                    <tbody>
                        @forelse($services as $svc)
                        <tr>
                            <td>
                                <div class="fw-semibold small">{{ $svc->domain }}</div>
                                <code class="text-muted" style="font-size:.7rem">{{ $svc->username }}</code>
                            </td>
                            <td><a href="{{ route('admin.clients.show', $svc->client) }}" class="text-decoration-none small">{{ $svc->client?->name }}</a></td>
                            <td><span class="badge bg-{{ ['active'=>'success','suspended'=>'warning','terminated'=>'danger','cancelled'=>'secondary'][$svc->status] ?? 'secondary' }} bg-opacity-75">{{ ucfirst($svc->status) }}</span></td>
                            <td><small class="{{ $svc->next_due_date && \Carbon\Carbon::parse($svc->next_due_date)->isPast() ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ $svc->next_due_date ? \Carbon\Carbon::parse($svc->next_due_date)->format('d/m/Y') : '—' }}
                            </small></td>
                            <td class="text-center">
                                <a href="{{ route('admin.services.show', $svc) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma conta neste servidor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($services instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="card-footer bg-white">{{ $services->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">

        {{-- Informações do Servidor --}}
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">Informações do Servidor</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    @foreach([
                        ['Hostname',     'hostname'],
                        ['IP Primário',  'ip_address'],
                        ['IP Secundario','ip_address_secondary'],
                        ['Módulo',       'module'],
                        ['Tipo',         'type'],
                        ['Porta',        'port'],
                        ['Datacenter',   'datacenter'],
                        ['Localização',  'location'],
                        ['Sistema Op.',  'os_name'],
                        ['NS1',          'nameserver1'],
                        ['NS2',          'nameserver2'],
                    ] as [$label, $field])
                    @if(!empty($server->{$field}))
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted">{{ $label }}</span>
                        <span class="fw-medium font-monospace" style="font-size:.8rem">{{ $server->{$field} }}</span>
                    </li>
                    @endif
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Ações --}}
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">Ações</div>
            <div class="card-body d-grid gap-2">
                <button class="btn btn-outline-success btn-sm" @click="healthCheck()" :disabled="checkingHealth">
                    <i class="bi bi-activity me-1"></i>Forçar Health Check
                </button>
                <button class="btn btn-outline-primary btn-sm" @click="testConnection()">
                    <i class="bi bi-wifi me-1"></i>Testar Conectividade
                </button>
                <button class="btn btn-outline-warning btn-sm" @click="editServer()">
                    <i class="bi bi-pencil me-1"></i>Editar Servidor
                </button>
                <a href="{{ route('admin.settings.cron') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-clock-history me-1"></i>Cron Jobs
                </a>
                <button class="btn btn-outline-danger btn-sm" @click="deleteServer()">
                    <i class="bi bi-trash me-1"></i>Remover Servidor
                </button>
            </div>
        </div>

        {{-- Último Monitoramento (dinâmico) --}}
        <div class="card mb-3" x-show="health">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Último Monitoramento</span>
                <small class="text-muted" x-text="health?.checked_at"></small>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted">Status</span>
                        <span class="badge rounded-pill"
                              :class="health?.network_status==='online'?'bg-success':(health?.network_status==='degraded'?'bg-warning text-dark':(health?.network_status==='unknown'?'bg-secondary':'bg-danger'))"
                              x-text="{'online':'Operacional','degraded':'Degradado','offline':'Offline','unknown':'Pendente'}[health?.network_status] ?? '—'"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted">CPU</span>
                        <span x-text="health?.cpu != null ? health.cpu + '%' : '—'"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted">RAM</span>
                        <span x-text="health?.ram != null ? health.ram + '%' : '—'"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted">Disco</span>
                        <span x-text="health?.disk != null ? health.disk + '%' : '—'"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted">Latência</span>
                        <span x-text="health?.latency_ms != null ? health.latency_ms + ' ms' : '—'"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted">Packet Loss</span>
                        <span x-text="health?.packet_loss != null ? health.packet_loss + '%' : '—'"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted">Uptime</span>
                        <span x-text="health?.uptime || '—'"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted">Load Avg</span>
                        <span class="font-monospace" style="font-size:.8rem"
                              x-text="[health?.load_avg_1, health?.load_avg_5, health?.load_avg_15].filter(v=>v!=null).join(' / ') || '—'"></span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Auto-refresh indicador --}}
        <div class="card">
            <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                <small class="text-muted"><i class="bi bi-clock-history me-1"></i>Auto-refresh a cada 30s</small>
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted" x-text="'próximo em ' + countdown + 's'"></small>
                    <div class="progress" style="width:60px;height:6px">
                        <div class="progress-bar bg-primary" :style="'width:' + ((30 - countdown) / 30 * 100) + '%'"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editServerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar servidor</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form @submit.prevent="updateServer">
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome *</label>
                        <input type="text" class="form-control" x-model="editForm.name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hostname *</label>
                        <input type="text" class="form-control" x-model="editForm.hostname" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">IP *</label>
                        <input type="text" class="form-control" x-model="editForm.ip_address" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">IP secundario</label>
                        <input type="text" class="form-control" x-model="editForm.ip_address_secondary">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Porta</label>
                        <input type="number" class="form-control" x-model="editForm.port" min="1" max="65535">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" x-model="editForm.type">
                            <option value="shared">Shared</option>
                            <option value="reseller">Reseller</option>
                            <option value="vps">VPS</option>
                            <option value="dedicated">Dedicated</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Modulo</label>
                        <select class="form-select" x-model="editForm.module" @change="applyModuleDefaults()">
                            <option value="whm">WHM/cPanel</option>
                            <option value="whmsonic">WHMSonic</option>
                            <option value="cpanel">cPanel</option>
                            <option value="aapanel">AAPanel</option>
                            <option value="btpanel">BT Panel</option>
                            <option value="plesk">Plesk</option>
                            <option value="directadmin">DirectAdmin</option>
                            <option value="ispconfig">ISPConfig</option>
                            <option value="blesta">Blesta</option>
                            <option value="cyberpanel">CyberPanel</option>
                            <option value="webuzo">Webuzo</option>
                            <option value="hestia">HestiaCP</option>
                            <option value="virtualmin">Virtualmin</option>
                            <option value="none">Nenhum</option>
                        </select>
                    </div>
                    <div class="col-12" x-show="moduleHelpText()">
                        <div class="alert alert-info py-2 small mb-0">
                            <span x-text="moduleHelpText()"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Usuario <span x-show="requiresUsername()">*</span></label>
                        <input type="text" class="form-control" x-model="editForm.username" :required="requiresUsername()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">API Key <span x-show="requiresApiKey()">*</span></label>
                        <input type="text" class="form-control" x-model="editForm.api_key" placeholder="Preencha apenas para alterar" :required="requiresApiKey() && !hasStoredApiKey">
                    </div>
                    <div class="col-md-6" x-show="requiresPassword()">
                        <label class="form-label">Senha <span x-show="requiresPassword()">*</span></label>
                        <input type="password" class="form-control" x-model="editForm.password" placeholder="Preencha apenas para alterar" :required="requiresPassword() && !hasStoredPassword">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Max. contas</label>
                        <input type="number" class="form-control" x-model="editForm.max_accounts" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NS primario</label>
                        <input type="text" class="form-control" x-model="editForm.nameserver1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NS secundario</label>
                        <input type="text" class="form-control" x-model="editForm.nameserver2">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NS terciario</label>
                        <input type="text" class="form-control" x-model="editForm.nameserver3">
                    </div>
                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" x-model="editForm.active">
                                    <label class="form-check-label">Servidor ativo</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" x-model="editForm.secure">
                                    <label class="form-check-label">Usar HTTPS na API</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" :disabled="savingEdit">
                        <span x-show="savingEdit" class="spinner-border spinner-border-sm me-1"></span>Salvar alteracoes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
function serverShow() {
    return {
        health: null,
        checkingHealth: false,
        savingEdit: false,
        hasStoredApiKey: true,
        hasStoredPassword: true,
        moduleCatalog: @js(\App\Services\ServerModules\ServerModuleManager::catalog()),
        editForm: {
            name: @js($server->name),
            hostname: @js($server->hostname),
            ip_address: @js($server->ip_address),
            ip_address_secondary: @js($server->ip_address_secondary),
            port: {{ (int) ($server->port ?: 2087) }},
            type: @js($server->type ?: 'shared'),
            module: @js($server->module ?: 'whm'),
            username: @js($server->username),
            api_key: '',
            password: '',
            max_accounts: {{ (int) ($server->max_accounts ?? 0) }},
            nameserver1: @js($server->nameserver1),
            nameserver2: @js($server->nameserver2),
            nameserver3: @js($server->nameserver3),
            active: {{ $server->active ? 'true' : 'false' }},
            secure: {{ $server->secure ? 'true' : 'false' }},
        },
        search: '',
        chartTab: 'load',
        countdown: 30,
        _timer: null,
        _countdownTimer: null,
        _loadChart: null,
        _networkChart: null,

        async init() {
            await this.loadHealth();
            await this.renderCharts();
            this.startAutoRefresh();
        },

        async loadHealth() {
            const d = await HostPanel.fetch('{{ route("admin.servers.health.status", $server) }}');
            this.health = d;
        },

        async healthCheck() {
            this.checkingHealth = true;
            const d = await HostPanel.fetch('{{ route("admin.servers.health.check", $server) }}', { method: 'POST' });
            await this.loadHealth();
            await this.renderCharts();
            this.checkingHealth = false;
            this.countdown = 30;
            HostPanel.toast(d.message || 'Status atualizado!', 'success');
        },

        async testConnection() {
            const d = await HostPanel.fetch('{{ route("admin.servers.test", $server) }}', { method: 'POST' });
            HostPanel.toast(d.message, d.success ? 'success' : 'danger');
        },

        async toggleActive() {
            try {
                const d = await HostPanel.fetch('{{ route("admin.servers.status", $server) }}', { method: 'POST' });
                if (d.ok) {
                    HostPanel.toast(d.message || 'Status atualizado!');
                } else {
                    HostPanel.toast(d.message || 'Erro ao atualizar status', 'danger');
                }
            } catch (e) {
                HostPanel.toast('Erro na conexão.', 'danger');
            }
        },

        editModal() {
            return bootstrap.Modal.getOrCreateInstance(document.getElementById('editServerModal'));
        },

        requiresUsername() {
            return !!this.moduleCatalog[String(this.editForm.module || 'none').toLowerCase()]?.requires_username;
        },

        requiresApiKey() {
            return !!this.moduleCatalog[String(this.editForm.module || 'none').toLowerCase()]?.requires_api_key;
        },

        requiresPassword() {
            return !!this.moduleCatalog[String(this.editForm.module || 'none').toLowerCase()]?.requires_password;
        },

        usesAaPanel() {
            return ['aapanel', 'btpanel'].includes(String(this.editForm.module || 'none').toLowerCase());
        },

        moduleHelpText() {
            if (this.usesAaPanel()) {
                return 'Use a API Key do painel. Para BT Panel, o sistema usa a mesma integracao do AAPanel.';
            }

            if (this.requiresApiKey() && this.requiresUsername()) {
                return 'Este modulo usa usuario e API Key/token para validar conexao e operacoes automaticas.';
            }

            if (this.requiresUsername() && this.requiresPassword()) {
                return 'Este modulo trabalha com usuario e senha do painel. A senha pode ficar em branco para manter a credencial atual.';
            }

            if (this.requiresApiKey()) {
                return 'Preencha a API Key ou token do painel para habilitar os testes de conectividade.';
            }

            return '';
        },

        applyModuleDefaults() {
            const moduleKey = String(this.editForm.module || 'none').toLowerCase();
            const defaultPort = Number(this.moduleCatalog[moduleKey]?.default_port || 2087);
            const knownPorts = Object.values(this.moduleCatalog)
                .map((definition) => Number(definition.default_port || 0))
                .filter((port) => port > 0);

            if (!this.editForm.port || knownPorts.includes(Number(this.editForm.port))) {
                this.editForm.port = defaultPort;
            }

            if (!this.requiresUsername()) {
                this.editForm.username = '';
            }

            if (!this.requiresApiKey()) {
                this.editForm.api_key = '';
            }

            if (!this.requiresPassword()) {
                this.editForm.password = '';
            }
        },

        editServer() {
            this.editModal().show();
        },

        async updateServer() {
            this.savingEdit = true;
            const payload = {
                ...this.editForm,
                module: String(this.editForm.module || 'none').toLowerCase(),
            };

            const d = await HostPanel.fetch('{{ route("admin.servers.update", $server) }}', {
                method: 'PUT',
                body: JSON.stringify(payload),
            });
            this.savingEdit = false;

            if (!d.ok || !d.server) {
                const message = typeof d.errors === 'object'
                    ? Object.values(d.errors).flat().join(', ')
                    : (d.message || 'Nao foi possivel atualizar o servidor.');
                HostPanel.toast(message, 'danger');
                return;
            }

            this.editModal().hide();
            HostPanel.toast(d.message || 'Servidor atualizado com sucesso!');
            window.location.reload();
        },

        async deleteServer() {
            if (!(await HostPanel.confirm({ text: 'Remover servidor {{ addslashes($server->hostname) }}? Esta acao nao pode ser desfeita.', confirmButtonText: 'Sim, remover' }))) return;
            const d = await HostPanel.fetch('{{ route("admin.servers.destroy", $server) }}', { method: 'DELETE' });
            HostPanel.toast(d.message);
            if (d.ok) setTimeout(() => window.location.href = '{{ route("admin.servers.index") }}', 1200);
        },

        switchChart(tab) {
            this.chartTab = tab;
        },

        networkQualityPct() {
            if (!this.health?.latency_ms) return 0;
            const loss = this.health.packet_loss || 0;
            const lat  = this.health.latency_ms;
            let pct = 100;
            if (lat > 500) pct -= 60;
            else if (lat > 200) pct -= 30;
            else if (lat > 100) pct -= 10;
            if (loss > 30) pct -= 40;
            else if (loss > 10) pct -= 20;
            else if (loss > 0) pct -= 5;
            return Math.max(0, Math.min(100, pct));
        },

        networkQualityClass() {
            const p = this.networkQualityPct();
            if (p >= 80) return 'bg-success';
            if (p >= 50) return 'bg-warning';
            return 'bg-danger';
        },

        networkQualityLabel() {
            const p = this.networkQualityPct();
            if (!this.health?.latency_ms) return '—';
            if (p >= 80) return 'Excelente';
            if (p >= 60) return 'Boa';
            if (p >= 40) return 'Regular';
            return 'Ruim';
        },

        startAutoRefresh() {
            this._countdownTimer = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    this.countdown = 30;
                    this.loadHealth();
                }
            }, 1000);
        },

        async renderCharts() {
            const d = await HostPanel.fetch('{{ route("admin.servers.health.history", $server) }}');
            if (!d.labels) return;

            if (this._loadChart) {
                this._loadChart.destroy();
                this._loadChart = null;
            }

            if (this._networkChart) {
                this._networkChart.destroy();
                this._networkChart = null;
            }

            const baseOpts = {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                scales: { x: { ticks: { maxTicksLimit: 10, font: { size: 10 } } } }
            };

            // Gráfico de Carga (CPU, RAM, Disco)
            const lctx = document.getElementById('loadChart')?.getContext('2d');
            if (lctx) {
                this._loadChart = new Chart(lctx, {
                    type: 'line',
                    data: {
                        labels: d.labels,
                        datasets: [
                            { label: 'CPU %',   data: d.cpu,  borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.08)',  tension: .4, pointRadius: 2, fill: true },
                            { label: 'RAM %',   data: d.ram,  borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.08)', tension: .4, pointRadius: 2, fill: true },
                            { label: 'Disco %', data: d.disk, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.08)', tension: .4, pointRadius: 2, fill: true },
                        ]
                    },
                    options: { ...baseOpts, scales: { ...baseOpts.scales, y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 10 } } } } }
                });
            }

            // Gráfico de Rede (Latência e Packet Loss)
            const nctx = document.getElementById('networkChart')?.getContext('2d');
            if (nctx) {
                this._networkChart = new Chart(nctx, {
                    type: 'line',
                    data: {
                        labels: d.labels,
                        datasets: [
                            { label: 'Latência (ms)', data: d.latency, borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,.08)', tension: .4, pointRadius: 2, fill: true, yAxisID: 'yLat' },
                            { label: 'Packet Loss %', data: d.packet_loss, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.08)', tension: .4, pointRadius: 2, fill: true, yAxisID: 'yLoss' },
                        ]
                    },
                    options: {
                        ...baseOpts,
                        scales: {
                            x: baseOpts.scales.x,
                            yLat:  { type: 'linear', position: 'left',  title: { display: true, text: 'ms', font: { size: 10 } }, ticks: { font: { size: 10 } } },
                            yLoss: { type: 'linear', position: 'right', title: { display: true, text: '%', font: { size: 10 } }, min: 0, max: 100, ticks: { font: { size: 10 } }, grid: { drawOnChartArea: false } },
                        }
                    }
                });
            }
        }
    }
}
</script>
@endpush
