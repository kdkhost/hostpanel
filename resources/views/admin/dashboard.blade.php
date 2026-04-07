@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div x-data="dashboard()">

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 small fw-semibold text-uppercase">Receita do Mês</p>
                        <h3 class="fw-bold mb-0">R$ {{ number_format($stats['mrr'] ?? 0, 2, ',', '.') }}</h3>
                    </div>
                    <i class="bi bi-currency-dollar fs-2 text-primary opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100" style="border-left: 4px solid #10b981">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 small fw-semibold text-uppercase">Serviços Ativos</p>
                        <h3 class="fw-bold mb-0">{{ number_format($stats['active_services'] ?? 0) }}</h3>
                    </div>
                    <i class="bi bi-hdd-stack fs-2 text-success opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100" style="border-left: 4px solid #f59e0b">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 small fw-semibold text-uppercase">Tickets Abertos</p>
                        <h3 class="fw-bold mb-0">{{ number_format($stats['open_tickets'] ?? 0) }}</h3>
                    </div>
                    <i class="bi bi-headset fs-2 text-warning opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100" style="border-left: 4px solid #ef4444">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 small fw-semibold text-uppercase">Faturas em Atraso</p>
                        <h3 class="fw-bold mb-0 text-danger">R$ {{ number_format($stats['overdue_amount'] ?? 0, 2, ',', '.') }}</h3>
                        <small class="text-muted">{{ $stats['overdue_invoices'] ?? 0 }} faturas</small>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-2 text-danger opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Gráfico de Receita + Servidores --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-bar-chart-line me-2"></i>Receita Mensal</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-server me-2"></i>Status dos Servidores</h5>
                </div>
                <div class="card-body">
                    <canvas id="serversChart" height="160"></canvas>
                    <div class="d-flex justify-content-around mt-3">
                        <div class="text-center">
                            <h4 class="fw-bold text-success">{{ $stats['servers_online'] ?? 0 }}</h4>
                            <small class="text-muted">Online</small>
                        </div>
                        <div class="text-center">
                            <h4 class="fw-bold text-danger">{{ $stats['servers_offline'] ?? 0 }}</h4>
                            <small class="text-muted">Offline</small>
                        </div>
                        <div class="text-center">
                            <h4 class="fw-bold text-primary">{{ $stats['active_services'] ?? 0 }}</h4>
                            <small class="text-muted">Serviços</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transações Recentes --}}
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Transações Recentes</h5>
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-primary">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>Cliente</th><th>Valor</th><th>Gateway</th><th>Data</th></tr></thead>
                            <tbody>
                                @forelse($stats['recent_transactions'] ?? [] as $tx)
                                <tr>
                                    <td>{{ $tx->client->name ?? '—' }}</td>
                                    <td class="fw-semibold text-success">R$ {{ number_format($tx->amount, 2, ',', '.') }}</td>
                                    <td><span class="badge bg-info text-dark">{{ $tx->gateway }}</span></td>
                                    <td class="text-muted small">{{ \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Nenhuma transação encontrada.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-graph-up me-2"></i>Resumo</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Total de Clientes</span>
                            <strong>{{ number_format($stats['total_clients'] ?? 0) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Novos este mês</span>
                            <strong class="text-success">+{{ $stats['new_clients'] ?? 0 }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Suspensos hoje</span>
                            <strong class="text-warning">{{ $stats['suspended_today'] ?? 0 }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Faturas pendentes</span>
                            <strong class="text-info">{{ $stats['pending_invoices'] ?? 0 }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboard() { return {}; }

// Revenue Chart
const revenueLabels = @json(array_keys(($stats['revenue_chart'] ?? collect())->toArray()));
const revenueData   = @json(array_values(($stats['revenue_chart'] ?? collect())->toArray()));

new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: revenueLabels,
        datasets: [{
            label: 'Receita (R$)',
            data: revenueData,
            borderColor: '#1a56db',
            backgroundColor: 'rgba(26,86,219,0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

// Servers Chart
new Chart(document.getElementById('serversChart'), {
    type: 'doughnut',
    data: {
        labels: ['Online', 'Offline'],
        datasets: [{ data: [{{ $stats['servers_online'] ?? 0 }}, {{ $stats['servers_offline'] ?? 0 }}], backgroundColor: ['#10b981', '#ef4444'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } }, cutout: '70%' }
});
</script>
@endpush
