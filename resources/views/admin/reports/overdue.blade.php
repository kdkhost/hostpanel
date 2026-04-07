@extends('admin.layouts.app')
@section('title', 'Inadimplência')
@section('page-title', 'Relatório de Inadimplência')
@section('breadcrumb')
    <li class="breadcrumb-item">Relatórios</li>
    <li class="breadcrumb-item active">Inadimplência</li>
@endsection

@section('content')
<div x-data="overdueReport()">

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 border-start border-danger border-4">
                <div class="card-body">
                    <div class="text-muted small">Total em Atraso</div>
                    <div class="fs-4 fw-black text-danger" x-text="'R$ ' + fmt(summary.total)">—</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 border-start border-warning border-4">
                <div class="card-body">
                    <div class="text-muted small">Faturas em Atraso</div>
                    <div class="fs-4 fw-black text-warning" x-text="summary.count ?? '—'">—</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 border-start border-info border-4">
                <div class="card-body">
                    <div class="text-muted small">Clientes Inadimplentes</div>
                    <div class="fs-4 fw-black text-info" x-text="summary.unique_clients ?? '—'">—</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 border-start border-secondary border-4">
                <div class="card-body">
                    <div class="text-muted small">Média por Fatura</div>
                    <div class="fs-4 fw-black" x-text="'R$ ' + fmt(summary.average)">—</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Aging --}}
    <div class="row g-4 mb-4">
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold">Aging (Tempo em Atraso)</div>
                <div class="card-body">
                    <canvas id="agingChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold">Distribuição por Faixa</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Faixa de Atraso</th><th class="text-end">Qtd.</th><th class="text-end">Valor</th></tr></thead>
                        <tbody>
                            <template x-for="age in aging" :key="age.label">
                                <tr>
                                    <td><span class="badge" :class="age.color" x-text="age.label"></span></td>
                                    <td class="text-end fw-semibold" x-text="age.count"></td>
                                    <td class="text-end fw-semibold" x-text="'R$ ' + fmt(age.amount)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Listagem --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Faturas em Atraso</span>
            <div class="d-flex gap-2">
                <input type="text" class="form-control form-control-sm" placeholder="Buscar..." style="width:200px"
                       x-model.debounce.400="search" @input="page=1">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Fatura</th>
                        <th>Cliente</th>
                        <th>Vencimento</th>
                        <th class="text-center">Dias em Atraso</th>
                        <th class="text-end">Valor</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                    </template>
                    <template x-for="inv in filteredInvoices" :key="inv.id">
                        <tr>
                            <td class="fw-semibold font-monospace" x-text="'#' + inv.number"></td>
                            <td>
                                <div x-text="inv.client?.name"></div>
                                <small class="text-muted" x-text="inv.client?.email"></small>
                            </td>
                            <td class="text-danger fw-semibold small" x-text="fmtDate(inv.date_due)"></td>
                            <td class="text-center">
                                <span class="badge" :class="daysOverdue(inv.date_due) > 90 ? 'bg-danger' : daysOverdue(inv.date_due) > 30 ? 'bg-warning text-dark' : 'bg-secondary'"
                                      x-text="daysOverdue(inv.date_due) + ' dias'"></span>
                            </td>
                            <td class="text-end fw-bold" x-text="'R$ ' + fmt(inv.amount_due)"></td>
                            <td class="text-center">
                                <a :href="'/admin/faturas/' + inv.id" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-eye"></i></a>
                                <button class="btn btn-sm btn-outline-warning" @click="sendReminder(inv.id)" title="Enviar Lembrete">
                                    <i class="bi bi-envelope"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && filteredInvoices.length === 0">
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma fatura em atraso. 🎉</td></tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
function overdueReport() {
    return {
        invoices: [], summary: {}, aging: [], search: '', loading: false, page: 1,

        get filteredInvoices() {
            if (!this.search) return this.invoices;
            const s = this.search.toLowerCase();
            return this.invoices.filter(i =>
                (i.number + '').includes(s) ||
                i.client?.name?.toLowerCase().includes(s) ||
                i.client?.email?.toLowerCase().includes(s)
            );
        },

        async load() {
            this.loading = true;
            const d = await HostPanel.fetch('{{ route("admin.reports.overdue") }}', {
                headers: { 'Accept': 'application/json' }
            });
            this.invoices = d.invoices ?? [];
            this.summary  = {
                total:          d.totalOverdue ?? 0,
                count:          d.count        ?? 0,
                unique_clients: [...new Set((d.invoices ?? []).map(i => i.client_id))].length,
                average:        d.count > 0 ? d.totalOverdue / d.count : 0,
            };
            this.buildAging(d.byAge ?? {});
            this.loading = false;
            this.$nextTick(() => this.renderChart());
        },

        buildAging(byAge) {
            this.aging = [
                { label:'1–7 dias',   count: byAge['1-7_dias']   ?? 0, amount: 0, color:'bg-secondary' },
                { label:'8–30 dias',  count: byAge['8-30_dias']  ?? 0, amount: 0, color:'bg-warning text-dark' },
                { label:'31–90 dias', count: byAge['31-90_dias'] ?? 0, amount: 0, color:'bg-danger' },
                { label:'+90 dias',   count: byAge['90+_dias']   ?? 0, amount: 0, color:'bg-dark' },
            ];
        },

        renderChart() {
            const ctx = document.getElementById('agingChart')?.getContext('2d');
            if (!ctx) return;
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: this.aging.map(a => a.label),
                    datasets: [{ data: this.aging.map(a => a.count), backgroundColor:['#94a3b8','#f59e0b','#ef4444','#1e293b'] }]
                },
                options: { responsive:true, plugins:{ legend:{ position:'bottom' } } }
            });
        },

        daysOverdue(d) { return Math.max(0, Math.floor((Date.now() - new Date(d)) / 86400000)); },
        fmtDate(d)     { return d ? new Date(d).toLocaleDateString('pt-BR') : '—'; },
        fmt(v)         { return parseFloat(v || 0).toLocaleString('pt-BR', { minimumFractionDigits:2 }); },

        async sendReminder(id) {
            const d = await HostPanel.fetch(`/admin/faturas/${id}/enviar`, { method:'POST' });
            HostPanel.toast(d.message);
        },

        init() { this.load(); }
    }
}
</script>
@endpush
