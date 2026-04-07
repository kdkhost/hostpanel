@extends('admin.layouts.app')
@section('title', $client->name)
@section('page-title', $client->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clientes</a></li>
    <li class="breadcrumb-item active">{{ $client->name }}</li>
@endsection

@section('content')
<div x-data="adminClientShow()" class="row g-4">

    {{-- Sidebar: Info do Cliente --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center py-4">
                <div class="avatar-initials rounded-circle d-flex align-items-center justify-content-center fw-black text-white mx-auto mb-3"
                     style="width:72px;height:72px;background:linear-gradient(135deg,#1a56db,#7c3aed);font-size:1.75rem">
                    {{ strtoupper(substr($client->name, 0, 1)) }}
                </div>
                <h5 class="fw-bold mb-0">{{ $client->name }}</h5>
                @if($client->company_name) <small class="text-muted">{{ $client->company_name }}</small> @endif
                <div class="mt-2">
                    <span class="badge bg-{{ ['active'=>'success','suspended'=>'warning','banned'=>'danger','pending'=>'secondary'][$client->status] ?? 'secondary' }}">
                        {{ ucfirst($client->status) }}
                    </span>
                </div>
            </div>
            <div class="card-body border-top py-3 px-4">
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2 d-flex gap-2"><i class="bi bi-envelope text-muted"></i><span>{{ $client->email }}</span></li>
                    @if($client->phone) <li class="mb-2 d-flex gap-2"><i class="bi bi-telephone text-muted"></i><span>{{ $client->phone }}</span></li> @endif
                    @if($client->whatsapp) <li class="mb-2 d-flex gap-2"><i class="bi bi-whatsapp text-muted"></i><span>{{ $client->whatsapp }}</span></li> @endif
                    @if($client->document_number) <li class="mb-2 d-flex gap-2"><i class="bi bi-card-text text-muted"></i><span>{{ strtoupper($client->document_type ?? 'CPF') }}: {{ $client->document_number }}</span></li> @endif
                    @if($client->postcode) <li class="mb-2 d-flex gap-2"><i class="bi bi-geo-alt text-muted"></i><span>{{ $client->city }}/{{ $client->state }}</span></li> @endif
                    <li class="d-flex gap-2"><i class="bi bi-calendar text-muted"></i><span>Desde {{ \Carbon\Carbon::parse($client->created_at)->format('d/m/Y') }}</span></li>
                </ul>
            </div>
            <div class="card-footer bg-white py-3">
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary flex-grow-1" @click="editClient()"><i class="bi bi-pencil me-1"></i>Editar</button>
                    <form method="POST" action="{{ route('admin.clients.impersonate', $client) }}">
                        @csrf <button type="submit" class="btn btn-sm btn-outline-warning flex-grow-1"><i class="bi bi-person-fill-gear me-1"></i>Impersonar</button>
                    </form>
                </div>
                @if($client->status === 'active')
                <button class="btn btn-sm btn-outline-danger w-100 mt-2" @click="changeStatus('suspended')"><i class="bi bi-pause-circle me-1"></i>Suspender Conta</button>
                @elseif($client->status === 'suspended')
                <button class="btn btn-sm btn-outline-success w-100 mt-2" @click="changeStatus('active')"><i class="bi bi-play-circle me-1"></i>Reativar Conta</button>
                @endif
            </div>
        </div>

        {{-- KPIs --}}
        <div class="card">
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Serviços Ativos</span>
                        <span class="fw-bold text-primary">{{ $client->services->where('status','active')->count() }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Faturas Pendentes</span>
                        <span class="fw-bold {{ $client->invoices->where('status','pending')->count() > 0 ? 'text-warning' : 'text-success' }}">
                            {{ $client->invoices->where('status','pending')->count() }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Fat. em Atraso</span>
                        <span class="fw-bold {{ $client->invoices->where('status','overdue')->count() > 0 ? 'text-danger' : 'text-success' }}">
                            {{ $client->invoices->where('status','overdue')->count() }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Total Pago</span>
                        <span class="fw-bold text-success">R$ {{ number_format($client->invoices->where('status','paid')->sum('total'), 2, ',', '.') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Saldo de Crédito</span>
                        <span class="fw-bold text-info">R$ {{ number_format($client->credit_balance, 2, ',', '.') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Tickets Abertos</span>
                        <span class="fw-bold">{{ $client->tickets->whereIn('status',['open','in_progress','customer_reply'])->count() }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Conteúdo Principal --}}
    <div class="col-lg-8">
        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><button class="nav-link" :class="{active:tab==='services'}" @click="tab='services'"><i class="bi bi-hdd me-1"></i>Serviços</button></li>
            <li class="nav-item"><button class="nav-link" :class="{active:tab==='invoices'}" @click="tab='invoices'"><i class="bi bi-receipt me-1"></i>Faturas</button></li>
            <li class="nav-item"><button class="nav-link" :class="{active:tab==='tickets'}" @click="tab='tickets'"><i class="bi bi-headset me-1"></i>Tickets</button></li>
            <li class="nav-item"><button class="nav-link" :class="{active:tab==='logs'}" @click="tab='logs'"><i class="bi bi-clock-history me-1"></i>Histórico</button></li>
        </ul>

        {{-- Serviços --}}
        <div x-show="tab==='services'">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between">
                    <span class="fw-semibold">Serviços</span>
                    <button class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Adicionar</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Domínio/Produto</th><th>Status</th><th>Ciclo</th><th>Próx. Venc.</th><th>Valor</th><th class="text-center">Ações</th></tr></thead>
                        <tbody>
                            @forelse($client->services as $svc)
                            <tr>
                                <td><div class="fw-semibold">{{ $svc->domain ?? $svc->product?->name }}</div><small class="text-muted">{{ $svc->product?->name }}</small></td>
                                <td><span class="badge bg-{{ ['active'=>'success','suspended'=>'warning','pending'=>'secondary','terminated'=>'danger'][$svc->status] ?? 'secondary' }}">{{ ucfirst($svc->status) }}</span></td>
                                <td><small class="text-muted">{{ ['monthly'=>'Mensal','annually'=>'Anual'][$svc->billing_cycle] ?? $svc->billing_cycle }}</small></td>
                                <td><small>{{ $svc->next_due_date ? \Carbon\Carbon::parse($svc->next_due_date)->format('d/m/Y') : '—' }}</small></td>
                                <td class="fw-semibold">R$ {{ number_format($svc->price, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.services.show', $svc) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum serviço.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Faturas --}}
        <div x-show="tab==='invoices'">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between">
                    <span class="fw-semibold">Faturas</span>
                    <button class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Nova Fatura</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Número</th><th>Status</th><th>Emissão</th><th>Vencimento</th><th>Total</th><th class="text-center">Ações</th></tr></thead>
                        <tbody>
                            @forelse($client->invoices()->orderByDesc('date_issued')->take(20)->get() as $inv)
                            <tr>
                                <td class="fw-semibold">#{{ $inv->number }}</td>
                                <td><span class="badge bg-{{ ['paid'=>'success','overdue'=>'danger','pending'=>'warning','cancelled'=>'secondary'][$inv->status] ?? 'secondary' }}">{{ ['paid'=>'Pago','overdue'=>'Atrasado','pending'=>'Pendente','cancelled'=>'Cancelado'][$inv->status] ?? $inv->status }}</span></td>
                                <td><small>{{ \Carbon\Carbon::parse($inv->date_issued)->format('d/m/Y') }}</small></td>
                                <td><small>{{ \Carbon\Carbon::parse($inv->date_due)->format('d/m/Y') }}</small></td>
                                <td class="fw-semibold">R$ {{ number_format($inv->total, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.invoices.show', $inv) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma fatura.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tickets --}}
        <div x-show="tab==='tickets'">
            <div class="card">
                <div class="card-header bg-white fw-semibold">Tickets de Suporte</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Número</th><th>Assunto</th><th>Status</th><th>Prioridade</th><th>Último Update</th><th class="text-center">Ações</th></tr></thead>
                        <tbody>
                            @forelse($client->tickets()->orderByDesc('last_reply_at')->take(20)->get() as $tkt)
                            <tr>
                                <td class="fw-semibold">#{{ $tkt->number }}</td>
                                <td>{{ Str::limit($tkt->subject, 40) }}</td>
                                <td><span class="badge bg-{{ ['open'=>'danger','in_progress'=>'primary','answered'=>'success','closed'=>'secondary'][$tkt->status] ?? 'secondary' }} bg-opacity-75">{{ ['open'=>'Aberto','answered'=>'Respondido','closed'=>'Fechado','in_progress'=>'Em Andamento'][$tkt->status] ?? $tkt->status }}</span></td>
                                <td><span class="badge bg-{{ ['urgent'=>'danger','high'=>'warning','medium'=>'primary','low'=>'secondary'][$tkt->priority] ?? 'secondary' }} bg-opacity-75">{{ ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Média','low'=>'Baixa'][$tkt->priority] ?? $tkt->priority }}</span></td>
                                <td><small class="text-muted">{{ $tkt->last_reply_at ? \Carbon\Carbon::parse($tkt->last_reply_at)->diffForHumans() : '—' }}</small></td>
                                <td class="text-center"><a href="{{ route('admin.tickets.show', $tkt) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum ticket.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Histórico de Logins --}}
        <div x-show="tab==='logs'">
            <div class="card">
                <div class="card-header bg-white fw-semibold">Histórico de Acessos</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Data/Hora</th><th>IP</th><th>Status</th><th>User-Agent</th></tr></thead>
                        <tbody>
                            @forelse($client->loginLogs ?? [] as $log)
                            <tr>
                                <td><small>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</small></td>
                                <td><code class="small">{{ $log->ip_address }}</code></td>
                                <td><span class="badge {{ $log->success ? 'bg-success' : 'bg-danger' }} bg-opacity-75">{{ $log->success ? 'Sucesso' : 'Falhou' }}</span></td>
                                <td><small class="text-muted" title="{{ $log->user_agent }}">{{ Str::limit($log->user_agent, 50) }}</small></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Nenhum registro.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function adminClientShow() {
    return {
        tab: 'services',

        editClient() {
            window.location.href = '{{ route("admin.clients.edit", $client) }}';
        },

        async changeStatus(status) {
            const labels = { suspended: 'suspender', active: 'reativar' };
            if (!confirm(`Deseja ${labels[status]} a conta de {{ $client->name }}?`)) return;
            const d = await HostPanel.fetch('{{ route("admin.clients.status", $client) }}', {
                method: 'POST', body: JSON.stringify({ status })
            });
            HostPanel.toast(d.message);
            if (d.status) setTimeout(() => window.location.reload(), 1200);
        }
    }
}
</script>
@endpush
