@extends('admin.layouts.app')
@section('title', 'Faturas')
@section('page-title', 'Faturas')
@section('breadcrumb')
    <li class="breadcrumb-item active">Faturas</li>
@endsection

@section('content')
<div x-data="invoicesTable()">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-semibold"><i class="bi bi-receipt me-2"></i>Faturas</h5>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <input type="text" class="form-control form-control-sm" style="width:220px" placeholder="Buscar fatura ou cliente..." x-model.debounce.400="search" @input="page=1;load()">
                <select class="form-select form-select-sm" style="width:130px" x-model="status" @change="page=1;load()">
                    <option value="">Todos status</option>
                    <option value="pending">Pendente</option>
                    <option value="paid">Pago</option>
                    <option value="overdue">Em Atraso</option>
                    <option value="cancelled">Cancelado</option>
                </select>
                <input type="date" class="form-control form-control-sm" style="width:145px" x-model="dateFrom" @change="page=1;load()">
                <input type="date" class="form-control form-control-sm" style="width:145px" x-model="dateTo" @change="page=1;load()">
            </div>
        </div>

        {{-- KPI rápido --}}
        <div class="card-body border-bottom pb-3">
            <div class="row g-2 text-center">
                <div class="col-6 col-md-3">
                    <p class="text-muted small mb-0">Total Pendente</p>
                    <h5 class="fw-bold text-warning" x-text="'R$ ' + fmt(kpis.pending)"></h5>
                </div>
                <div class="col-6 col-md-3">
                    <p class="text-muted small mb-0">Total Pago</p>
                    <h5 class="fw-bold text-success" x-text="'R$ ' + fmt(kpis.paid)"></h5>
                </div>
                <div class="col-6 col-md-3">
                    <p class="text-muted small mb-0">Em Atraso</p>
                    <h5 class="fw-bold text-danger" x-text="'R$ ' + fmt(kpis.overdue)"></h5>
                </div>
                <div class="col-6 col-md-3">
                    <p class="text-muted small mb-0">Total Registros</p>
                    <h5 class="fw-bold" x-text="meta?.total ?? '—'"></h5>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Nº Fatura</th><th>Cliente</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Em Aberto</th><th>Vencimento</th><th class="text-center">Ações</th></tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>
                        </template>
                        <template x-for="inv in invoices" :key="inv.id">
                            <tr>
                                <td><a :href="`/admin/faturas/${inv.id}`" class="fw-semibold" x-text="`#${inv.number}`"></a></td>
                                <td>
                                    <div x-text="inv.client?.name"></div>
                                    <small class="text-muted" x-text="inv.client?.email"></small>
                                </td>
                                <td><span :class="`badge bg-${statusColor(inv.status)}`" x-text="statusLabel(inv.status)"></span></td>
                                <td class="text-end fw-semibold" x-text="'R$ ' + fmt(inv.total)"></td>
                                <td class="text-end" :class="inv.amount_due > 0 ? 'text-danger fw-semibold' : 'text-muted'" x-text="'R$ ' + fmt(inv.amount_due)"></td>
                                <td :class="isOverdue(inv.date_due, inv.status) ? 'text-danger fw-semibold' : 'text-muted'" x-text="formatDate(inv.date_due)"></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a :href="`/admin/faturas/${inv.id}`" class="btn btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></a>
                                        <a :href="`/admin/faturas/${inv.id}/pdf`" target="_blank" class="btn btn-outline-secondary" title="PDF"><i class="bi bi-file-pdf"></i></a>
                                        <button x-show="inv.status === 'pending' || inv.status === 'overdue'" class="btn btn-outline-success" title="Marcar Pago" @click="markPaid(inv)"><i class="bi bi-check-circle"></i></button>
                                        <button x-show="inv.status === 'pending'" class="btn btn-outline-danger" title="Cancelar" @click="cancel(inv)"><i class="bi bi-x-circle"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && invoices.length === 0">
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma fatura encontrada.</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center" x-show="meta">
            <span class="text-muted small" x-text="`${meta?.from ?? 0}–${meta?.to ?? 0} de ${meta?.total ?? 0}`"></span>
            <nav><ul class="pagination pagination-sm mb-0">
                <li class="page-item" :class="{disabled: page===1}"><button class="page-link" @click="page--;load()">«</button></li>
                <li class="page-item active"><a class="page-link" x-text="page"></a></li>
                <li class="page-item" :class="{disabled: page>=meta?.last_page}"><button class="page-link" @click="page++;load()">»</button></li>
            </ul></nav>
        </div>
    </div>

    {{-- Modal Marcar Pago --}}
    <div class="modal fade" id="payModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Registrar Pagamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form @submit.prevent="confirmPay">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Valor</label>
                            <input type="number" step="0.01" class="form-control" x-model="payForm.amount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gateway</label>
                            <select class="form-select" x-model="payForm.gateway">
                                <option value="manual">Manual</option>
                                <option value="pix">PIX</option>
                                <option value="boleto">Boleto</option>
                                <option value="credit_card">Cartão</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">ID Transação (opcional)</label>
                            <input type="text" class="form-control" x-model="payForm.transaction_id">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Pagamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function invoicesTable() {
    return {
        invoices: [], meta: null, loading: false,
        search: '', status: '', dateFrom: '', dateTo: '', page: 1,
        kpis: { pending: 0, paid: 0, overdue: 0 },
        payForm: { amount: 0, gateway: 'manual', transaction_id: '' },
        currentInvoice: null,

        async load() {
            this.loading = true;
            const p = new URLSearchParams({ search: this.search, status: this.status, date_from: this.dateFrom, date_to: this.dateTo, page: this.page });
            const data = await HostPanel.fetch(`/admin/faturas?${p}`);
            this.invoices = data.data || [];
            this.meta     = data.meta || data;
            this.kpis     = data.kpis || this.kpis;
            this.loading  = false;
        },

        markPaid(inv) {
            this.currentInvoice = inv;
            this.payForm.amount = inv.amount_due;
            new bootstrap.Modal(document.getElementById('payModal')).show();
        },

        async confirmPay() {
            const data = await HostPanel.fetch(`/admin/faturas/${this.currentInvoice.id}/pagar`, { method:'POST', body: JSON.stringify(this.payForm) });
            bootstrap.Modal.getInstance(document.getElementById('payModal'))?.hide();
            HostPanel.toast(data.message);
            this.load();
        },

        async cancel(inv) {
            if (!(await HostPanel.confirm({ text: 'Cancelar esta fatura?', confirmButtonText: 'Sim, cancelar' }))) return;
            const data = await HostPanel.fetch(`/admin/faturas/${inv.id}/cancelar`, { method:'POST' });
            HostPanel.toast(data.message);
            this.load();
        },

        statusColor(s) { return {pending:'warning',paid:'success',overdue:'danger',cancelled:'secondary',refunded:'info'}[s]||'secondary'; },
        statusLabel(s) { return {pending:'Pendente',paid:'Pago',overdue:'Em Atraso',cancelled:'Cancelado',refunded:'Estornado'}[s]||s; },
        isOverdue(d, s) { return s === 'overdue' || (s === 'pending' && new Date(d) < new Date()); },
        formatDate(d) { return d ? new Date(d+'T00:00:00').toLocaleDateString('pt-BR') : '—'; },
        fmt(v) { return parseFloat(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2}); },
        init() { this.load(); }
    }
}
</script>
@endpush
