@extends('admin.layouts.app')
@section('title', 'Logs de Impersonação')
@section('page-title', 'Logs de Impersonação')
@section('breadcrumb')
    <li class="breadcrumb-item">Logs</li>
    <li class="breadcrumb-item active">Impersonação</li>
@endsection

@section('content')
<div x-data="impersonationLogs()">

    {{-- Filtros --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <input type="text" class="form-control form-control-sm" placeholder="Buscar admin ou cliente..."
                           x-model.debounce.400="search" @input="page=1;load()">
                </div>
                <div class="col-sm-3">
                    <input type="date" class="form-control form-control-sm" x-model="dateFrom" @change="page=1;load()">
                </div>
                <div class="col-sm-3">
                    <input type="date" class="form-control form-control-sm" x-model="dateTo" @change="page=1;load()">
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-sm btn-outline-secondary w-100" @click="search='';dateFrom='';dateTo='';page=1;load()">
                        <i class="bi bi-x-lg me-1"></i>Limpar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerta de Segurança --}}
    <div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
        <i class="bi bi-shield-exclamation fs-5 mt-1 flex-shrink-0"></i>
        <div>
            <strong>Auditoria de Impersonação</strong> — Este log registra todos os acessos em que um administrador assumiu a conta de um cliente.
            Revise regularmente para garantir a conformidade com a política de privacidade.
        </div>
    </div>

    {{-- Tabela --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-person-fill-gear me-2 text-warning"></i>Registros de Impersonação</span>
            <span class="badge bg-secondary" x-text="meta?.total ? meta.total + ' registros' : ''"></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Início</th>
                        <th>Admin</th>
                        <th>Cliente</th>
                        <th>IP</th>
                        <th>Motivo</th>
                        <th>Duração</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                    </template>
                    <template x-for="log in logs" :key="log.id">
                        <tr>
                            <td class="text-muted font-monospace" style="font-size:.75rem" x-text="fmtDatetime(log.started_at)"></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                         style="width:28px;height:28px;font-size:.75rem"
                                         x-text="log.admin?.name?.charAt(0)?.toUpperCase() ?? '?'"></div>
                                    <div>
                                        <div class="fw-semibold" x-text="log.admin?.name ?? '—'"></div>
                                        <small class="text-muted" x-text="log.admin?.email ?? ''"></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a :href="`/admin/clientes/${log.client_id}`" class="text-decoration-none">
                                    <div class="fw-semibold" x-text="log.client?.name ?? '—'"></div>
                                    <small class="text-muted" x-text="log.client?.email ?? ''"></small>
                                </a>
                            </td>
                            <td><code style="font-size:.75rem" x-text="log.ip_address ?? '—'"></code></td>
                            <td>
                                <span class="text-muted fst-italic" x-text="log.reason ? '\"' + log.reason + '\"' : '—'"></span>
                            </td>
                            <td>
                                <span x-show="log.ended_at" x-text="calcDuration(log.started_at, log.ended_at)"
                                      class="badge bg-light text-dark border"></span>
                                <span x-show="!log.ended_at" class="badge bg-success bg-opacity-75">Ativa</span>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && logs.length === 0">
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum registro encontrado.</td></tr>
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
function impersonationLogs() {
    return {
        logs: [], meta: null, loading: false, page: 1, search: '', dateFrom: '', dateTo: '',

        async load() {
            this.loading = true;
            const p = new URLSearchParams({ search: this.search, date_from: this.dateFrom, date_to: this.dateTo, page: this.page, per_page: 20 });
            const d = await HostPanel.fetch(`{{ route('admin.logs.impersonation') }}?${p}`, {
                headers: { 'Accept': 'application/json' }
            });
            this.logs = d.data ?? [];
            this.meta = d.meta ?? null;
            this.loading = false;
        },

        calcDuration(start, end) {
            if (!start || !end) return '';
            const secs = Math.floor((new Date(end) - new Date(start)) / 1000);
            if (secs < 60)  return secs + 's';
            if (secs < 3600) return Math.floor(secs/60) + 'min';
            return Math.floor(secs/3600) + 'h ' + Math.floor((secs%3600)/60) + 'min';
        },

        fmtDatetime(d) { return d ? new Date(d).toLocaleString('pt-BR') : '—'; },
        init() { this.load(); }
    }
}
</script>
@endpush
