@extends('admin.layouts.app')
@section('title', 'Gateways de Pagamento')
@section('page-title', 'Gateways de Pagamento')
@section('breadcrumb')
    <li class="breadcrumb-item active">Gateways</li>
@endsection

@section('content')
<div x-data="gatewaysIndex()">

    <div class="row g-4" x-show="!loading">
        <template x-for="gw in gateways" :key="gw.id">
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border" :class="gw.active ? 'border-success' : 'border-0'">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-4" x-text="{ pix:'⚡', boleto:'📄', credit_card:'💳', paypal:'🔵', mercadopago:'🟢', credit_balance:'💰', pagseguro:'🟠', paghiper:'🔶', efirpro:'🟣', bancointer:'🟠', bancobrasil:'🟡', pagbank:'🔵' }[gw.slug] ?? '💳'"></span>
                            <div>
                                <div class="fw-bold" x-text="gw.name"></div>
                                <code class="small text-muted" x-text="gw.slug"></code>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" :checked="gw.active" @change="toggleActive(gw)">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 text-center mb-3">
                            <div class="col-4">
                                <div class="small text-muted">Modo</div>
                                <span class="badge" :class="gw.test_mode ? 'bg-warning text-dark' : 'bg-success'" x-text="gw.test_mode ? 'Teste' : 'Produção'"></span>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">Taxa Fixa</div>
                                <div class="fw-semibold small" x-text="gw.fee_fixed ? 'R$ ' + parseFloat(gw.fee_fixed).toFixed(2) : '—'"></div>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">Taxa %</div>
                                <div class="fw-semibold small" x-text="gw.fee_percentage ? gw.fee_percentage + '%' : '—'"></div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a :href="`{{ url('admin/gateways') }}/${gw.id}/configurar`"
                               class="btn btn-sm btn-outline-primary flex-grow-1">
                                <i class="bi bi-gear me-1"></i>Configurar
                            </a>
                            <button class="btn btn-sm btn-outline-secondary" @click="testGateway(gw)" title="Testar">
                                <i class="bi bi-wifi"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-footer bg-light py-2 small text-muted text-center" x-show="gw.active">
                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Gateway Ativo</span>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="!loading && gateways.length === 0">
            <div class="col-12 text-center py-5 text-muted">Nenhum gateway configurado.</div>
        </template>
    </div>

    <div x-show="loading" class="text-center py-5"><div class="spinner-border text-primary"></div></div>

    {{-- Modal Edição --}}
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" x-show="editing">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="'Configurar ' + (editing?.name ?? '')"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Modo</label>
                            <select class="form-select" x-model="form.test_mode">
                                <option :value="false">Produção</option>
                                <option :value="true">Teste/Sandbox</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Taxa Fixa (R$)</label>
                            <input type="number" step="0.01" class="form-control" x-model="form.fee_fixed">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Taxa (%)</label>
                            <input type="number" step="0.01" class="form-control" x-model="form.fee_percentage">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Ordem</label>
                            <input type="number" class="form-control" x-model="form.sort_order">
                        </div>

                        {{-- Configurações específicas --}}
                        <div class="col-12" x-show="editing && editing.slug !== 'credit_balance'">
                            <hr class="my-1"><div class="fw-semibold mb-3">Credenciais de API</div>
                            <template x-for="(val, key) in (form.settings ?? {})" :key="key">
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold text-capitalize" x-text="key.replace(/_/g,' ')"></label>
                                    <input type="text" class="form-control form-control-sm"
                                           :value="val" @input="form.settings[key] = $event.target.value">
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" @click="saveGateway()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function gatewaysIndex() {
    return {
        gateways: [], loading: false, editing: null, saving: false,
        form: {},

        async load() {
            this.loading = true;
            this.gateways = await HostPanel.fetch('{{ route("admin.gateways.index") }}');
            this.loading = false;
        },

        openEdit(gw) {
            this.editing = gw;
            this.form = { test_mode: gw.test_mode, fee_fixed: gw.fee_fixed, fee_percentage: gw.fee_percentage, sort_order: gw.sort_order ?? 0, settings: { ...(gw.settings_decrypted ?? {}) } };
            new bootstrap.Modal(document.getElementById('editModal')).show();
        },

        async saveGateway() {
            this.saving = true;
            const d = await HostPanel.fetch(`{{ url('admin/gateways') }}/${this.editing.id}`, { method:'PUT', body: JSON.stringify(this.form) });
            this.saving = false;
            HostPanel.toast(d.message);
            if (d.gateway) { await this.load(); bootstrap.Modal.getInstance(document.getElementById('editModal'))?.hide(); }
        },

        async toggleActive(gw) {
            const d = await HostPanel.fetch(`{{ url('admin/gateways') }}/${gw.id}`, { method:'PUT', body: JSON.stringify({ active: !gw.active }) });
            HostPanel.toast(d.message);
            if (d.gateway) gw.active = d.gateway.active;
        },

        async testGateway(gw) {
            const d = await HostPanel.fetch(`{{ url('admin/gateways') }}/${gw.id}/testar`, { method:'POST' });
            HostPanel.toast(d.message, 'info');
        },

        init() { this.load(); }
    }
}
</script>
@endpush
