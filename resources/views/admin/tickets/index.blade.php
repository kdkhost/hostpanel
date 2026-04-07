@extends('admin.layouts.app')
@section('title', 'Tickets')
@section('page-title', 'Tickets de Suporte')
@section('breadcrumb')
    <li class="breadcrumb-item active">Tickets</li>
@endsection

@section('content')
<div x-data="ticketsTable()">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between flex-wrap gap-2 align-items-center">
            <h5 class="mb-0 fw-semibold"><i class="bi bi-headset me-2"></i>Tickets</h5>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <input type="text" class="form-control form-control-sm" style="width:220px" placeholder="Buscar..." x-model.debounce.400="search" @input="page=1;load()">
                <select class="form-select form-select-sm" style="width:140px" x-model="status" @change="page=1;load()">
                    <option value="">Todos status</option>
                    <option value="open">Aberto</option>
                    <option value="in_progress">Em Andamento</option>
                    <option value="answered">Respondido</option>
                    <option value="on_hold">Em Espera</option>
                    <option value="customer_reply">Resposta do Cliente</option>
                    <option value="closed">Fechado</option>
                </select>
                <select class="form-select form-select-sm" style="width:130px" x-model="priority" @change="page=1;load()">
                    <option value="">Prioridade</option>
                    <option value="urgent">Urgente</option>
                    <option value="high">Alta</option>
                    <option value="medium">Média</option>
                    <option value="low">Baixa</option>
                </select>
                <a href="{{ route('admin.tickets.kanban') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-kanban me-1"></i>Kanban</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Ticket</th><th>Cliente</th><th>Departamento</th><th>Prioridade</th><th>Status</th><th>Atribuído</th><th>Última Resposta</th><th class="text-center">Ações</th></tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>
                        </template>
                        <template x-for="t in tickets" :key="t.id">
                            <tr>
                                <td>
                                    <a :href="`/admin/tickets/${t.id}`" class="fw-semibold" x-text="`#${t.number}`"></a>
                                    <div class="text-muted small text-truncate" style="max-width:180px" x-text="t.subject"></div>
                                </td>
                                <td>
                                    <div class="small" x-text="t.client?.name"></div>
                                    <div class="text-muted" style="font-size:.75rem" x-text="t.client?.email"></div>
                                </td>
                                <td><span class="badge bg-light text-dark" x-text="t.department?.name || '—'"></span></td>
                                <td><span :class="`badge bg-${priorityColor(t.priority)}`" x-text="priorityLabel(t.priority)"></span></td>
                                <td><span :class="`badge bg-${statusColor(t.status)}`" x-text="statusLabel(t.status)"></span></td>
                                <td class="text-muted small" x-text="t.assigned_admin?.name || '—'"></td>
                                <td class="text-muted small" x-text="timeAgo(t.last_reply_at)"></td>
                                <td class="text-center">
                                    <a :href="`/admin/tickets/${t.id}`" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && tickets.length === 0">
                            <tr><td colspan="8" class="text-center text-muted py-4">Nenhum ticket encontrado.</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between" x-show="meta">
            <span class="text-muted small" x-text="`${meta?.from??0}–${meta?.to??0} de ${meta?.total??0}`"></span>
            <nav><ul class="pagination pagination-sm mb-0">
                <li class="page-item" :class="{disabled: page===1}"><button class="page-link" @click="page--;load()">«</button></li>
                <li class="page-item active"><a class="page-link" x-text="page"></a></li>
                <li class="page-item" :class="{disabled: page>=meta?.last_page}"><button class="page-link" @click="page++;load()">»</button></li>
            </ul></nav>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ticketsTable() {
    return {
        tickets: [], meta: null, loading: false,
        search: '', status: '', priority: '', page: 1,

        async load() {
            this.loading = true;
            const p = new URLSearchParams({ search: this.search, status: this.status, priority: this.priority, page: this.page });
            const d = await HostPanel.fetch(`/admin/tickets?${p}`);
            this.tickets = d.data || [];
            this.meta    = d.meta || d;
            this.loading = false;
        },

        priorityColor(p) { return {urgent:'danger',high:'warning',medium:'primary',low:'secondary'}[p]||'secondary'; },
        priorityLabel(p) { return {urgent:'Urgente',high:'Alta',medium:'Média',low:'Baixa'}[p]||p; },
        statusColor(s)   { return {open:'danger',in_progress:'primary',answered:'success',on_hold:'warning',customer_reply:'info',closed:'secondary'}[s]||'secondary'; },
        statusLabel(s)   { return {open:'Aberto',in_progress:'Em Andamento',answered:'Respondido',on_hold:'Em Espera',customer_reply:'Resp. Cliente',closed:'Fechado'}[s]||s; },
        timeAgo(d) {
            if (!d) return '—';
            const diff = Math.floor((Date.now() - new Date(d)) / 60000);
            if (diff < 1) return 'agora';
            if (diff < 60) return `${diff}min atrás`;
            if (diff < 1440) return `${Math.floor(diff/60)}h atrás`;
            return `${Math.floor(diff/1440)}d atrás`;
        },
        init() { this.load(); }
    }
}
</script>
@endpush
