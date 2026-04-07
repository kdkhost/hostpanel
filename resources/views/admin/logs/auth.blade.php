@extends('admin.layouts.app')
@section('title', 'Logs de Autenticação')
@section('page-title', 'Logs de Autenticação')
@section('breadcrumb')
    <li class="breadcrumb-item">Logs</li>
    <li class="breadcrumb-item active">Autenticação</li>
@endsection

@section('content')
<div x-data="authLogs()">

    {{-- Filtros --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-3">
                    <input type="text" class="form-control form-control-sm" placeholder="IP, e-mail..."
                           x-model.debounce.400="filters.search" @input="page=1;load()">
                </div>
                <div class="col-sm-2">
                    <select class="form-select form-select-sm" x-model="filters.type" @change="page=1;load()">
                        <option value="">Todos os tipos</option>
                        <option value="admin">Admins</option>
                        <option value="client">Clientes</option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <select class="form-select form-select-sm" x-model="filters.success" @change="page=1;load()">
                        <option value="">Todos</option>
                        <option value="true">Sucesso</option>
                        <option value="false">Falha</option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <input type="date" class="form-control form-control-sm" x-model="filters.date_from" @change="page=1;load()">
                </div>
                <div class="col-sm-2">
                    <input type="date" class="form-control form-control-sm" x-model="filters.date_to" @change="page=1;load()">
                </div>
                <div class="col-sm-1">
                    <button class="btn btn-sm btn-outline-secondary w-100" @click="resetFilters()"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        </div>
    </div>

    {{-- Resumo de Segurança --}}
    <div class="row g-3 mb-4" x-show="summary.total > 0">
        <div class="col-sm-4">
            <div class="card border-0 border-start border-success border-4">
                <div class="card-body py-3">
                    <div class="text-muted small">Logins com Sucesso</div>
                    <div class="fs-4 fw-black text-success" x-text="summary.success ?? 0"></div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 border-start border-danger border-4">
                <div class="card-body py-3">
                    <div class="text-muted small">Tentativas Falhas</div>
                    <div class="fs-4 fw-black text-danger" x-text="summary.failed ?? 0"></div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 border-start border-warning border-4">
                <div class="card-body py-3">
                    <div class="text-muted small">IPs Únicos</div>
                    <div class="fs-4 fw-black text-warning" x-text="summary.unique_ips ?? 0"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-shield-lock me-2 text-primary"></i>Histórico de Autenticação</span>
            <span class="badge bg-secondary" x-text="meta?.total ? meta.total + ' registros' : ''"></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width:160px">Data/Hora</th>
                        <th>Usuário</th>
                        <th>Tipo</th>
                        <th>IP</th>
                        <th>Resultado</th>
                        <th>User-Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                    </template>
                    <template x-for="log in logs" :key="log.id">
                        <tr :class="!log.success ? 'table-danger bg-opacity-25' : ''">
                            <td class="font-monospace text-muted" style="font-size:.75rem" x-text="fmtDatetime(log.created_at)"></td>
                            <td>
                                <div class="fw-semibold" x-text="log.authenticatable?.name ?? log.email ?? '—'"></div>
                                <small class="text-muted" x-text="log.authenticatable?.email ?? ''"></small>
                            </td>
                            <td>
                                <span class="badge" :class="log.authenticatable_type?.includes('Admin') ? 'bg-primary' : 'bg-info'"
                                      x-text="log.authenticatable_type?.includes('Admin') ? 'Admin' : 'Cliente'"></span>
                            </td>
                            <td><code style="font-size:.75rem" x-text="log.ip_address ?? '—'"></code></td>
                            <td>
                                <span class="badge" :class="log.success ? 'bg-success' : 'bg-danger'"
                                      x-text="log.success ? '✓ Sucesso' : '✗ Falha'"></span>
                                <div class="text-muted mt-0.5" style="font-size:.7rem" x-text="log.failure_reason" x-show="!log.success && log.failure_reason"></div>
                            </td>
                            <td>
                                <span class="text-muted" style="font-size:.75rem;max-width:200px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                      :title="log.user_agent"
                                      x-text="formatUa(log.user_agent)"></span>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && logs.length === 0">
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum log encontrado.</td></tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2" x-show="meta && meta.total > 0">
            <small class="text-muted" x-text="`${meta?.from ?? 0}–${meta?.to ?? 0} de ${meta?.total ?? 0} registros`"></small>
            <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-secondary" :disabled="page === 1" @click="page--;load()">‹</button>
                <span class="btn btn-sm btn-primary disabled" x-text="page"></span>
                <button class="btn btn-sm btn-outline-secondary" :disabled="page >= (meta?.last_page ?? 1)" @click="page++;load()">›</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function authLogs() {
    return {
        logs: [], meta: null, loading: false, page: 1,
        summary: {},
        filters: { search: '', type: '', success: '', date_from: '', date_to: '' },

        async load() {
            this.loading = true;
            const p = new URLSearchParams({ ...this.filters, page: this.page, per_page: 25 });
            const d = await HostPanel.fetch(`{{ route('admin.logs.auth') }}?${p}`, {
                headers: { 'Accept': 'application/json' }
            });
            this.logs = d.data ?? [];
            this.meta = d.meta ?? null;
            this.buildSummary();
            this.loading = false;
        },

        buildSummary() {
            this.summary = {
                total:      this.meta?.total ?? 0,
                success:    this.logs.filter(l => l.success).length,
                failed:     this.logs.filter(l => !l.success).length,
                unique_ips: [...new Set(this.logs.map(l => l.ip_address))].length,
            };
        },

        resetFilters() {
            this.filters = { search:'', type:'', success:'', date_from:'', date_to:'' };
            this.page = 1;
            this.load();
        },

        formatUa(ua) {
            if (!ua) return '—';
            if (ua.includes('Chrome'))  return '🌐 Chrome';
            if (ua.includes('Firefox')) return '🦊 Firefox';
            if (ua.includes('Safari'))  return '🧭 Safari';
            if (ua.includes('Edge'))    return '🔵 Edge';
            return ua.substring(0, 40) + (ua.length > 40 ? '…' : '');
        },

        fmtDatetime(d) { return d ? new Date(d).toLocaleString('pt-BR') : '—'; },
        init() { this.load(); }
    }
}
</script>
@endpush
