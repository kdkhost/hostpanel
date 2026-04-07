@extends('admin.layouts.app')
@section('title', 'Domínios')
@section('page-title', 'Domínios')
@section('breadcrumb')
    <li class="breadcrumb-item active">Domínios</li>
@endsection

@section('content')
<div x-data="domainsIndex()">

    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" style="width:220px" placeholder="Buscar domínio..."
                   x-model.debounce.400="search" @input="page=1;load()">
            <select class="form-select form-select-sm" style="width:130px" x-model="statusFilter" @change="page=1;load()">
                <option value="">Todos</option>
                <option value="active">Ativo</option>
                <option value="expired">Expirado</option>
                <option value="pending">Pendente</option>
                <option value="cancelled">Cancelado</option>
            </select>
        </div>
        <button class="btn btn-sm btn-outline-secondary" @click="showTlds=!showTlds">
            <i class="bi bi-list-ul me-1"></i>TLDs Configurados
        </button>
    </div>

    {{-- Tabela Domínios --}}
    <div class="card mb-4" x-show="!showTlds">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Domínio</th>
                        <th>Cliente</th>
                        <th>Registrador</th>
                        <th>Registro</th>
                        <th>Expiração</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                    </template>
                    <template x-for="domain in domains" :key="domain.id">
                        <tr>
                            <td>
                                <div class="fw-semibold" x-text="domain.name"></div>
                                <code class="text-muted" style="font-size:.7rem" x-text="'.' + domain.tld"></code>
                            </td>
                            <td>
                                <a :href="`/admin/clientes/${domain.client?.id}`" class="text-decoration-none small" x-text="domain.client?.name ?? '—'"></a>
                                <div class="text-muted" style="font-size:.7rem" x-text="domain.client?.email ?? ''"></div>
                            </td>
                            <td class="text-muted" x-text="domain.registrar ?? '—'"></td>
                            <td class="text-muted small" x-text="fmtDate(domain.registration_date)"></td>
                            <td>
                                <span :class="isExpiringSoon(domain.expiry_date) ? 'text-danger fw-semibold' : 'text-muted'"
                                      x-text="fmtDate(domain.expiry_date)"></span>
                                <div class="text-danger" style="font-size:.7rem" x-show="isExpiringSoon(domain.expiry_date)">
                                    <i class="bi bi-exclamation-triangle"></i> Vence em breve
                                </div>
                            </td>
                            <td>
                                <span class="badge" :class="{
                                    'bg-success': domain.status === 'active',
                                    'bg-danger':  domain.status === 'expired',
                                    'bg-warning text-dark': domain.status === 'pending',
                                    'bg-secondary': !['active','expired','pending'].includes(domain.status),
                                }" x-text="{ active:'Ativo', expired:'Expirado', pending:'Pendente', cancelled:'Cancelado', transferring:'Transferindo' }[domain.status] ?? domain.status"></span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" title="Detalhes" @click="viewDomain(domain)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && domains.length === 0">
                        <tr><td colspan="7" class="text-center text-muted py-5">Nenhum domínio encontrado.</td></tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2" x-show="meta && meta.total > 0">
            <small class="text-muted" x-text="`${meta?.from ?? 0}–${meta?.to ?? 0} de ${meta?.total ?? 0}`"></small>
            <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-secondary" :disabled="page===1" @click="page--;load()">‹</button>
                <span class="btn btn-sm btn-primary disabled" x-text="page"></span>
                <button class="btn btn-sm btn-outline-secondary" :disabled="page>=(meta?.last_page??1)" @click="page++;load()">›</button>
            </div>
        </div>
    </div>

    {{-- TLDs --}}
    <div class="card" x-show="showTlds">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">TLDs Configurados</span>
            <button class="btn btn-sm btn-primary" @click="openAddTld()"><i class="bi bi-plus-lg me-1"></i>Adicionar TLD</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr><th>TLD</th><th>Registrador</th><th class="text-end">Registro</th><th class="text-end">Renovação</th><th class="text-center">Status</th></tr>
                </thead>
                <tbody>
                    <template x-for="tld in tlds" :key="tld.id">
                        <tr>
                            <td class="fw-bold font-monospace" x-text="tld.tld"></td>
                            <td x-text="tld.registrar"></td>
                            <td class="text-end" x-text="'R$ ' + parseFloat(tld.register_price).toFixed(2)"></td>
                            <td class="text-end" x-text="'R$ ' + parseFloat(tld.renew_price || tld.register_price).toFixed(2)"></td>
                            <td class="text-center">
                                <span class="badge" :class="tld.active ? 'bg-success' : 'bg-secondary'" x-text="tld.active ? 'Ativo' : 'Inativo'"></span>
                            </td>
                        </tr>
                    </template>
                    <template x-if="tlds.length === 0"><tr><td colspan="5" class="text-center text-muted py-3">Nenhum TLD configurado.</td></tr></template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Adicionar TLD --}}
    <div class="modal fade" id="tldModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Adicionar TLD</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">TLD <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="tldForm.tld" placeholder=".com.br">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Registrador <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="tldForm.registrar" placeholder="RegistroBR, GoDaddy...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Preço Registro (R$)</label>
                            <input type="number" step="0.01" class="form-control" x-model="tldForm.register_price">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Preço Renovação (R$)</label>
                            <input type="number" step="0.01" class="form-control" x-model="tldForm.renew_price">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" @click="saveTld()">Salvar TLD</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function domainsIndex() {
    return {
        domains: [], tlds: [], meta: null, loading: false, page: 1,
        search: '', statusFilter: '', showTlds: false,
        tldForm: { tld:'', registrar:'', register_price:0, renew_price:0 },

        async load() {
            this.loading = true;
            const p = new URLSearchParams({ search: this.search, status: this.statusFilter, page: this.page });
            const d = await HostPanel.fetch(`{{ route('admin.domains.index') }}?${p}`);
            this.domains = d.data ?? [];
            this.meta    = d.meta ?? null;
            this.loading = false;
        },

        async loadTlds() {
            this.tlds = await HostPanel.fetch('{{ route("admin.domains.tlds") }}');
        },

        openAddTld() { this.tldForm = {tld:'',registrar:'',register_price:0,renew_price:0}; new bootstrap.Modal(document.getElementById('tldModal')).show(); },

        async saveTld() {
            const d = await HostPanel.fetch('{{ route("admin.domains.tlds.store") }}', { method:'POST', body: JSON.stringify(this.tldForm) });
            HostPanel.toast(d.message);
            if (d.tld) { bootstrap.Modal.getInstance(document.getElementById('tldModal'))?.hide(); await this.loadTlds(); }
        },

        viewDomain(d) { HostPanel.toast(`Domínio: ${d.name}`, 'info'); },
        isExpiringSoon(d) { if (!d) return false; const diff = (new Date(d) - Date.now()) / 86400000; return diff < 30 && diff > 0; },
        fmtDate(d) { return d ? new Date(d).toLocaleDateString('pt-BR') : '—'; },

        init() { this.load(); this.loadTlds(); }
    }
}
</script>
@endpush
