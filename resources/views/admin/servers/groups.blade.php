@extends('admin.layouts.app')

@section('title', 'Grupos de Servidores')
@section('page-title', 'Grupos de Servidores')

@section('content')
<div x-data="serverGroups()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Grupos de Servidores</h4>
            <p class="text-muted small mb-0">Gerencie como suas novas contas de hospedagem sao distribuidas entre os servidores.</p>
        </div>
        <button class="btn btn-primary" @click="createGroup()">
            <i class="bi bi-plus-lg me-1"></i> Novo Grupo
        </button>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Nome do Grupo</th>
                                    <th>Tipo de Preenchimento</th>
                                    <th class="text-center">Servidores</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-4">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="group in groups" :key="group.id">
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold" x-text="group.name"></div>
                                            <div class="text-muted small" x-text="group.description || 'Sem descricao'"></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-normal" x-text="formatFillType(group.fill_type)"></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary" x-text="group.servers_count || 0"></span>
                                        </td>
                                        <td class="text-center">
                                            <span x-show="group.active" class="badge bg-success">Ativo</span>
                                            <span x-show="!group.active" class="badge bg-danger">Inativo</span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary" @click="editGroup(group)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" @click="deleteGroup(group)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="groups.length === 0">
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        Nenhum grupo de servidor encontrado.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Grupo -->
    <div class="modal fade" id="groupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" x-text="editing ? 'Editar Grupo' : 'Novo Grupo'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveGroup()">
                        <div class="mb-3">
                            <label class="form-label">Nome do Grupo</label>
                            <input type="text" class="form-control" x-model="form.name" required placeholder="Ex: Servidores Cloud BR">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descricao</label>
                            <textarea class="form-control" x-model="form.description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Preenchimento</label>
                            <select class="form-select" x-model="form.fill_type" required>
                                <option value="least_used">Menor Uso (Recomendado)</option>
                                <option value="sequential">Sequencial</option>
                                <option value="random">Aleatorio</option>
                            </select>
                            <div class="form-text small mt-1">
                                <span x-show="form.fill_type === 'least_used'">Escolhe o servidor com menos contas ativas.</span>
                                <span x-show="form.fill_type === 'sequential'">Preenche um servidor ate o limite antes de passar para o proximo.</span>
                                <span x-show="form.fill_type === 'random'">Distribui as contas aleatoriamente entre os servidores do grupo.</span>
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="form.active" id="groupActive">
                                <label class="form-check-label" for="groupActive">Grupo Ativo</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="saveGroup()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                        Salvar Grupo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function serverGroups() {
    return {
        groups: @js($groups),
        editing: false,
        saving: false,
        form: {
            id: null,
            name: '',
            description: '',
            fill_type: 'least_used',
            active: true
        },
        modal: null,

        init() {
            this.modal = new bootstrap.Modal(document.getElementById('groupModal'));
        },

        createGroup() {
            this.editing = false;
            this.form = { id: null, name: '', description: '', fill_type: 'least_used', active: true };
            this.modal.show();
        },

        editGroup(group) {
            this.editing = true;
            this.form = { ...group };
            this.modal.show();
        },

        async saveGroup() {
            if (this.saving) return;
            this.saving = true;

            try {
                const url = this.editing ? `/admin/servidores/grupos/${this.form.id}` : '{{ route("admin.servers.groups.store") }}';
                const method = this.editing ? 'PUT' : 'POST';

                const response = await HostPanel.fetch(url, {
                    method: method,
                    body: JSON.stringify(this.form)
                });

                if (response.ok) {
                    HostPanel.toast('Grupo salvo com sucesso!', 'success');
                    window.location.reload();
                } else {
                    HostPanel.toast(response.message || 'Erro ao salvar grupo', 'danger');
                }
            } catch (e) {
                HostPanel.toast('Erro na requisicao', 'danger');
            } finally {
                this.saving = false;
            }
        },

        async deleteGroup(group) {
            const result = await Swal.fire({
                title: 'Remover Grupo?',
                text: `Deseja remover o grupo "${group.name}"? Esta acao nao pode ser desfeita.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, remover',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await HostPanel.fetch(`/admin/servidores/grupos/${group.id}`, {
                    method: 'DELETE'
                });

                if (response.ok) {
                    HostPanel.toast('Grupo removido!', 'success');
                    window.location.reload();
                } else {
                    HostPanel.toast(response.message || 'Erro ao remover grupo', 'danger');
                }
            } catch (e) {
                HostPanel.toast('Erro na requisicao', 'danger');
            }
        },

        formatFillType(type) {
            const types = {
                'least_used': 'Menor Uso',
                'sequential': 'Sequencial',
                'random': 'Aleatorio'
            };
            return types[type] || type;
        }
    }
}
</script>
@endpush
