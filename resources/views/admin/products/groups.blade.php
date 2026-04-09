@extends('admin.layouts.app')
@section('title', 'Grupos de Produtos')
@section('page-title', 'Gerenciar Grupos')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Produtos</a></li>
    <li class="breadcrumb-item active">Grupos</li>
@endsection

@section('content')
<div x-data="groupsManager()">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-collection me-2"></i>Grupos de Produtos</h5>
            <button class="btn btn-primary shadow-sm" @click="openModal()">
                <i class="bi bi-plus-lg me-1"></i>Novo Grupo
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="50" class="ps-4">#</th>
                            <th>Nome do Grupo</th>
                            <th>Descrição</th>
                            <th class="text-center">Produtos</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <div class="mt-2 text-muted small">Carregando grupos...</div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="(g, index) in groups" :key="g.id">
                            <tr>
                                <td class="ps-4 text-muted small" x-text="index + 1"></td>
                                <td>
                                    <div class="fw-bold text-dark" x-text="g.name"></div>
                                    <div class="text-muted extra-small" x-text="g.slug"></div>
                                </td>
                                <td class="text-muted small" x-text="g.description || 'Sem descrição'"></td>
                                <td class="text-center">
                                    <span class="badge bg-soft-primary text-primary px-3 rounded-pill" x-text="g.products_count + ' produtos'"></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill px-3" 
                                          :class="g.active ? 'bg-success' : 'bg-secondary'"
                                          x-text="g.active ? 'Ativo' : 'Inativo'"></span>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group btn-group-sm rounded shadow-sm">
                                        <button class="btn btn-white border" @click="openModal(g)" title="Editar">
                                            <i class="bi bi-pencil text-primary"></i>
                                        </button>
                                        <button class="btn btn-white border" @click="deleteGroup(g)" title="Excluir">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && groups.length === 0">
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-folder-x display-4 text-muted"></i>
                                    <p class="mt-2 text-muted">Nenhum grupo cadastrado ainda.</p>
                                    <button class="btn btn-sm btn-outline-primary" @click="openModal()">Clique aqui para criar o primeiro</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal de Grupo -->
    <div class="modal fade" id="groupModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold text-primary" x-text="form.id ? 'Editar Grupo' : 'Novo Grupo'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="save">
                    <div class="modal-body py-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nome do Grupo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.name" required placeholder="Ex: Hospedagem de Sites">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descrição</label>
                                <textarea class="form-control" rows="3" x-model="form.description" placeholder="Uma breve descrição sobre os produtos deste grupo..."></textarea>
                            </div>
                            <div class="col-12">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-3">
                                        <div class="form-check form-switch p-0 d-flex align-items-center">
                                            <input class="form-check-input" type="checkbox" id="groupActiveSw" x-model="form.active" style="margin-left: 0; width: 2.5em; height: 1.25em; cursor: pointer;">
                                            <label class="form-check-label fw-bold ms-2" for="groupActiveSw" style="cursor: pointer;">Grupo Ativo e Visível</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm" :disabled="saving">
                            <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <span x-text="form.id ? 'Salvar Alterações' : 'Criar Grupo'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.bg-soft-primary { background-color: rgba(26, 86, 219, 0.1); }
.ls-1 { letter-spacing: 0.5px; }
.extra-small { font-size: 0.7rem; }
</style>
@endsection

@push('scripts')
<script>
function groupsManager() {
    return {
        groups: [],
        loading: false,
        saving: false,
        form: { id: null, name: '', description: '', active: true },
        modal: null,

        init() {
            this.modal = new bootstrap.Modal(document.getElementById('groupModal'));
            this.load();
        },

        async load() {
            this.loading = true;
            try {
                this.groups = await HostPanel.fetch('/admin/produtos/grupos');
            } catch (e) {
                HostPanel.toast('Erro ao carregar grupos', 'danger');
            } finally {
                this.loading = false;
            }
        },

        openModal(group = null) {
            if (group) {
                this.form = { ...group };
            } else {
                this.form = { id: null, name: '', description: '', active: true };
            }
            this.modal.show();
        },

        async save() {
            this.saving = true;
            const url = this.form.id ? `/admin/produtos/grupos/${this.form.id}` : '/admin/produtos/grupos';
            const method = this.form.id ? 'PUT' : 'POST';

            try {
                const response = await HostPanel.fetch(url, {
                    method: method,
                    body: JSON.stringify(this.form)
                });
                
                HostPanel.toast(response.message || 'Salvo com sucesso!');
                this.modal.hide();
                this.load();
            } catch (e) {
                HostPanel.toast('Erro ao salvar grupo', 'danger');
            } finally {
                this.saving = false;
            }
        },

        async deleteGroup(group) {
            if (group.products_count > 0) {
                return HostPanel.toast('Este grupo possui produtos e nao pode ser excluido.', 'warning');
            }

            if (!(await HostPanel.confirm({ text: `Deseja excluir o grupo "${group.name}"?` }))) return;

            try {
                await HostPanel.fetch(`/admin/produtos/grupos/${group.id}`, { method: 'DELETE' });
                HostPanel.toast('Grupo excluido!');
                this.load();
            } catch (e) {
                HostPanel.toast('Erro ao excluir', 'danger');
            }
        }
    }
}
</script>
@endpush
