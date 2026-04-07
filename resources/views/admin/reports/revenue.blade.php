@extends('admin.layouts.app')
@section('title', 'Relatório de Receita')
@section('page-title', 'Relatório de Receita')
@section('breadcrumb')
    <li class="breadcrumb-item">Relatórios</li>
    <li class="breadcrumb-item active">Receita</li>
@endsection

@section('content')
<div x-data="revenueReport()" class="space-y-4">

    {{-- Filtros --}}
    <div class="card mb-0">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-3">
                    <label class="form-label fw-semibold small mb-1">De</label>
                    <input type="date" class="form-control form-control-sm" x-model="filters.date_from" @change="load()">
                </div>
                <div class="col-sm-3">
                    <label class="form-label fw-semibold small mb-1">Até</label>
                    <input type="date" class="form-control form-control-sm" x-model="filters.date_to" @change="load()">
                </div>
                <div class="col-sm-3">
                    <label class="form-label fw-semibold small mb-1">Agrupar por</label>
                    <select class="form-select form-select-sm" x-model="filters.group_by" @change="load()">
                        <option value="day">Dia</option>
                        <option value="week">Semana</option>
                        <option value="month" selected>Mês</option>
                    </select>
                </div>
                <div class="col-sm-3">
                    <button class="btn btn-sm btn-outline-secondary w-100" @click="exportCsv()"><i class="bi bi-download me-1"></i>Exportar CSV</button>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3">
        <template x-for="kpi in kpis" :key="kpi.label">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 h-100" :style="`border-left: 4px solid ${kpi.color} !important`">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small" x-text="kpi.label"></div>
                                <div class="fs-4 fw-black mt-1" x-text="kpi.value" :style="`color: ${kpi.color}`"></div>
                                <div class="small mt-1" x-show="kpi.delta !== undefined">
                                    <span :class="kpi.delta >= 0 ? 'text-success' : 'text-danger'">
                                        <i class="bi" :class="kpi.delta >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right'"></i>
                                        <span x-text="Math.abs(kpi.delta) + '% vs período anterior'"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="fs-2 opacity-25" x-text="kpi.icon"></div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Gráfico de Receita --}}
    <div class="card">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-graph-up me-2 text-primary"></i>Evolução de Receita</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm" :class="chartType==='bar'?'btn-primary':'btn-outline-secondary'" @click="chartType='bar';renderChart()">
                    <i class="bi bi-bar-chart"></i>
                </button>
                <button class="btn btn-sm" :class="chartType==='line'?'btn-primary':'btn-outline-secondary'" @click="chartType='line';renderChart()">
                    <i class="bi bi-graph-up"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div x-show="loading" class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            <canvas id="revenueChart" height="100" x-show="!loading"></canvas>
        </div>
    </div>

    {{-- Tabela Detalhada --}}
    <div class="card">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-table me-2"></i>Detalhamento por Período</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Período</th>
                        <th class="text-end">Faturado</th>
                        <th class="text-end">Recebido</th>
                        <th class="text-end">Pendente</th>
                        <th class="text-end">Cancelado</th>
                        <th class="text-end">Qtd. Faturas</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>
                    </template>
                    <template x-for="row in rows" :key="row.period">
                        <tr>
                            <td class="fw-semibold" x-text="row.period"></td>
                            <td class="text-end fw-semibold" x-text="fmt(row.total)"></td>
                            <td class="text-end text-success fw-semibold" x-text="fmt(row.paid)"></td>
                            <td class="text-end text-warning" x-text="fmt(row.pending)"></td>
                            <td class="text-end text-danger" x-text="fmt(row.cancelled)"></td>
                            <td class="text-end text-muted" x-text="row.count"></td>
                        </tr>
                    </template>
                    <template x-if="!loading && rows.length === 0">
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum dado no período selecionado.</td></tr>
                    </template>
                </tbody>
                <tfoot x-show="rows.length > 0">
                    <tr class="table-light fw-bold">
                        <td>Total</td>
                        <td class="text-end" x-text="fmt(totals.total)"></td>
                        <td class="text-end text-success" x-text="fmt(totals.paid)"></td>
                        <td class="text-end text-warning" x-text="fmt(totals.pending)"></td>
                        <td class="text-end text-danger" x-text="fmt(totals.cancelled)"></td>
                        <td class="text-end" x-text="totals.count"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Top Produtos --}}
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-trophy me-2 text-warning"></i>Top Produtos</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Produto</th><th class="text-end">Receita</th><th class="text-end">Qtd.</th></tr></thead>
                        <tbody>
                            <template x-for="p in topProducts" :key="p.name">
                                <tr>
                                    <td class="fw-semibold small" x-text="p.name"></td>
                                    <td class="text-end small fw-semibold text-success" x-text="fmt(p.revenue)"></td>
                                    <td class="text-end small text-muted" x-text="p.count"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-credit-card me-2 text-info"></i>Por Gateway de Pagamento</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Gateway</th><th class="text-end">Valor</th><th class="text-end">Qtd.</th></tr></thead>
                        <tbody>
                            <template x-for="g in gateways" :key="g.name">
                                <tr>
                                    <td class="fw-semibold small" x-text="g.name"></td>
                                    <td class="text-end small fw-semibold" x-text="fmt(g.amount)"></td>
                                    <td class="text-end small text-muted" x-text="g.count"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
