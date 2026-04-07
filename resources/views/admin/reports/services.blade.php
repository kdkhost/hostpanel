@extends('admin.layouts.app')
@section('title', 'Relatório de Serviços')
@section('page-title', 'Relatório de Serviços')
@section('breadcrumb')
    <li class="breadcrumb-item">Relatórios</li>
    <li class="breadcrumb-item active">Serviços</li>
@endsection

@section('content')
<div x-data="servicesReport()">

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <template x-for="kpi in kpis" :key="kpi.label">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 h-100" :style="`border-left: 4px solid ${kpi.color} !important`">
                    <div class="card-body">
                        <div class="text-muted small" x-text="kpi.label"></div>
                        <div class="fs-3 fw-black mt-1" x-text="kpi.value" :style="`color:${kpi.color}`"></div>
                        <div class="small text-muted mt-1" x-text="kpi.sub" x-show="kpi.sub"></div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="row g-4 mb-4">
        {{-- Status por Status --}}
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold">Distribuição por Status</div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="statusChart" height="220"></canvas>
                </div>
            </div>
        </div>

        {{-- Top Produtos --}}
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-trophy me-2 text-warning"></i>Top Produtos por Adoção</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Produto</th><th class="text-end">Ativos</th><th class="text-end">Total</th><th style="width:120px">%</th></tr></thead>
                        <tbody>
                            <template x-for="p in topProducts" :key="p.name">
                                <tr>
                                    <td class="fw-semibold small" x-text="p.name"></td>
                                    <td class="text-end text-success fw-semibold" x-text="p.active ?? p.count"></td>
                                    <td class="text-end text-muted" x-text="p.count"></td>
                                    <td>
                                        <div class="progress" style="height:6px">
                                            <div class="progress-bar bg-primary" :style="`width:${p.pct ?? 0}%`"></div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Crescimento Mensal --}}
    <div class="card">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-graph-up me-2 text-primary"></i>Novos Serviços por Mês (últimos 12 meses)</div>
        <div class="card-body">
            <canvas id="growthChart" height="100"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
function servicesReport() {
    return {
        kpis: [], topProducts: [], byStatus: [], loading: false,

        async load() {
            this.loading = true;
            const d = await HostPanel.fetch('{{ route("admin.reports.services") }}', {
                headers: { 'Accept': 'application/json' }
            });
            this.byStatus    = d.byStatus    ?? [];
            this.topProducts = (d.byProduct ?? []).map((p, _, arr) => {
                const total = arr.reduce((s, x) => s + x.count, 0);
                return { ...p, name: p.product?.name ?? 'N/A', pct: total ? (p.count/total*100).toFixed(1) : 0 };
            });
            this.kpis = [
                { label:'Serviços Ativos',      value: (d.byStatus.find(s=>s.status==='active')?.count??0).toString(),          color:'#10b981' },
                { label:'Suspensos',            value: (d.byStatus.find(s=>s.status==='suspended')?.count??0).toString(),       color:'#f59e0b' },
                { label:'Novos Este Mês',       value: (d.newThisMonth??0).toString(),                                          color:'#1a56db' },
                { label:'Cancelamentos/Mês',    value: (d.terminationsThisMonth??0).toString(), sub:`Churn: ${parseFloat(d.churnRate??0).toFixed(1)}%`, color:'#ef4444' },
            ];
            this.loading = false;
            await this.$nextTick();
            this.renderCharts(d);
        },

        renderCharts(d) {
            const statusCtx = document.getElementById('statusChart')?.getContext('2d');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: (d.byStatus ?? []).map(s => ({ active:'Ativo', suspended:'Suspenso', pending:'Pendente', terminated:'Encerrado', provisioning:'Provisionando' }[s.status]??s.status)),
                        datasets: [{ data: (d.byStatus ?? []).map(s => s.count), backgroundColor:['#10b981','#f59e0b','#94a3b8','#ef4444','#3b82f6'] }]
                    },
                    options: { responsive:true, plugins:{ legend:{ position:'bottom' } } }
                });
            }

            const growthCtx = document.getElementById('growthChart')?.getContext('2d');
            if (growthCtx && d.monthly) {
                new Chart(growthCtx, {
                    type: 'bar',
                    data: {
                        labels: (d.monthly ?? []).map(m => m.month),
                        datasets: [{ label:'Novos Serviços', data:(d.monthly??[]).map(m=>m.count), backgroundColor:'rgba(26,86,219,.7)', borderRadius:4 }]
                    },
                    options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
                });
            }
        },

        init() { this.load(); }
    }
}
</script>
@endpush
