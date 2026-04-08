@extends('admin.layouts.app')
@section('title', 'Clientes')
@section('page-title', 'Clientes')
@section('breadcrumb')
    <li class="breadcrumb-item active">Clientes</li>
@endsection

@section('content')
<div x-data="clientsTable()">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-semibold"><i class="bi bi-people me-2"></i>Clientes</h5>
            <div class="d-flex gap-2 flex-wrap">
                <div class="input-group input-group-sm" style="width:260px">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" placeholder="Buscar por nome, email ou CPF/CNPJ..." x-model.debounce.400="search" @input="page=1;load()">
                </div>
                <select class="form-select form-select-sm" style="width:130px" x-model="status" @change="page=1;load()">
                    <option value="">Todos os status</option>
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                    <option value="pending">Pendente</option>
                    <option value="blocked">Bloqueado</option>
                </select>
                <button class="btn btn-sm btn-primary" @click="showModal=true"><i class="bi bi-plus-lg me-1"></i>Novo Cliente</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Cliente</th><th>Documento</th><th>Status</th>
                            <th class="text-end">Serviços</th><th class="text-end">Faturas</th>
                            <th>Cadastrado</th><th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>
                        </template>
                        <template x-for="client in clients" :key="client.id">
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:38px;height:38px;font-size:.875rem" x-text="client.name.charAt(0).toUpperCase()"></div>
                                        <div>
                                            <a :href="`/admin/clientes/${client.id}`" class="fw-semibold text-dark" x-text="client.name"></a>
                                            <div class="text-muted small" x-text="client.email"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted small" x-text="client.document_number || '—'"></td>
                                <td>
                                    <span :class="`badge bg-${statusColor(client.status)}`" x-text="statusLabel(client.status)"></span>
                                </td>
                                <td class="text-end" x-text="client.services_count ?? '—'"></td>
                                <td class="text-end" x-text="client.invoices_count ?? '—'"></td>
                                <td class="text-muted small" x-text="formatDate(client.created_at)"></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a :href="`/admin/clientes/${client.id}`" class="btn btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></a>
                                        <button class="btn btn-outline-warning" title="Impersonar" @click="impersonate(client)"><i class="bi bi-person-fill-gear"></i></button>
                                        <button :class="`btn btn-outline-${client.status==='active'?'danger':'success'}`" @click="toggleStatus(client)" :title="client.status==='active'?'Bloquear':'Ativar'">
                                            <i :class="`bi bi-${client.status==='active'?'lock':'unlock'}`"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && clients.length === 0">
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum cliente encontrado.</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center" x-show="meta">
            <span class="text-muted small" x-text="`Mostrando ${meta?.from ?? 0}–${meta?.to ?? 0} de ${meta?.total ?? 0} registros`"></span>
            <nav x-show="meta?.last_page > 1">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{disabled: page === 1}"><button class="page-link" @click="page--;load()">«</button></li>
                    <li class="page-item active"><a class="page-link" x-text="page"></a></li>
                    <li class="page-item" :class="{disabled: page >= meta?.last_page}"><button class="page-link" @click="page++;load()">»</button></li>
                </ul>
            </nav>
        </div>
    </div>

    {{-- Modal Novo Cliente --}}
    <div class="modal fade" id="clientModal" tabindex="-1" x-ref="modal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Novo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="store">
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="form.name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" x-model="form.email" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tipo de Documento</label>
                            <select class="form-select" x-model="form.document_type">
                                <option value="cpf">CPF</option>
                                <option value="cnpj">CNPJ</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Número do Documento</label>
                            <input type="text" class="form-control" x-model="form.document_number">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Senha <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" x-model="form.password" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Telefone</label>
                            <input type="text" class="form-control" x-model="form.phone">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">WhatsApp</label>
                            <input type="text" class="form-control" x-model="form.whatsapp">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" x-model="form.status">
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                                <option value="pending">Pendente</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                            Criar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function clientsTable() {
    return {
        clients: [], meta: null, loading: false, search: '', status: '', page: 1,
        showModal: false, saving: false,
        form: { name:'', email:'', password:'', document_type:'cpf', document_number:'', phone:'', whatsapp:'', status:'active' },

        async load() {
            this.loading = true;
            const params = new URLSearchParams({ search: this.search, status: this.status, page: this.page });
            const data = await HostPanel.fetch(`/admin/clientes?${params}`, { method: 'GET', headers: { 'Accept': 'application/json' } });
            this.clients = data.data || [];
            this.meta    = data.meta || data;
            this.loading = false;
        },

        async store() {
            this.saving = true;
            const data = await HostPanel.fetch('/admin/clientes', { method:'POST', body: JSON.stringify(this.form) });
            this.saving = false;
            if (data.client) {
                bootstrap.Modal.getInstance(document.getElementById('clientModal'))?.hide();
                HostPanel.toast('Cliente criado com sucesso!');
                this.load();
            } else {
                HostPanel.toast(data.message || 'Erro ao criar cliente.', 'danger');
            }
        },

        async toggleStatus(client) {
            const data = await HostPanel.fetch(`/admin/clientes/${client.id}/status`, { method: 'POST' });
            if (data.status) { client.status = data.status; HostPanel.toast(data.message); }
        },

        async impersonate(client) {
            if (!(await HostPanel.confirm({ text: `Deseja entrar como ${client.name}?`, confirmButtonText: 'Sim, entrar' }))) return;
            const data = await HostPanel.fetch(`/admin/clientes/${client.id}/impersonar`, { method: 'POST', body: JSON.stringify({ reason: 'Suporte administrativo' }) });
            if (data.redirect) window.location.href = data.redirect;
            else HostPanel.toast(data.message, 'danger');
        },

        statusColor(s) { return {active:'success',inactive:'secondary',pending:'warning',blocked:'danger'}[s] || 'secondary'; },
        statusLabel(s) { return {active:'Ativo',inactive:'Inativo',pending:'Pendente',blocked:'Bloqueado'}[s] || s; },
        formatDate(d) { return d ? new Date(d).toLocaleDateString('pt-BR') : '—'; },

        init() {
            this.load();
            this.$watch('showModal', v => v && new bootstrap.Modal(document.getElementById('clientModal')).show());
        }
    }
}
</script>
@endpush