let chart = null;

function revenueReport() {
    const today    = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0,10);
    const lastDay  = today.toISOString().slice(0,10);

    return {
        filters: { date_from: firstDay, date_to: lastDay, group_by: 'month' },
        loading: false, chartType: 'bar',
        rows: [], totals: {}, kpis: [], topProducts: [], gateways: [], chartData: null,

        async load() {
            this.loading = true;
            const p = new URLSearchParams(this.filters);
            const d = await HostPanel.fetch(`/api/v1/admin/reports/revenue?${p}`);
            this.rows        = d.rows        ?? [];
            this.totals      = d.totals      ?? {};
            this.topProducts = d.top_products ?? [];
            this.gateways    = d.gateways    ?? [];
            this.chartData   = d.chart       ?? null;
            this.buildKpis(d);
            this.loading     = false;
            await this.$nextTick();
            this.renderChart();
        },

        buildKpis(d) {
            this.kpis = [
                { label:'Faturado no Período', value: this.fmt(d.totals?.total ?? 0),    color:'#1a56db', icon:'💰' },
                { label:'Recebido',            value: this.fmt(d.totals?.paid ?? 0),     color:'#10b981', icon:'✅', delta: d.delta_paid },
                { label:'Pendente',            value: this.fmt(d.totals?.pending ?? 0),  color:'#f59e0b', icon:'⏳' },
                { label:'Faturas Geradas',     value: (d.totals?.count ?? 0).toString(), color:'#6366f1', icon:'📄' },
            ];
        },

        renderChart() {
            if (!this.chartData) return;
            if (chart) { chart.destroy(); chart = null; }
            const ctx = document.getElementById('revenueChart')?.getContext('2d');
            if (!ctx) return;
            chart = new Chart(ctx, {
                type: this.chartType,
                data: {
                    labels: this.chartData.labels,
                    datasets: [
                        { label:'Recebido',  data: this.chartData.paid,      backgroundColor:'rgba(16,185,129,.8)',  borderColor:'#10b981', borderWidth:2, tension:.3 },
                        { label:'Pendente',  data: this.chartData.pending,   backgroundColor:'rgba(245,158,11,.6)',  borderColor:'#f59e0b', borderWidth:2, tension:.3 },
                        { label:'Cancelado', data: this.chartData.cancelled, backgroundColor:'rgba(239,68,68,.4)',   borderColor:'#ef4444', borderWidth:2, tension:.3 },
                    ]
                },
                options: { responsive:true, plugins:{ legend:{ position:'bottom' } }, scales:{ y:{ ticks:{ callback: v=>'R$'+v } } } }
            });
        },

        exportCsv() {
            const p = new URLSearchParams({ ...this.filters, export:'csv' });
            window.location.href = `/api/v1/admin/reports/revenue?${p}`;
        },

        fmt(v) { return 'R$ ' + parseFloat(v || 0).toLocaleString('pt-BR', { minimumFractionDigits:2 }); },
        init() { this.load(); }
    }
}
</script>
@endpush
