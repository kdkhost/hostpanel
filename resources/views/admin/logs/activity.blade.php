@extends('admin.layouts.app')
@section('title', 'Logs de Atividade')
@section('page-title', 'Logs de Atividade')
@section('breadcrumb')
    <li class="breadcrumb-item">Logs</li>
    <li class="breadcrumb-item active">Atividade</li>
@endsection

@section('content')
<div x-data="activityLogs()">

    {{-- Filtros --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <input type="text" class="form-control form-control-sm" placeholder="Buscar evento, usuário..."
                           x-model.debounce.400="filters.search" @input="page=1;load()">
                </div>
                <div class="col-sm-3">
                    <select class="form-select form-select-sm" x-model="filters.causer_type" @change="page=1;load()">
                        <option value="">Todos os atores</option>
                        <option value="admin">Admins</option>
                        <option value="client">Clientes</option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <input type="date" class="form-control form-control-sm" x-model="filters.date_from" @change="page=1;load()">
                </div>
                <div class="col-sm-2">
                    <input type="date" class="form-control form-control-sm" x-model="filters.date_to" @change="page=1;load()">
                </div>
                <div class="col-sm-1">
                    <button class="btn btn-sm btn-outline-secondary w-100" @click="clearFilters()"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabela de Logs --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-clock-history me-2"></i>Histórico de Atividades</span>
            <span class="badge bg-secondary" x-text="meta?.total ? meta.total + ' registros' : ''"></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width:160px">Data/Hora</th>
                        <th>Ator</th>
                        <th>Evento</th>
                        <th>Objeto</th>
                        <th>IP</th>
                        <th class="text-center">Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                    </template>
                    <template x-for="log in logs" :key="log.id">
                        <tr>
                            <td class="text-muted font-monospace" style="font-size:.75rem" x-text="fmtDatetime(log.created_at)"></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge" :class="log.causer_type === 'admin' ? 'bg-primary' : 'bg-info'" x-text="log.causer_type === 'admin' ? 'Admin' : 'Cliente'"></span>
                                    <span x-text="log.causer?.name ?? '—'"></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border" x-text="log.description"></span>
                            </td>
                            <td>
                                <span class="text-muted" x-text="log.subject_type ? log.subject_type.split('\\\\').pop() + ' #' + log.subject_id : '—'"></span>
                            </td>
                            <td><code style="font-size:.75rem" x-text="log.properties?.ip ?? '—'"></code></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary" @click="selected=log" data-bs-toggle="modal" data-bs-target="#logModal">
                                    <i class="bi bi-info-circle"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && logs.length === 0">
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum log encontrado.</td></tr>
                    </template>
                </tbody>
            </table>
        </div>
        {{-- Paginação --}}
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2" x-show="meta && meta.total > 0">
            <small class="text-muted" x-text="`${meta?.from ?? 0}–${meta?.to ?? 0} de ${meta?.total ?? 0} registros`"></small>
            <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-secondary" :disabled="page === 1" @click="page--;load()">‹</button>
                <span class="btn btn-sm btn-primary disabled" x-text="page"></span>
                <button class="btn btn-sm btn-outline-secondary" :disabled="page >= (meta?.last_page ?? 1)" @click="page++;load()">›</button>
            </div>
        </div>
    </div>

    {{-- Modal Detalhes --}}
    <div class="modal fade" id="logModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" x-show="selected">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Log</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" x-show="selected">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="fw-semibold text-muted small mb-1">Data/Hora</div>
                            <div x-text="fmtDatetime(selected?.created_at)"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold text-muted small mb-1">Evento</div>
                            <div x-text="selected?.description"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold text-muted small mb-1">Ator</div>
                            <div x-text="(selected?.causer_type === 'admin' ? 'Admin' : 'Cliente') + ': ' + (selected?.causer?.name ?? '—')"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold text-muted small mb-1">Objeto</div>
                            <div x-text="selected?.subject_type ? selected.subject_type.split('\\\\').pop() + ' #' + selected.subject_id : '—'"></div>
                        </div>
                        <div class="col-12">
                            <div class="fw-semibold text-muted small mb-1">Propriedades</div>
                            <pre class="bg-light rounded p-3 small" style="max-height:300px;overflow-y:auto" x-text="JSON.stringify(selected?.properties, null, 2)"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function activityLogs() {
    return {
        logs: [], meta: null, loading: false, page: 1, selected: null,
        filters: { search: '', causer_type: '', date_from: '', date_to: '' },

        async load() {
            this.loading = true;
            const p = new URLSearchParams({ ...this.filters, page: this.page });
            const d = await HostPanel.fetch(`/api/v1/admin/logs/activity?${p}`);
            this.logs = d.data ?? [];
            this.meta = d.meta ?? null;
            this.loading = false;
        },

        clearFilters() {
            this.filters = { search:'', causer_type:'', date_from:'', date_to:'' };
            this.page = 1;
            this.load();
        },

        fmtDatetime(d) { return d ? new Date(d).toLocaleString('pt-BR') : '—'; },
        init() { this.load(); }
    }
}
</script>
@endpush
