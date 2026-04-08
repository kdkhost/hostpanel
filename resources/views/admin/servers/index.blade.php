@extends('admin.layouts.app')
@section('title', 'Servidores')
@section('page-title', 'Servidores')
@section('breadcrumb')
    <li class="breadcrumb-item active">Servidores</li>
@endsection

@section('content')
<div x-data="serversTable()">
    <div class="alert alert-warning d-flex justify-content-between align-items-start gap-3 flex-wrap" x-show="!loading && requiresCronAttention()">
        <div>
            <div class="fw-semibold mb-1">Alguns servidores estao sem monitoramento recente.</div>
            <div class="small">
                Isso normalmente acontece quando o cron principal do Laravel Scheduler ainda nao foi cadastrado na hospedagem
                ou quando o health check nao roda ha mais de 15 minutos.
            </div>
        </div>
        <a href="{{ route('admin.settings.cron') }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-terminal me-1"></i>Ver cron jobs
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="small text-muted">
            <span class="fw-semibold text-dark" x-text="servers.length"></span> servidor(es) cadastrados
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.settings.cron') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-clock-history me-1"></i>Cron jobs
            </a>
            <button class="btn btn-primary btn-sm" @click="openCreate()">
                <i class="bi bi-plus-lg me-1"></i>Adicionar servidor
            </button>
        </div>
    </div>

    <template x-if="loading">
        <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
    </template>

    <div class="row g-3" x-show="!loading">
        <template x-for="server in servers" :key="server.id">
            <div class="col-md-6 col-xl-4">
                <div class="card h-100" :style="`border-left: 4px solid ${statusColor(server)}`">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h6 class="fw-bold mb-0" x-text="server.name"></h6>
                                <small class="text-muted d-block" x-text="server.hostname"></small>
                                <small class="text-muted" x-text="`Ultima verificacao: ${lastCheckLabel(server)}`"></small>
                            </div>
                            <span class="badge" :class="statusBadgeClass(server)" x-text="statusLabel(server)"></span>
                        </div>

                        <div class="row g-2 mb-3 text-center">
                            <div class="col-4">
                                <div class="bg-light rounded p-2">
                                    <div class="fw-bold" x-text="server.services_count ?? 0"></div>
                                    <div class="text-muted" style="font-size:.7rem">Contas</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-light rounded p-2">
                                    <div class="fw-bold" x-text="metricLabel(server.latest_health_log?.cpu_usage, '%')"></div>
                                    <div class="text-muted" style="font-size:.7rem">CPU</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-light rounded p-2">
                                    <div class="fw-bold" x-text="metricLabel(server.latest_health_log?.ram_usage, '%')"></div>
                                    <div class="text-muted" style="font-size:.7rem">RAM</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <small>Capacidade</small>
                                <small x-text="capacityLabel(server)"></small>
                            </div>
                            <div class="progress" style="height:6px">
                                <div class="progress-bar bg-primary" :style="`width:${capacityPercent(server)}%`"></div>
                            </div>
                        </div>

                        <div class="text-muted small mb-3">
                            <span><i class="bi bi-hdd me-1"></i>Modulo: <strong x-text="moduleLabel(server.module)"></strong></span>
                        </div>

                        <div class="mt-auto d-flex gap-2 flex-wrap">
                            <a :href="`/admin/servidores/${server.id}`" class="btn btn-sm btn-outline-primary flex-grow-1">
                                <i class="bi bi-eye me-1"></i>Detalhes
                            </a>
                            <button class="btn btn-sm btn-outline-warning" @click="openEdit(server)" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="healthCheck(server)" title="Health check">
                                <i class="bi bi-activity"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-info" @click="testConn(server)" title="Testar conexao">
                                <i class="bi bi-wifi"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="!loading && servers.length === 0">
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-server fs-1 d-block mb-2 opacity-25"></i>
                Nenhum servidor cadastrado.
                <div class="mt-3">
                    <button class="btn btn-primary" @click="openCreate()"><i class="bi bi-plus-lg me-1"></i>Adicionar servidor</button>
                </div>
            </div>
        </template>
    </div>

    <div class="modal fade" id="serverModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="editingId ? 'Editar servidor' : 'Adicionar servidor'"></h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="saveServer">
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome *</label>
                            <input type="text" class="form-control" x-model="serverForm.name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hostname *</label>
                            <input type="text" class="form-control" x-model="serverForm.hostname" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">IP *</label>
                            <input type="text" class="form-control" x-model="serverForm.ip_address" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">IP secundario</label>
                            <input type="text" class="form-control" x-model="serverForm.ip_address_secondary">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Porta</label>
                            <input type="number" class="form-control" x-model="serverForm.port" min="1" max="65535">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" x-model="serverForm.type">
                                <option value="shared">Shared</option>
                                <option value="reseller">Reseller</option>
                                <option value="vps">VPS</option>
                                <option value="dedicated">Dedicated</option>
                                <option value="other">Outro</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Modulo</label>
                            <select class="form-select" x-model="serverForm.module" @change="applyModuleDefaults()">
                                <option value="whm">WHM/cPanel</option>
                                <option value="whmsonic">WHMSonic</option>
                                <option value="cpanel">cPanel</option>
                                <option value="aapanel">AAPanel</option>
                                <option value="btpanel">BT Panel</option>
                                <option value="plesk">Plesk</option>
                                <option value="directadmin">DirectAdmin</option>
                                <option value="ispconfig">ISPConfig</option>
                                <option value="blesta">Blesta</option>
                                <option value="cyberpanel">CyberPanel</option>
                                <option value="webuzo">Webuzo</option>
                                <option value="hestia">HestiaCP</option>
                                <option value="virtualmin">Virtualmin</option>
                                <option value="none">Nenhum</option>
                            </select>
                        </div>

                        <div class="col-12" x-show="moduleHelpText()">
                            <div class="alert alert-info py-2 small mb-0">
                                <span x-text="moduleHelpText()"></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Usuario <span x-show="requiresUsername()">*</span></label>
                            <input type="text" class="form-control" x-model="serverForm.username" :required="requiresUsername()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">API Key <span x-show="requiresApiKey()">*</span></label>
                            <input type="text" class="form-control" x-model="serverForm.api_key" :placeholder="editingId ? 'Preencha apenas para alterar' : ''" :required="requiresApiKey() && !editingId">
                        </div>
                        <div class="col-md-6" x-show="requiresPassword()">
                            <label class="form-label">Senha <span x-show="requiresPassword()">*</span></label>
                            <input type="password" class="form-control" x-model="serverForm.password" :placeholder="editingId ? 'Preencha apenas para alterar' : ''" :required="requiresPassword() && !editingId">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max. contas</label>
                            <input type="number" class="form-control" x-model="serverForm.max_accounts" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">NS primario</label>
                            <input type="text" class="form-control" x-model="serverForm.nameserver1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">NS secundario</label>
                            <input type="text" class="form-control" x-model="serverForm.nameserver2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">NS terciario</label>
                            <input type="text" class="form-control" x-model="serverForm.nameserver3">
                        </div>
                        <div class="col-12">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" x-model="serverForm.active">
                                        <label class="form-check-label">Servidor ativo</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" x-model="serverForm.secure">
                                        <label class="form-check-label">Usar HTTPS na API</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <span x-text="editingId ? 'Salvar alteracoes' : 'Cadastrar servidor'"></span>
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
        servers: [],
        loading: true,
        saving: false,
        editingId: null,
        serverForm: {},
        moduleCatalog: @js(\App\Services\ServerModules\ServerModuleManager::catalog()),

        resetForm() {
            this.editingId = null;
            this.serverForm = {
                name: '',
                hostname: '',
                ip_address: '',
                ip_address_secondary: '',
                port: 2087,
                type: 'shared',
                module: 'whm',
                username: '',
                api_key: '',
                password: '',
                max_accounts: 500,
                nameserver1: '',
                nameserver2: '',
                nameserver3: '',
                active: true,
                secure: true,
            };
        },

        modal() {
            return bootstrap.Modal.getOrCreateInstance(document.getElementById('serverModal'));
        },

        normalizeModule(module) {
            return String(module || 'none').toLowerCase().trim() || 'none';
        },

        moduleLabel(module) {
            return this.moduleCatalog[this.normalizeModule(module)]?.label || module;
        },

        statusKey(server) {
            return server.latest_health_log?.network_status || server.status || 'unknown';
        },

        statusLabel(server) {
            return {
                online: 'Online',
                degraded: 'Degradado',
                offline: 'Offline',
                warning: 'Instavel',
                unknown: 'Pendente',
            }[this.statusKey(server)] || 'Pendente';
        },

        statusBadgeClass(server) {
            return {
                online: 'bg-success',
                degraded: 'bg-warning text-dark',
                offline: 'bg-danger',
                warning: 'bg-warning text-dark',
                unknown: 'bg-secondary',
            }[this.statusKey(server)] || 'bg-secondary';
        },

        statusColor(server) {
            return {
                online: '#10b981',
                degraded: '#f59e0b',
                offline: '#ef4444',
                warning: '#f59e0b',
                unknown: '#94a3b8',
            }[this.statusKey(server)] || '#94a3b8';
        },

        metricLabel(value, suffix = '') {
            return value == null ? '-' : `${Number(value).toFixed(0)}${suffix}`;
        },

        capacityPercent(server) {
            if (!server.max_accounts) {
                return 0;
            }

            return Math.min(((server.services_count || 0) / server.max_accounts) * 100, 100);
        },

        capacityLabel(server) {
            return `${server.services_count || 0} / ${server.max_accounts || 'sem limite'}`;
        },

        lastCheckLabel(server) {
            if (server.latest_health_log?.checked_at) {
                return new Date(server.latest_health_log.checked_at).toLocaleString('pt-BR');
            }

            if (server.last_check_at) {
                return new Date(server.last_check_at).toLocaleString('pt-BR');
            }

            return 'sem verificacao';
        },

        requiresCronAttention() {
            return this.servers.some((server) => {
                const lastCheck = server.latest_health_log?.checked_at || server.last_check_at;
                if (!lastCheck) {
                    return true;
                }

                const diffMinutes = (Date.now() - new Date(lastCheck).getTime()) / 60000;
                return Number.isFinite(diffMinutes) && diffMinutes > 15;
            });
        },

        requiresUsername() {
            return !!this.moduleCatalog[this.normalizeModule(this.serverForm.module)]?.requires_username;
        },

        requiresApiKey() {
            return !!this.moduleCatalog[this.normalizeModule(this.serverForm.module)]?.requires_api_key;
        },

        requiresPassword() {
            return !!this.moduleCatalog[this.normalizeModule(this.serverForm.module)]?.requires_password;
        },

        usesAaPanel() {
            return ['aapanel', 'btpanel'].includes(this.normalizeModule(this.serverForm.module));
        },

        moduleHelpText() {
            if (this.usesAaPanel()) {
                return 'Use a API Key do painel. Para BT Panel, o sistema usa a mesma integracao do AAPanel.';
            }

            if (this.requiresApiKey() && this.requiresUsername()) {
                return 'Este modulo usa usuario e API Key/token para validar conexao e operacoes automaticas.';
            }

            if (this.requiresUsername() && this.requiresPassword()) {
                return 'Este modulo trabalha com usuario e senha do painel. A senha pode ficar em branco na edicao para manter a atual.';
            }

            if (this.requiresApiKey()) {
                return 'Preencha a API Key ou token do painel para habilitar os testes de conectividade.';
            }

            return '';
        },

        applyModuleDefaults() {
            const defaultPort = Number(this.moduleCatalog[this.normalizeModule(this.serverForm.module)]?.default_port || 2087);
            const knownPorts = Object.values(this.moduleCatalog)
                .map((definition) => Number(definition.default_port || 0))
                .filter((port) => port > 0);

            if (!this.serverForm.port || knownPorts.includes(Number(this.serverForm.port))) {
                this.serverForm.port = defaultPort;
            }

            if (!this.requiresUsername()) {
                this.serverForm.username = '';
            }

            if (!this.requiresApiKey()) {
                this.serverForm.api_key = '';
            }

            if (!this.requiresPassword()) {
                this.serverForm.password = '';
            }
        },

        openCreate() {
            this.resetForm();
            this.modal().show();
        },

        openEdit(server) {
            this.editingId = server.id;
            this.serverForm = {
                name: server.name || '',
                hostname: server.hostname || '',
                ip_address: server.ip_address || '',
                ip_address_secondary: server.ip_address_secondary || '',
                port: server.port || 2087,
                type: server.type || 'shared',
                module: server.module || 'whm',
                username: server.username || '',
                api_key: '',
                password: '',
                max_accounts: server.max_accounts ?? 0,
                nameserver1: server.nameserver1 || '',
                nameserver2: server.nameserver2 || '',
                nameserver3: server.nameserver3 || '',
                active: !!server.active,
                secure: !!server.secure,
            };
            this.modal().show();
        },

        async load() {
            const data = await HostPanel.fetch('/admin/servidores');
            this.servers = Array.isArray(data) ? data : (data.data || []);
            this.loading = false;
        },

        async healthCheck(server) {
            HostPanel.toast(`Executando health check em ${server.name}...`, 'info');
            const data = await HostPanel.fetch(`/admin/servidores/${server.id}/health-check`, { method: 'POST' });
            HostPanel.toast(data.message || 'Health check executado.');
            this.load();
        },

        async testConn(server) {
            const data = await HostPanel.fetch(`/admin/servidores/${server.id}/testar`, { method: 'POST' });
            HostPanel.toast(data.message, data.success ? 'success' : 'danger');
        },

        async saveServer() {
            this.saving = true;
            const method = this.editingId ? 'PUT' : 'POST';
            const url = this.editingId ? `/admin/servidores/${this.editingId}` : '/admin/servidores';
            const payload = {
                ...this.serverForm,
                module: this.normalizeModule(this.serverForm.module),
            };

            const data = await HostPanel.fetch(url, {
                method,
                body: JSON.stringify(payload),
            });
            this.saving = false;

            if (!data.ok || !data.server) {
                const message = typeof data.errors === 'object'
                    ? Object.values(data.errors).flat().join(', ')
                    : (data.message || 'Nao foi possivel salvar o servidor.');
                HostPanel.toast(message, 'danger');
                return;
            }

            this.modal().hide();
            HostPanel.toast(data.message || 'Servidor salvo com sucesso!');
            this.load();
        },

        init() {
            this.resetForm();
            this.load();
        },
    };
}
</script>
@endpush
