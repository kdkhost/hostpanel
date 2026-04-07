@extends('admin.layouts.app')
@section('title', 'Servidores')
@section('page-title', 'Servidores')
@section('breadcrumb')
    <li class="breadcrumb-item active">Servidores</li>
@endsection

@section('content')
<div x-data="serversTable()">
    <div class="row g-3 mb-4" x-show="!loading">
        <template x-for="s in servers" :key="s.id">
            <div class="col-md-6 col-xl-4">
                <div class="card h-100" :style="`border-left: 4px solid ${s.status==='online'?'#10b981':'#ef4444'}`">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-0" x-text="s.name"></h6>
                                <small class="text-muted" x-text="s.hostname"></small>
                            </div>
                            <span :class="`badge bg-${s.status==='online'?'success':'danger'}`" x-text="s.status==='online'?'Online':'Offline'"></span>
                        </div>
                        <div class="row g-1 mb-3 text-center">
                            <div class="col-4">
                                <div class="bg-light rounded p-1">
                                    <div class="fw-bold" x-text="s.services_count??0"></div>
                                    <div class="text-muted" style="font-size:.7rem">Contas</div>
                                </div>
                            </div>
                            <div class="col-4" x-show="s.latest_health_log">
                                <div class="bg-light rounded p-1">
                                    <div class="fw-bold" x-text="(s.latest_health_log?.cpu_usage??0).toFixed(0)+'%'"></div>
                                    <div class="text-muted" style="font-size:.7rem">CPU</div>
                                </div>
                            </div>
                            <div class="col-4" x-show="s.latest_health_log">
                                <div class="bg-light rounded p-1">
                                    <div class="fw-bold" x-text="(s.latest_health_log?.memory_usage??0).toFixed(0)+'%'"></div>
                                    <div class="text-muted" style="font-size:.7rem">RAM</div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <small>Capacidade</small>
                                <small x-text="`${s.services_count??0} / ${s.max_accounts??'∞'}`"></small>
                            </div>
                            <div class="progress" style="height:6px">
                                <div class="progress-bar bg-primary" :style="`width:${s.max_accounts ? Math.min((s.services_count/s.max_accounts)*100,100) : 0}%`"></div>
                            </div>
                        </div>
                        <div class="text-muted small mb-3">
                            <span><i class="bi bi-hdd me-1"></i>Módulo: <strong x-text="s.module"></strong></span>
                        </div>
                        <div class="d-flex gap-2">
                            <a :href="`/admin/servidores/${s.id}`" class="btn btn-sm btn-outline-primary flex-grow-1"><i class="bi bi-eye me-1"></i>Detalhes</a>
                            <button class="btn btn-sm btn-outline-secondary" @click="healthCheck(s)" title="Health Check"><i class="bi bi-activity"></i></button>
                            <button class="btn btn-sm btn-outline-info" @click="testConn(s)" title="Testar Conexão"><i class="bi bi-wifi"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template x-if="!loading && servers.length === 0">
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-server fs-1 d-block mb-2 opacity-25"></i>
                Nenhum servidor cadastrado.
                <div class="mt-3"><button class="btn btn-primary" @click="showAddModal=true"><i class="bi bi-plus-lg me-1"></i>Adicionar Servidor</button></div>
            </div>
        </template>
    </div>

    <div x-show="!loading && servers.length > 0" class="mb-3">
        <button class="btn btn-primary" @click="showAddModal=true"><i class="bi bi-plus-lg me-1"></i>Adicionar Servidor</button>
    </div>

    <template x-if="loading">
        <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
    </template>

    {{-- Modal Adicionar Servidor --}}
    <div class="modal fade" id="serverModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Adicionar Servidor</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <form @submit.prevent="storeServer">
                    <div class="modal-body row g-3">
                        <div class="col-md-6"><label class="form-label">Nome *</label><input type="text" class="form-control" x-model="serverForm.name" required></div>
                        <div class="col-md-6"><label class="form-label">Hostname *</label><input type="text" class="form-control" x-model="serverForm.hostname" required></div>
                        <div class="col-md-4"><label class="form-label">IP *</label><input type="text" class="form-control" x-model="serverForm.ip_address" required></div>
                        <div class="col-md-2"><label class="form-label">Porta</label><input type="number" class="form-control" x-model="serverForm.port" value="2087"></div>
                        <div class="col-md-3"><label class="form-label">Tipo</label>
                            <select class="form-select" x-model="serverForm.type"><option value="shared">Shared</option><option value="reseller">Reseller</option><option value="vps">VPS</option></select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Módulo</label>
                            <select class="form-select" x-model="serverForm.module">
                                <option value="whm">WHM/cPanel</option>
                                <option value="aapanel">AAPanel (宝塔)</option>
                                <option value="btpanel">BT Panel</option>
                                <option value="plesk">Plesk</option>
                                <option value="directadmin">DirectAdmin</option>
                                <option value="none">Nenhum</option>
                            </select>
                        </div>
                        {{-- Campos específicos de módulo --}}
                        <div class="col-12" x-show="serverForm.module === 'aapanel' || serverForm.module === 'btpanel'">
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>AAPanel:</strong> Informe a <strong>API Key</strong> do painel (Configurações → API).
                                Porta padrão: <strong>8888</strong>. Deixe o campo Usuário em branco.
                            </div>
                        </div>
                        <div class="col-md-6"><label class="form-label">Usuário WHM *</label><input type="text" class="form-control" x-model="serverForm.username" required></div>
                        <div class="col-md-6"><label class="form-label">API Key *</label><input type="text" class="form-control" x-model="serverForm.api_key" required></div>
                        <div class="col-md-4"><label class="form-label">Máx. Contas</label><input type="number" class="form-control" x-model="serverForm.max_accounts"></div>
                        <div class="col-md-4"><label class="form-label">NS Primário</label><input type="text" class="form-control" x-model="serverForm.nameserver1"></div>
                        <div class="col-md-4"><label class="form-label">NS Secundário</label><input type="text" class="form-control" x-model="serverForm.nameserver2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>Salvar
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
function serversTable() {
    return {
        servers: [], loading: true, saving: false, showAddModal: false,
        serverForm: { name:'', hostname:'', ip_address:'', port:2087, type:'shared', module:'whm', username:'', api_key:'', max_accounts:500, nameserver1:'', nameserver2:'' },

        async load() {
            const d = await HostPanel.fetch('/admin/servidores');
            this.servers = Array.isArray(d) ? d : d.data || [];
            this.loading = false;
        },

        async healthCheck(s) {
            HostPanel.toast(`Health check iniciado para ${s.name}...`, 'info');
            await HostPanel.fetch(`/admin/servidores/${s.id}/health-check`, { method:'POST' });
        },

        async testConn(s) {
            const d = await HostPanel.fetch(`/admin/servidores/${s.id}/testar`, { method:'POST' });
            HostPanel.toast(d.message, d.success ? 'success' : 'danger');
        },

        async storeServer() {
            this.saving = true;
            const d = await HostPanel.fetch('/admin/servidores', { method:'POST', body: JSON.stringify(this.serverForm) });
            this.saving = false;
            if (d.server) {
                bootstrap.Modal.getInstance(document.getElementById('serverModal'))?.hide();
                HostPanel.toast('Servidor adicionado com sucesso!');
                this.load();
            } else { HostPanel.toast(d.message || 'Erro.', 'danger'); }
        },

        init() {
            this.load();
            this.$watch('showAddModal', v => v && new bootstrap.Modal(document.getElementById('serverModal')).show());
        }
    }
}
</script>
@endpush
