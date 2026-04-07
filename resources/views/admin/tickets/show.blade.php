@extends('admin.layouts.app')
@section('title', "Ticket #{$ticket->number}")
@section('page-title', "Ticket #{$ticket->number}")
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">Tickets</a></li>
    <li class="breadcrumb-item active">#{{ $ticket->number }}</li>
@endsection

@section('content')
<div x-data="adminTicketShow()" class="row g-4">

    {{-- Coluna Principal: Mensagens + Resposta --}}
    <div class="col-lg-8">
        {{-- Header do Ticket --}}
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-2">{{ $ticket->subject }}</h5>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-{{ ['urgent'=>'danger','high'=>'warning','medium'=>'primary','low'=>'secondary'][$ticket->priority] ?? 'secondary' }}">
                        {{ ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Média','low'=>'Baixa'][$ticket->priority] ?? $ticket->priority }}
                    </span>
                    <span class="badge bg-{{ ['open'=>'danger','in_progress'=>'primary','answered'=>'success','on_hold'=>'warning','customer_reply'=>'info','closed'=>'secondary'][$ticket->status] ?? 'secondary' }}" id="status-badge">
                        {{ ['open'=>'Aberto','in_progress'=>'Em Andamento','answered'=>'Respondido','on_hold'=>'Em Espera','customer_reply'=>'Resp. Cliente','closed'=>'Fechado'][$ticket->status] ?? $ticket->status }}
                    </span>
                    <span class="badge bg-light text-dark border">{{ $ticket->department?->name }}</span>
                    @if($ticket->service)
                        <span class="badge bg-light text-dark border"><i class="bi bi-hdd me-1"></i>{{ $ticket->service->domain ?? "Serviço #{$ticket->service->id}" }}</span>
                    @endif
                </div>

                {{-- Ações rápidas de status --}}
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary" @click="changeStatus('in_progress')"><i class="bi bi-play-circle me-1"></i>Em Andamento</button>
                    <button class="btn btn-sm btn-outline-success" @click="changeStatus('answered')"><i class="bi bi-check me-1"></i>Respondido</button>
                    <button class="btn btn-sm btn-outline-warning" @click="changeStatus('on_hold')"><i class="bi bi-pause-circle me-1"></i>Em Espera</button>
                    <button class="btn btn-sm btn-outline-secondary" @click="changeStatus('closed')"><i class="bi bi-lock me-1"></i>Fechar</button>
                </div>
            </div>
        </div>

        {{-- Mensagens --}}
        <div class="d-flex flex-column gap-3 mb-4">
            @foreach($ticket->replies as $reply)
            @php $isClient = !is_null($reply->client_id); @endphp
            <div class="d-flex gap-3 {{ !$isClient ? 'flex-row-reverse' : '' }}">
                <div class="avatar-initials rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                    style="width:40px;height:40px;background:{{ $isClient ? '#1a56db' : '#1e293b' }};font-size:.875rem">
                    {{ strtoupper(substr($isClient ? ($reply->client?->name ?? 'C') : ($reply->admin?->name ?? 'S'), 0, 1)) }}
                </div>
                <div style="max-width:75%" class="{{ !$isClient ? 'text-end' : '' }}">
                    <div class="rounded-3 p-3 {{ $isClient ? 'bg-white border' : 'text-white' }}" style="{{ !$isClient ? 'background:#1a56db' : '' }}">
                        <div class="fw-semibold mb-1 small {{ $isClient ? 'text-muted' : 'text-white-50' }}">
                            {{ $isClient ? ($reply->client?->name ?? 'Cliente') : ($reply->admin?->name ?? 'Suporte') }}
                            {{ !$isClient ? '(Admin)' : '' }}
                        </div>
                        <div class="small" style="white-space:pre-wrap">{{ $reply->message }}</div>
                        @if($reply->attachments && count($reply->attachments))
                        <div class="mt-2 d-flex flex-wrap gap-1">
                            @foreach($reply->attachments as $att)
                            <a href="{{ asset('storage/'.$att) }}" target="_blank" class="btn btn-sm btn-outline-{{ $isClient ? 'secondary' : 'light' }} py-0 px-2" style="font-size:.7rem">
                                <i class="bi bi-paperclip"></i> Anexo
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <div class="text-muted mt-1 px-1" style="font-size:.7rem">{{ \Carbon\Carbon::parse($reply->created_at)->format('d/m/Y H:i') }}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Resposta Admin --}}
        @if($ticket->status !== 'closed')
        <div class="card">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-reply me-2"></i>Responder como Suporte</div>
            <div class="card-body">
                <form @submit.prevent="reply">
                    <div class="mb-3">
                        <textarea class="form-control" rows="5" x-model="message" placeholder="Digite sua resposta..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <input type="file" class="form-control form-control-sm" multiple x-ref="files" accept="image/*,.pdf,.txt,.log">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" :disabled="loading">
                            <span x-show="loading" class="spinner-border spinner-border-sm me-1"></span>
                            Enviar Resposta
                        </button>
                        <button type="button" class="btn btn-outline-secondary" @click="quickReply('Olá! Estamos analisando o seu chamado e retornaremos em breve. Agradecemos a paciência.')">
                            <i class="bi bi-lightning me-1"></i>Resposta Rápida
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar: Info Cliente + Ações --}}
    <div class="col-lg-4">
        {{-- Info do Cliente --}}
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person me-2"></i>Cliente</div>
            <div class="card-body">
                <h6 class="fw-bold">{{ $ticket->client?->name }}</h6>
                <p class="text-muted small mb-1"><i class="bi bi-envelope me-1"></i>{{ $ticket->client?->email }}</p>
                @if($ticket->client?->phone)
                    <p class="text-muted small mb-1"><i class="bi bi-telephone me-1"></i>{{ $ticket->client?->phone }}</p>
                @endif
                <div class="d-flex gap-2 mt-2">
                    <a href="{{ route('admin.clients.show', $ticket->client) }}" class="btn btn-sm btn-outline-primary flex-grow-1">Ver Cliente</a>
                    <form method="POST" action="{{ route('admin.clients.impersonate', $ticket->client) }}" class="flex-grow-1">
                        @csrf <button type="submit" class="btn btn-sm btn-outline-warning w-100"><i class="bi bi-person-fill-gear"></i></button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Informações do Ticket --}}
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-info-circle me-2"></i>Informações</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small">Criado em</span>
                        <span class="small">{{ \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y H:i') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small">Última resposta</span>
                        <span class="small">{{ $ticket->last_reply_at ? \Carbon\Carbon::parse($ticket->last_reply_at)->format('d/m/Y H:i') : '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small">Respostas</span>
                        <span class="small">{{ $ticket->replies->count() }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small">Atribuído a</span>
                        <span class="small">{{ $ticket->assignedAdmin?->name ?? '—' }}</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Atribuição --}}
        <div class="card">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person-plus me-2"></i>Atribuir Admin</div>
            <div class="card-body">
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" x-model="assignedId">
                        <option value="">Sem atribuição</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}" {{ $ticket->assigned_admin_id == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-primary" @click="assign">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function adminTicketShow() {
    return {
        message: '', loading: false, assignedId: '{{ $ticket->assigned_admin_id ?? "" }}',

        async reply() {
            this.loading = true;
            const fd = new FormData();
            fd.append('message', this.message);
            fd.append('_token', HostPanel.csrfToken);
            Array.from(this.$refs.files.files).forEach(f => fd.append('attachments[]', f));
            const res  = await fetch('{{ route("admin.tickets.reply", $ticket) }}', { method:'POST', headers:{'Accept':'application/json'}, body: fd });
            const data = await res.json();
            this.loading = false;
            if (data.reply) { window.location.reload(); }
            else HostPanel.toast(data.message || 'Erro ao enviar.', 'danger');
        },

        quickReply(text) { this.message = text; },

        async changeStatus(status) {
            const d = await HostPanel.fetch('{{ route("admin.tickets.status.post", $ticket) }}', { method:'POST', body: JSON.stringify({ status }) });
            HostPanel.toast(d.message);
            if (d.status) document.getElementById('status-badge').textContent = { open:'Aberto', in_progress:'Em Andamento', answered:'Respondido', on_hold:'Em Espera', customer_reply:'Resp. Cliente', closed:'Fechado' }[d.status] || d.status;
        },

        async assign() {
            const d = await HostPanel.fetch('{{ route("admin.tickets.assign.post", $ticket) }}', { method:'POST', body: JSON.stringify({ admin_id: this.assignedId }) });
            HostPanel.toast(d.message);
        }
    }
}
</script>
@endpush
