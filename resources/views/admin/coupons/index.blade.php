@extends('admin.layouts.app')
@section('title', 'Cupons de Desconto')
@section('page-title', 'Cupons de Desconto')
@section('breadcrumb')
    <li class="breadcrumb-item active">Cupons</li>
@endsection

@section('content')
<div x-data="couponsIndex()">

    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" style="width:220px" placeholder="Buscar cupom..."
                   x-model.debounce.400="search" @input="page=1;load()">
            <select class="form-select form-select-sm" style="width:140px" x-model="filterActive" @change="page=1;load()">
                <option value="">Todos</option>
                <option value="1">Ativos</option>
                <option value="0">Inativos</option>
            </select>
        </div>
        <button class="btn btn-primary btn-sm" @click="openCreate()">
            <i class="bi bi-plus-lg me-1"></i>Novo Cupom
        </button>
    </div>

    {{-- Tabela --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Tipo</th>
                        <th>Desconto</th>
                        <th>Usos</th>
                        <th>Validade</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                    </template>
                    <template x-for="coupon in coupons" :key="coupon.id">
                        <tr>
                            <td>
                                <code class="fw-bold fs-6 text-primary" x-text="coupon.code"></code>
                                <div class="small text-muted" x-text="coupon.description" x-show="coupon.description"></div>
                            </td>
                            <td>
                                <span class="badge" :class="coupon.type==='percentage'?'bg-info':'bg-secondary'"
                                      x-text="coupon.type==='percentage'?'Percentual':'Valor Fixo'"></span>
                            </td>
                            <td class="fw-bold" :class="coupon.type==='percentage'?'text-info':'text-success'"
                                x-text="coupon.type==='percentage' ? coupon.value+'%' : 'R$ '+parseFloat(coupon.value).toFixed(2)"></td>
                            <td>
                                <span x-text="(coupon.times_used ?? 0)"></span>
                                <span class="text-muted" x-show="coupon.max_uses" x-text="' / ' + coupon.max_uses"></span>
                                <span class="text-muted" x-show="!coupon.max_uses">/ ∞</span>
                            </td>
                            <td>
                                <span class="text-muted small" x-show="!coupon.expires_at">Sem expiração</span>
                                <span class="small" :class="isExpired(coupon.expires_at) ? 'text-danger fw-semibold' : 'text-muted'"
                                      x-show="coupon.expires_at" x-text="fmtDate(coupon.expires_at)"></span>
                            </td>
                            <td>
                                <span class="badge" :class="coupon.active && !isExpired(coupon.expires_at) ? 'bg-success' : 'bg-secondary'"
                                      x-text="coupon.active && !isExpired(coupon.expires_at) ? 'Ativo' : 'Inativo'"></span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary me-1" @click="openEdit(coupon)"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" @click="deleteCoupon(coupon)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && coupons.length === 0">
                        <tr><td colspan="7" class="text-center text-muted py-5">
                            <div class="fs-1 mb-2">🎫</div>
                            Nenhum cupom encontrado.
                        </td></tr>
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

    {{-- Modal --}}
    <div class="modal fade" id="couponModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="form.id ? 'Editar Cupom' : 'Novo Cupom'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Código <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" x-model="form.code" placeholder="EX: PROMO20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select" x-model="form.type">
                                <option value="percentage">Percentual (%)</option>
                                <option value="fixed">Valor Fixo (R$)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Desconto <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" x-text="form.type==='percentage'?'%':'R$'"></span>
                                <input type="number" step="0.01" min="0" class="form-control" x-model="form.value">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Máx. Usos</label>
                            <input type="number" min="0" class="form-control" x-model="form.max_uses" placeholder="Ilimitado">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Válido até</label>
                            <input type="date" class="form-control" x-model="form.expires_at">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Valor mínimo (R$)</label>
                            <input type="number" step="0.01" min="0" class="form-control" x-model="form.min_amount" placeholder="0.00">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrição</label>
                            <input type="text" class="form-control" x-model="form.description" placeholder="Uso interno">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="form.active" id="couponActive">
                                <label class="form-check-label fw-semibold" for="couponActive">Cupom Ativo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" @click="saveCoupon()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                        <span x-text="form.id ? 'Atualizar' : 'Criar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function couponsIndex() {
    return {
        coupons: [], meta: null, loading: false, saving: false, page: 1, search: '', filterActive: '',
        form: { id: null, code: '', type: 'percentage', value: 10, max_uses: '', expires_at: '', description: '', active: true, min_amount: '' },
        defaultForm: null,

        async load() {
            this.loading = true;
            const p = new URLSearchParams({ search: this.search, active: this.filterActive, page: this.page });
            const d = await HostPanel.fetch(`{{ route('admin.coupons.index') }}?${p}`);
            this.coupons = d.data ?? [];
            this.meta    = d.meta ?? null;
            this.loading = false;
        },

        openCreate() {
            this.form = { id: null, code: '', type: 'percentage', value: 10, max_uses: '', expires_at: '', description: '', active: true, min_amount: '' };
            new bootstrap.Modal(document.getElementById('couponModal')).show();
        },

        openEdit(c) {
            this.form = { id: c.id, code: c.code, type: c.type, value: c.value, max_uses: c.max_uses ?? '', expires_at: c.expires_at ? c.expires_at.slice(0,10) : '', description: c.description ?? '', active: !!c.active, min_amount: c.min_amount ?? '' };
            new bootstrap.Modal(document.getElementById('couponModal')).show();
        },

        async saveCoupon() {
            this.saving = true;
            const isEdit = !!this.form.id;
            const url    = isEdit ? `{{ url('admin/cupons') }}/${this.form.id}` : '{{ route("admin.coupons.store") }}';
            const d = await HostPanel.fetch(url, { method: isEdit ? 'PUT' : 'POST', body: JSON.stringify(this.form) });
            this.saving = false;
            HostPanel.toast(d.message);
            if (d.coupon) { bootstrap.Modal.getInstance(document.getElementById('couponModal'))?.hide(); await this.load(); }
        },

        async deleteCoupon(c) {
            if (!confirm(`Excluir o cupom "${c.code}"? Esta ação não pode ser desfeita.`)) return;
            const d = await HostPanel.fetch(`{{ url('admin/cupons') }}/${c.id}`, { method: 'DELETE' });
            HostPanel.toast(d.message);
            await this.load();
        },

        isExpired(date) { return date && new Date(date) < new Date(); },
        fmtDate(d)      { return d ? new Date(d).toLocaleDateString('pt-BR') : '—'; },
        init()          { this.load(); }
    }
}
</script>
@endpush
