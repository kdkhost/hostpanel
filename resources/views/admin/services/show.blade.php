@extends('admin.layouts.app')
@section('title', $service->domain ?? "Serviço #{$service->id}")
@section('page-title', $service->domain ?? ($service->product?->name ?? "Serviço #{$service->id}"))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.services.index') }}">Serviços</a></li>
    <li class="breadcrumb-item active">{{ $service->domain ?? "#{$service->id}" }}</li>
@endsection

@section('content')
@php
    $statusBadge = ['active'=>'success','suspended'=>'warning','pending'=>'secondary','terminated'=>'danger','provisioning'=>'primary','failed'=>'danger'];
    $statusLabel  = ['active'=>'Ativo','suspended'=>'Suspenso','pending'=>'Pendente','terminated'=>'Encerrado','provisioning'=>'Provisionando','failed'=>'Falhou'];
    $cycleLabel   = ['monthly'=>'Mensal','quarterly'=>'Trimestral','semiannually'=>'Semestral','annually'=>'Anual','biennially'=>'Bienal','triennially'=>'Trienal','free'=>'Grátis'];
@endphp
<div x-data="adminServiceShow()" class="row g-4">

    {{-- Coluna Principal --}}
    <div class="col-lg-8">

        {{-- Info do Serviço --}}
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0">{{ $service->domain ?? $service->product?->name }}</h5>
                    <small class="text-muted">{{ $service->product?->name }} — {{ $cycleLabel[$service->billing_cycle] ?? $service->billing_cycle }}</small>
                </div>
                <span class="badge bg-{{ $statusBadge[$service->status] ?? 'secondary' }} fs-6">
                    {{ $statusLabel[$service->status] ?? $service->status }}
                </span>
            </div>
            <div class="card-body">
                {{-- Ações de Provisionamento --}}
                <div class="d-flex flex-wrap gap-2 mb-4">
                    @if($service->status === 'active')
                        <button class="btn btn-warning btn-sm" @click="suspend()"><i class="bi bi-pause-circle me-1"></i>Suspender</button>
                        @if($service->username)
                        @php $moduleLabel = match($service->server?->module) {
                            'aapanel','btpanel' => 'AAPanel',
                            'whm','cpanel'      => 'cPanel',
                            default             => 'Painel'
                        }; @endphp
                        <a href="{{ route('admin.services.autologin', $service) }}" target="_blank"
                           class="btn btn-primary btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Login {{ $moduleLabel }}
                        </a>
                        <button class="btn btn-outline-info btn-sm" @click="loadUsage()" title="Uso de recursos">
                            <i class="bi bi-bar-chart me-1"></i>Métricas
                        </button>
                        @endif
                    @elseif($service->status === 'suspended')
                        <button class="btn btn-success btn-sm" @click="reactivate()"><i class="bi bi-play-circle me-1"></i>Reativar</button>
                    @elseif(in_array($service->status, ['pending','failed']))
                        <button class="btn btn-primary btn-sm" @click="provision()"><i class="bi bi-gear me-1"></i>Provisionar</button>
                    @endif
                    @if($service->status !== 'terminated')
                        <button class="btn btn-outline-danger btn-sm" @click="terminate()"><i class="bi bi-x-octagon me-1"></i>Encerrar</button>
                    @endif
                    @if($service->username)
                    <button class="btn btn-outline-success btn-sm" @click="sendLinkModal = true" title="Enviar link de acesso ao cliente">
                        <i class="bi bi-send me-1"></i>Enviar Link
                    </button>
                    @endif
                    <button class="btn btn-outline-secondary btn-sm" @click="testConnection()" title="Testar conexão">
                        <i class="bi bi-wifi me-1"></i>Testar
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" @click="editService()"><i class="bi bi-pencil me-1"></i>Editar</button>
                </div>

                {{-- Detalhes --}}
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <div class="text-muted small text-uppercase fw-semibold mb-2">Acesso</div>
                            <ul class="list-unstyled mb-0 small">
                                @if($service->username) <li class="mb-1"><strong>Usuário:</strong> <code>{{ $service->username }}</code></li> @endif
                                @if($service->domain)   <li class="mb-1"><strong>Domínio:</strong> <code>{{ $service->domain }}</code></li> @endif
                                @if($service->server)   <li class="mb-1"><strong>Servidor:</strong> {{ $service->server->hostname }}</li> @endif
                                @if($service->server?->ip_address) <li class="mb-1"><strong>IP:</strong> {{ $service->server->ip_address }}</li> @endif
                                @if($service->server?->nameserver1) <li class="mb-1"><strong>NS1:</strong> {{ $service->server->nameserver1 }}</li> @endif
                                @if($service->server?->nameserver2) <li class="mb-1"><strong>NS2:</strong> {{ $service->server->nameserver2 }}</li> @endif
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3">
                            <div class="text-muted small text-uppercase fw-semibold mb-2">Faturamento</div>
                            <ul class="list-unstyled mb-0 small">
                                <li class="mb-1"><strong>Ciclo:</strong> {{ $cycleLabel[$service->billing_cycle] ?? $service->billing_cycle }}</li>
                                <li class="mb-1"><strong>Valor:</strong> R$ {{ number_format($service->price, 2, ',', '.') }}</li>
                                <li class="mb-1"><strong>Ativado em:</strong> {{ $service->created_at ? \Carbon\Carbon::parse($service->created_at)->format('d/m/Y') : '—' }}</li>
                                <li class="mb-1"><strong>Próx. venc.:</strong>
                                    <span class="{{ $service->next_due_date && \Carbon\Carbon::parse($service->next_due_date)->isPast() ? 'text-danger fw-semibold' : '' }}">
                                        {{ $service->next_due_date ? \Carbon\Carbon::parse($service->next_due_date)->format('d/m/Y') : '—' }}
                                    </span>
                                </li>
                                @if($service->termination_date)
                                    <li class="mb-1"><strong>Encerrado em:</strong> {{ \Carbon\Carbon::parse($service->termination_date)->format('d/m/Y') }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Métricas de Uso (carregadas via AJAX) --}}
                <div x-show="usage" class="mt-3">
                    <div class="border rounded-3 p-3">
                        <div class="fw-semibold small text-uppercase text-muted mb-3">Uso de Recursos</div>
                        <div class="row g-3">
                            <div class="col-sm-6" x-show="usage?.disk_used_bytes">
                                <div class="small fw-semibold mb-1">Disco</div>
                                <div class="progress" style="height:8px">
                                    <div class="progress-bar" :class="diskPct > 90 ? 'bg-danger' : diskPct > 70 ? 'bg-warning' : 'bg-primary'"
                                         :style="'width:' + diskPct + '%'"></div>
                                </div>
                                <small class="text-muted" x-text="fmtBytes(usage.disk_used_bytes) + ' / ' + fmtBytes(usage.disk_total_bytes)"></small>
                            </div>
                            <div class="col-sm-6" x-show="usage?.mem_used_bytes">
                                <div class="small fw-semibold mb-1">Memória</div>
                                <div class="progress" style="height:8px">
                                    <div class="progress-bar bg-info" :style="'width:' + memPct + '%'"></div>
                                </div>
                                <small class="text-muted" x-text="fmtBytes(usage.mem_used_bytes) + ' / ' + fmtBytes(usage.mem_total_bytes)"></small>
                            </div>
                            <div class="col-sm-4" x-show="usage?.load_average">
                                <div class="small fw-semibold mb-1">Carga do Servidor</div>
                                <span class="badge bg-secondary" x-text="'Load: ' + usage.load_average"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Notas Admin --}}
                @if($service->admin_notes)
                <div class="alert alert-info mt-3 mb-0 small"><i class="bi bi-info-circle me-1"></i>{{ $service->admin_notes }}</div>
                @endif
            </div>
        </div>

        {{-- Faturas --}}
        <div class="card mb-4">
            <div class="card-header bg-white fw-semibold">Faturas do Serviço</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Número</th><th>Status</th><th>Vencimento</th><th>Total</th><th class="text-center">Ações</th></tr></thead>
                    <tbody>
                        @forelse($service->invoices ?? [] as $inv)
                        <tr>
                            <td class="fw-semibold">#{{ $inv->number }}</td>
                            <td><span class="badge bg-{{ ['paid'=>'success','overdue'=>'danger','pending'=>'warning','cancelled'=>'secondary'][$inv->status] ?? 'secondary' }}">{{ ['paid'=>'Pago','overdue'=>'Atrasado','pending'=>'Pendente','cancelled'=>'Cancelado'][$inv->status] ?? $inv->status }}</span></td>
                            <td><small>{{ \Carbon\Carbon::parse($inv->date_due)->format('d/m/Y') }}</small></td>
                            <td class="fw-semibold">R$ {{ number_format($inv->total, 2, ',', '.') }}</td>
                            <td class="text-center"><a href="{{ route('admin.invoices.show', $inv) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Nenhuma fatura.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Cliente --}}
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">Cliente</div>
            <div class="card-body">
                <h6 class="fw-bold mb-1">{{ $service->client?->name }}</h6>
                <p class="small text-muted mb-1"><i class="bi bi-envelope me-1"></i>{{ $service->client?->email }}</p>
                @if($service->client?->phone) <p class="small text-muted mb-2"><i class="bi bi-telephone me-1"></i>{{ $service->client?->phone }}</p> @endif
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.clients.show', $service->client) }}" class="btn btn-sm btn-outline-primary flex-grow-1">Ver Perfil</a>
                    <form method="POST" action="{{ route('admin.clients.impersonate', $service->client) }}">
                        @csrf<button type="submit" class="btn btn-sm btn-outline-warning"><i class="bi bi-person-fill-gear"></i></button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Servidor --}}
        @if($service->server)
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">Servidor</div>
            <div class="card-body">
                <h6 class="fw-bold mb-1">{{ $service->server->hostname }}</h6>
                <p class="small text-muted mb-0"><i class="bi bi-hdd me-1"></i>{{ $service->server->ip_address }}</p>
                <p class="small text-muted mb-0"><i class="bi bi-globe me-1"></i>{{ $service->server->nameserver1 }}</p>
                <a href="{{ route('admin.servers.show', $service->server) }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">Ver Servidor</a>
            </div>
        </div>
        @endif

        {{-- Notas Internas --}}
        <div class="card">
            <div class="card-header bg-white fw-semibold">Notas Internas</div>
            <div class="card-body">
                <textarea class="form-control form-control-sm" rows="4" x-model="adminNotes" placeholder="Notas visíveis apenas para admins..."></textarea>
                <button class="btn btn-sm btn-outline-primary w-100 mt-2" @click="saveNotes()" :disabled="savingNotes">
                    <span x-show="savingNotes" class="spinner-border spinner-border-sm me-1"></span>Salvar Notas
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Enviar Link de Acesso ao Cliente --}}
@if($service->username)
<div x-show="sendLinkModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)" @click.self="sendLinkModal=false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-send me-2"></i>Enviar Link de Acesso</h6>
                <button class="btn-close" @click="sendLinkModal=false"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Gera um link com token que será enviado ao cliente por <strong>email</strong>
                    @if($service->client?->whatsapp_enabled) e <strong>WhatsApp</strong>@endif.
                </p>
                <label class="form-label small fw-semibold">Validade do link</label>
                <select class="form-select form-select-sm" x-model="linkHours">
                    <option value="24">24 horas</option>
                    <option value="48">48 horas</option>
                    <option value="72" selected>72 horas (padrão)</option>
                    <option value="168">7 dias</option>
                    <option value="360">15 dias</option>
                    <option value="720">30 dias</option>
                </select>
                <div x-show="sentLinkUrl" class="mt-3">
                    <label class="form-label small fw-semibold text-success">Link gerado:</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace" :value="sentLinkUrl" readonly>
                        <button class="btn btn-outline-secondary" @click="navigator.clipboard.writeText(sentLinkUrl); HostPanel.toast('Copiado!')"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <small class="text-muted">Válido até: <strong x-text="sentLinkExpires"></strong></small>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" @click="sendLinkModal=false">Fechar</button>
                <button class="btn btn-success btn-sm" @click="sendLink()" :disabled="sendingLink">
                    <span x-show="sendingLink" class="spinner-border spinner-border-sm me-1"></span>
                    <span x-text="sendingLink ? 'Enviando...' : 'Enviar Link'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function adminServiceShow() {
    return {
        adminNotes: '{{ addslashes($service->admin_notes ?? "") }}',
        savingNotes: false,
        usage: null,
        sendLinkModal: false,
        sendingLink: false,
        linkHours: '72',
        sentLinkUrl: null,
        sentLinkExpires: null,

        get diskPct() { return this.usage ? Math.min(100, Math.round(this.usage.disk_used_bytes / this.usage.disk_total_bytes * 100)) : 0; },
        get memPct()  { return this.usage ? Math.min(100, Math.round(this.usage.mem_used_bytes  / this.usage.mem_total_bytes  * 100)) : 0; },

        fmtBytes(b) {
            if (!b) return '—';
            if (b >= 1073741824) return (b/1073741824).toFixed(1)+' GB';
            if (b >= 1048576)    return (b/1048576).toFixed(1)+' MB';
            return (b/1024).toFixed(0)+' KB';
        },

        async loadUsage() {
            const d = await HostPanel.fetch('{{ route("admin.services.usage", $service) }}');
            if (d.disk_used_bytes !== undefined || d.mem_used_bytes !== undefined) {
                this.usage = d;
                HostPanel.toast('Métricas atualizadas.');
            } else {
                HostPanel.toast(d.message || 'Não foi possível carregar métricas.', 'warning');
            }
        },

        async testConnection() {
            const d = await HostPanel.fetch('{{ route("admin.services.test.connection", $service) }}', { method:'POST' });
            HostPanel.toast(d.message, d.success ? 'success' : 'danger');
        },

        async sendLink() {
            this.sendingLink = true;
            const d = await HostPanel.fetch('{{ route("admin.services.send.access", $service) }}', {
                method: 'POST',
                body: JSON.stringify({ hours: parseInt(this.linkHours) })
            });
            this.sendingLink = false;
            if (d.success) {
                this.sentLinkUrl     = d.url;
                this.sentLinkExpires = d.expires_at;
                HostPanel.toast(d.message, 'success');
            } else {
                HostPanel.toast(d.message || 'Erro ao enviar link.', 'danger');
            }
        },

        async suspend() {
            if (!confirm('Suspender este serviço no servidor?')) return;
            const d = await HostPanel.fetch('{{ route("admin.services.suspend", $service) }}', { method:'POST' });
            HostPanel.toast(d.message);
            if (d.service) setTimeout(() => window.location.reload(), 1200);
        },

        async reactivate() {
            const d = await HostPanel.fetch('{{ route("admin.services.reactivate", $service) }}', { method:'POST' });
            HostPanel.toast(d.message);
            if (d.service) setTimeout(() => window.location.reload(), 1200);
        },

        async terminate() {
            if (!confirm('ENCERRAR permanentemente este serviço? Esta ação é irreversível!')) return;
            const d = await HostPanel.fetch('{{ route("admin.services.terminate", $service) }}', { method:'POST' });
            HostPanel.toast(d.message);
            if (d.service) setTimeout(() => window.location.href = '{{ route("admin.services.index") }}', 1500);
        },

        async provision() {
            const d = await HostPanel.fetch('{{ route("admin.services.provision", $service) }}', { method:'POST' });
            HostPanel.toast(d.message);
        },

        editService() { window.location.href = '{{ route("admin.services.edit", $service) }}'; },

        async saveNotes() {
            this.savingNotes = true;
            const d = await HostPanel.fetch('{{ route("admin.services.notes", $service) }}', {
                method:'POST', body: JSON.stringify({ notes: this.adminNotes })
            });
            this.savingNotes = false;
            HostPanel.toast(d.message);
        }
    }
}
</script>
@endpush
