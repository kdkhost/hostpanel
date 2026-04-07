@extends('admin.layouts.app')
@section('title', 'Kanban de Tickets')
@section('page-title', 'Kanban de Tickets')
@section('breadcrumb')
    <li class="breadcrumb-item active">Kanban</li>
@endsection

@push('styles')
<style>
.kanban-board   { display:flex; gap:1rem; overflow-x:auto; padding-bottom:1rem; min-height:calc(100vh - 240px); }
.kanban-col     { flex:0 0 280px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; display:flex; flex-direction:column; max-height:calc(100vh - 240px); }
.kanban-header  { padding:12px 14px; border-bottom:1px solid #e2e8f0; position:sticky; top:0; background:#f8fafc; border-radius:12px 12px 0 0; z-index:1; }
.kanban-items   { overflow-y:auto; padding:8px; flex:1; }
.kanban-card    { background:#fff; border-radius:8px; border:1px solid #e2e8f0; padding:10px 12px; margin-bottom:8px; cursor:pointer; transition:.15s; }
.kanban-card:hover { border-color:#1a56db; box-shadow:0 2px 8px rgba(26,86,219,.12); }
.priority-low    { border-left:3px solid #94a3b8; }
.priority-medium { border-left:3px solid #f59e0b; }
.priority-high   { border-left:3px solid #ef4444; }
.priority-urgent { border-left:3px solid #7c3aed; }
.badge-dot       { display:inline-block; width:8px; height:8px; border-radius:50%; }
</style>
@endpush

@section('content')
<div x-data="kanbanBoard()">

    {{-- Filtros --}}
    <div class="d-flex gap-2 align-items-center mb-4 flex-wrap">
        <select class="form-select form-select-sm" style="width:200px" x-model="filters.department_id" @change="load()">
            <option value="">Todos os Departamentos</option>
            @foreach(\App\Models\TicketDepartment::where('active',true)->get() as $dept)
            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
        </select>
        <select class="form-select form-select-sm" style="width:180px" x-model="filters.assigned_to" @change="load()">
            <option value="">Todos os Agentes</option>
            @foreach(\App\Models\Admin::where('status','active')->get() as $adm)
            <option value="{{ $adm->id }}">{{ $adm->name }}</option>
            @endforeach
        </select>
        <div class="form-check ms-1">
            <input class="form-check-input" type="checkbox" x-model="autoRefresh" id="autoRefresh" @change="toggleAutoRefresh()">
            <label class="form-check-label small" for="autoRefresh">Atualizar automaticamente</label>
        </div>
        <button class="btn btn-sm btn-outline-secondary ms-auto" @click="load()">
            <i class="bi bi-arrow-repeat me-1"></i>Atualizar
        </button>
    </div>

    {{-- Board --}}
    <div class="kanban-board" x-show="!loading">
        <template x-for="col in columns" :key="col.id">
            <div class="kanban-col">
                <div class="kanban-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge-dot" :style="`background:${col.color}`"></span>
                            <span class="fw-bold small" x-text="col.label"></span>
                        </div>
                        <span class="badge bg-white border text-dark small" x-text="col.tickets?.length ?? 0"></span>
                    </div>
                </div>
                <div class="kanban-items">
                    <template x-for="ticket in col.tickets" :key="ticket.id">
                        <div class="kanban-card" :class="`priority-${ticket.priority}`"
                             @click="openTicket(ticket)">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <code class="text-muted" style="font-size:.7rem" x-text="'#'+ticket.number"></code>
                                <span class="badge" :class="{
                                    'bg-secondary': ticket.priority==='low',
                                    'bg-warning text-dark': ticket.priority==='medium',
                                    'bg-danger': ticket.priority==='high',
                                    'bg-purple': ticket.priority==='urgent',
                                }" style="font-size:.6rem" x-text="ticket.priority?.toUpperCase()"></span>
                            </div>
                            <div class="fw-semibold small mb-1 text-truncate" x-text="ticket.subject" style="max-width:240px"></div>
                            <div class="small text-muted d-flex align-items-center gap-2 mt-2">
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold"
                                     style="width:20px;height:20px;font-size:.6rem"
                                     x-text="(ticket.client?.name ?? '?').charAt(0).toUpperCase()"></div>
                                <span class="text-truncate" x-text="ticket.client?.name ?? '—'" style="max-width:120px"></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="text-muted" style="font-size:.7rem" x-text="timeAgo(ticket.last_reply_at)"></span>
                                <div x-show="ticket.assigned_admin">
                                    <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center fw-bold"
                                         style="width:20px;height:20px;font-size:.6rem"
                                         :title="ticket.assigned_admin?.name"
                                         x-text="(ticket.assigned_admin?.name ?? '').charAt(0).toUpperCase()"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div class="text-center text-muted py-3 small" x-show="!col.tickets?.length">Sem tickets</div>
                </div>
            </div>
        </template>
    </div>

    <div class="text-center py-5" x-show="loading"><div class="spinner-border text-primary"></div></div>
</div>
@endsection

@push('scripts')
<script>
function kanbanBoard() {
    const STATUS_CONFIG = {
        open:           { label:'Aberto',           color:'#ef4444' },
        in_progress:    { label:'Em Andamento',     color:'#f59e0b' },
        answered:       { label:'Respondido',       color:'#3b82f6' },
        on_hold:        { label:'Em Espera',        color:'#94a3b8' },
        closed:         { label:'Fechado',          color:'#10b981' },
    };

    return {
        columns: [], loading: false, autoRefresh: false, refreshTimer: null,
        filters: { department_id:'', assigned_to:'' },

        async load() {
            this.loading = true;
            const p = new URLSearchParams(Object.fromEntries(Object.entries(this.filters).filter(([,v]) => v)));
            const data = await HostPanel.fetch(`{{ route('admin.tickets.kanban') }}?${p}`);
            this.columns = Object.entries(STATUS_CONFIG).map(([id, config]) => ({
                id,
                ...config,
                tickets: data[id] ?? [],
            }));
            this.loading = false;
        },

        openTicket(ticket) {
            window.open(`/admin/tickets/${ticket.id}`, '_blank');
        },

        toggleAutoRefresh() {
            if (this.autoRefresh) {
                this.refreshTimer = setInterval(() => this.load(), 30000);
            } else {
                clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            }
        },

        timeAgo(d) {
            if (!d) return '—';
            const diff = Math.floor((Date.now() - new Date(d)) / 1000);
            if (diff < 60)    return diff + 's atrás';
            if (diff < 3600)  return Math.floor(diff/60) + 'min atrás';
            if (diff < 86400) return Math.floor(diff/3600) + 'h atrás';
            return Math.floor(diff/86400) + 'd atrás';
        },

        init() { this.load(); }
    }
}
</script>
@endpush
