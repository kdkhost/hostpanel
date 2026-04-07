@extends('admin.layouts.app')
@section('title', 'Perfis e Permissões')
@section('page-title', 'Perfis e Permissões')
@section('breadcrumb')
    <li class="breadcrumb-item active">Permissões</li>
@endsection

@section('content')
<div x-data="permissionsIndex()">
    <div class="row g-4">

        {{-- Roles --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    Perfis de Acesso
                    <span class="badge bg-primary" x-text="roles.length"></span>
                </div>
                <div class="list-group list-group-flush">
                    <template x-if="loading">
                        <div class="list-group-item text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
                    </template>
                    <template x-for="role in roles" :key="role.id">
                        <button class="list-group-item list-group-item-action"
                                :class="selectedRole?.id === role.id ? 'active' : ''"
                                @click="selectRole(role)">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold small" :class="selectedRole?.id===role.id?'text-white':''" x-text="role.name"></div>
                                    <small :class="selectedRole?.id===role.id?'text-white opacity-75':'text-muted'"
                                           x-text="(role.permissions?.length ?? 0) + ' permissões'"></small>
                                </div>
                                <i class="bi bi-chevron-right" :class="selectedRole?.id===role.id?'text-white opacity-50':'text-muted'"></i>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Permissões do Role --}}
        <div class="col-lg-8">
            <div class="card" x-show="selectedRole">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-bold" x-text="'Permissões: ' + (selectedRole?.name ?? '')"></span>
                        <small class="text-muted ms-2" x-text="checkedCount + ' de ' + totalPermissions + ' selecionadas'"></small>
                    </div>
                    <button class="btn btn-sm btn-primary" @click="savePermissions()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>Salvar
                    </button>
                </div>
                <div class="card-body" style="max-height:600px;overflow-y:auto">
                    <template x-for="(group, key) in permissionGroups" :key="key">
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 text-capitalize" x-text="key"></div>
                                <div class="flex-1 border-top"></div>
                                <button class="btn btn-link btn-sm p-0 text-muted" @click="toggleGroup(key, group)">
                                    <span class="small" x-text="isGroupChecked(key,group) ? 'Desmarcar todos' : 'Marcar todos'"></span>
                                </button>
                            </div>
                            <div class="row g-2">
                                <template x-for="perm in group" :key="perm.id">
                                    <div class="col-md-6">
                                        <div class="form-check border rounded-2 px-3 py-2 m-0 hover-bg-light">
                                            <input class="form-check-input" type="checkbox"
                                                   :id="'perm_'+perm.id"
                                                   :checked="selectedPerms.has(perm.id)"
                                                   @change="togglePerm(perm.id, $event.target.checked)">
                                            <label class="form-check-label small w-100 cursor-pointer" :for="'perm_'+perm.id">
                                                <span class="fw-semibold" x-text="perm.name"></span>
                                            </label>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="card text-center py-5 text-muted" x-show="!selectedRole">
                <i class="bi bi-shield-lock fs-1 d-block mb-2 opacity-25"></i>
                <p>Selecione um perfil para gerenciar suas permissões.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function permissionsIndex() {
    return {
        roles: [], permissionGroups: {}, loading: false, saving: false,
        selectedRole: null, selectedPerms: new Set(),

        get checkedCount()    { return this.selectedPerms.size; },
        get totalPermissions(){ return Object.values(this.permissionGroups).reduce((s,g) => s + g.length, 0); },

        async load() {
            this.loading = true;
            const d = await HostPanel.fetch('{{ route("admin.permissions") }}');
            this.roles            = d.roles ?? [];
            this.permissionGroups = d.permissions ?? {};
            this.loading          = false;
        },

        selectRole(role) {
            this.selectedRole  = role;
            this.selectedPerms = new Set((role.permissions ?? []).map(p => p.id));
        },

        togglePerm(id, checked) {
            if (checked) this.selectedPerms.add(id);
            else         this.selectedPerms.delete(id);
            this.selectedPerms = new Set(this.selectedPerms);
        },

        isGroupChecked(key, group) {
            return group.every(p => this.selectedPerms.has(p.id));
        },

        toggleGroup(key, group) {
            const allChecked = this.isGroupChecked(key, group);
            group.forEach(p => {
                if (allChecked) this.selectedPerms.delete(p.id);
                else            this.selectedPerms.add(p.id);
            });
            this.selectedPerms = new Set(this.selectedPerms);
        },

        async savePermissions() {
            if (!this.selectedRole) return;
            this.saving = true;
            const d = await HostPanel.fetch('{{ route("admin.permissions.assign") }}', {
                method: 'POST',
                body: JSON.stringify({
                    admin_id: null,
                    role_id:  this.selectedRole.id,
                    permissions: [...this.selectedPerms],
                })
            });
            this.saving = false;
            HostPanel.toast(d.message ?? 'Permissões atualizadas!');
        },

        init() { this.load(); }
    }
}
</script>
@endpush
