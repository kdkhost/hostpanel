@extends('admin.layouts.app')
@section('title', 'Serviços')
@section('page-title', 'Serviços')
@section('breadcrumb')
    <li class="breadcrumb-item active">Serviços</li>
@endsection

@section('content')
<div x-data="servicesTable()">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between flex-wrap gap-2 align-items-center">
            <h5 class="mb-0 fw-semibold"><i class="bi bi-hdd-stack me-2"></i>Serviços</h5>
            <div class="d-flex gap-2 flex-wrap">
                <input type="text" class="form-control form-control-sm" style="width:220px" placeholder="Domínio, usuário ou cliente..." x-model.debounce.400="search" @input="page=1;load()">
                <select class="form-select form-select-sm" style="width:130px" x-model="status" @change="page=1;load()">
                    <option value="">Todos status</option>
                    <option value="pending">Pendente</option>
                    <option value="active">Ativo</option>
                    <option value="suspended">Suspenso</option>
                    <option value="terminated">Encerrado</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Serviço</th><th>Cliente</th><th>Produto</th><th>Servidor</th><th>Status</th><th>Próx. Venc.</th><th class="text-center">Ações</th></tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>
                        </template>
                        <template x-for="s in services" :key="s.id">
                            <tr>
                                <td>
                                    <div class="fw-semibold" x-text="s.domain || s.username || `#${s.id}`"></div>
                                    <div class="text-muted small" x-text="s.username ? `cPanel: ${s.username}` : ''"></div>
                                </td>
                                <td>
                                    <a :href="`/admin/clientes/${s.client?.id}`" x-text="s.client?.name" class="text-dark small"></a>
                                </td>
                                <td class="small text-muted" x-text="s.product?.name || s.product_name || '—'"></td>
                                <td class="small text-muted" x-text="s.server?.hostname || '—'"></td>
                                <td><span :class="`badge bg-${statusColor(s.status)}`" x-text="statusLabel(s.status)"></span></td>
                                <td :class="isExpiring(s.next_due_date) ? 'text-danger fw-semibold' : 'text-muted'" class="small" x-text="formatDate(s.next_due_date)"></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a :href="`/admin/servicos/${s.id}`" class="btn btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></a>
                                        <button x-show="s.status === 'active'" class="btn btn-outline-warning" title="Suspender" @click="suspend(s)"><i class="bi bi-pause-circle"></i></button>
                                        <button x-show="s.status === 'suspended'" class="btn btn-outline-success" title="Reativar" @click="reactivate(s)"><i class="bi bi-play-circle"></i></button>
                                        <button x-show="s.status === 'active' || s.status === 'suspended'" class="btn btn-outline-info" title="cPanel" @click="cpanelLogin(s)"><i class="bi bi-box-arrow-up-right"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && services.length === 0">
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum serviço encontrado.</td></tr>
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
function servicesTable() {
    return {
        services: [], meta: null, loading: false, search: '', status: '', page: 1,

        async load() {
            this.loading = true;
            const p = new URLSearchParams({ search: this.search, status: this.status, page: this.page });
            const d = await HostPanel.fetch(`/admin/servicos?${p}`);
            this.services = d.data || [];
            this.meta = d.meta || d;
            this.loading = false;
        },

        async suspend(s) {
            const reason = prompt('Motivo da suspensão (opcional):') ?? '';
            const d = await HostPanel.fetch(`/admin/servicos/${s.id}/suspender`, { method:'POST', body: JSON.stringify({ reason }) });
            HostPanel.toast(d.message, d.message.includes('sucesso') ? 'success' : 'danger');
            this.load();
        },

        async reactivate(s) {
            const d = await HostPanel.fetch(`/admin/servicos/${s.id}/reativar`, { method:'POST' });
            HostPanel.toast(d.message, d.message.includes('sucesso') ? 'success' : 'danger');
            this.load();
        },

        async cpanelLogin(s) {
            const d = await HostPanel.fetch(`/admin/servicos/${s.id}/cpanel-login`, { method:'POST' });
            if (d.url) window.open(d.url, '_blank');
            else HostPanel.toast(d.message, 'danger');
        },

        statusColor(s) { return {pending:'secondary',active:'success',suspended:'warning',terminated:'danger',provisioning:'info',failed:'danger'}[s]||'secondary'; },
        statusLabel(s) { return {pending:'Pendente',active:'Ativo',suspended:'Suspenso',terminated:'Encerrado',provisioning:'Provisionando',failed:'Falhou'}[s]||s; },
        formatDate(d) { return d ? new Date(d+'T00:00:00').toLocaleDateString('pt-BR') : '—'; },
        isExpiring(d) { return d && new Date(d+'T00:00:00') < new Date(Date.now() + 7 * 86400000); },
        init() { this.load(); }
    }
}
</script>
@endpush
