@extends('admin.layouts.app')
@section('title', 'Administradores')
@section('page-title', 'Administradores')
@section('breadcrumb')
    <li class="breadcrumb-item active">Administradores</li>
@endsection

@section('content')
<div x-data="adminsIndex()">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <input type="text" class="form-control form-control-sm" style="width:220px" placeholder="Buscar por nome ou e-mail..."
               x-model.debounce.400="search" @input="load()">
        <button class="btn btn-primary btn-sm" @click="openCreate()">
            <i class="bi bi-person-plus me-1"></i>Novo Administrador
        </button>
    </div>

    <div class="row g-3">
        <template x-if="loading">
            <div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>
        </template>
        <template x-for="admin in filteredAdmins" :key="admin.id">
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-black flex-shrink-0"
                                 style="width:46px;height:46px;font-size:1.1rem"
                                 x-text="admin.name.charAt(0).toUpperCase()"></div>
                            <div class="flex-1 min-w-0">
                                <div class="fw-bold text-dark" x-text="admin.name"></div>
                                <div class="text-muted small" x-text="admin.email"></div>
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    <template x-for="role in (admin.roles ?? [])" :key="role.id">
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 small"
                                              x-text="role.name"></span>
                                    </template>
                                </div>
                                <div class="small mt-2">
                                    <span class="badge" :class="admin.status === 'active' ? 'bg-success' : 'bg-secondary'"
                                          x-text="admin.status === 'active' ? 'Ativo' : 'Inativo'"></span>
                                    <span class="text-muted ms-2" x-text="(admin.assigned_tickets ?? 0) + ' tickets atribuídos'"></span>
                                </div>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <button class="btn btn-sm btn-outline-primary" @click="openEdit(admin)"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" @click="deleteAdmin(admin)"
                                        x-show="admin.id !== {{ auth('admin')->id() }}"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template x-if="!loading && filteredAdmins.length === 0">
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>Nenhum administrador encontrado.
            </div>
        </template>
    </div>

    {{-- Modal --}}
    <div class="modal fade" id="adminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="form.id ? 'Editar Administrador' : 'Novo Administrador'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="form.name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" x-model="form.email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Senha <span x-show="!form.id" class="text-danger">*</span></label>
                            <input type="password" class="form-control" x-model="form.password"
                                   :placeholder="form.id ? 'Deixe em branco para não alterar' : 'Senha'">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Perfil <span class="text-danger">*</span></label>
                            <select class="form-select" x-model="form.role">
                                <option value="">Selecione...</option>
                                <template x-for="r in roles" :key="r.id">
                                    <option :value="r.name" x-text="r.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-12" x-show="form.id">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" x-model="form.status">
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" @click="saveAdmin()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                        <span x-text="form.id ? 'Atualizar' : 'Criar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function adminsIndex() {
    return {
        admins: [], roles: [], loading: false, saving: false, search: '',
        form: { id: null, name: '', email: '', password: '', role: '', status: 'active' },

        get filteredAdmins() {
            if (!this.search) return this.admins;
            const s = this.search.toLowerCase();
            return this.admins.filter(a => a.name.toLowerCase().includes(s) || a.email.toLowerCase().includes(s));
        },

        async load() {
            this.loading = true;
            this.admins = await HostPanel.fetch('{{ route("admin.admins.index") }}');
            this.loading = false;
        },

        async loadRoles() {
            const d = await HostPanel.fetch('{{ url("admin/permissoes/roles") }}');
            this.roles = d.roles ?? d ?? [];
        },

        openCreate() {
            this.form = { id: null, name: '', email: '', password: '', role: '', status: 'active' };
            new bootstrap.Modal(document.getElementById('adminModal')).show();
        },

        openEdit(a) {
            this.form = { id: a.id, name: a.name, email: a.email, password: '', role: a.roles?.[0]?.name ?? '', status: a.status };
            new bootstrap.Modal(document.getElementById('adminModal')).show();
        },

        async saveAdmin() {
            this.saving = true;
            const isEdit = !!this.form.id;
            const url    = isEdit ? `{{ url('admin/administradores') }}/${this.form.id}` : '{{ route("admin.admins.store") }}';
            const d = await HostPanel.fetch(url, { method: isEdit ? 'PUT' : 'POST', body: JSON.stringify(this.form) });
            this.saving = false;
            HostPanel.toast(d.message);
            if (d.admin) { bootstrap.Modal.getInstance(document.getElementById('adminModal'))?.hide(); await this.load(); }
        },

        async deleteAdmin(a) {
            if (!(await HostPanel.confirm({ text: `Remover administrador "${a.name}"?`, confirmButtonText: 'Sim, remover' }))) return;
            const d = await HostPanel.fetch(`{{ url('admin/administradores') }}/${a.id}`, { method: 'DELETE' });
            HostPanel.toast(d.message);
            await this.load();
        },

        init() { this.load(); this.loadRoles(); }
    }
}
</script>
@endpush
