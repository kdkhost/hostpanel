@extends('admin.layouts.app')
@section('title', 'Produtos')
@section('page-title', 'Produtos & Planos')
@section('breadcrumb')
    <li class="breadcrumb-item active">Produtos</li>
@endsection

@section('content')
<div x-data="productsTable()">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between flex-wrap gap-2 align-items-center">
            <h5 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2"></i>Produtos</h5>
            <div class="d-flex gap-2 flex-wrap">
                <input type="text" class="form-control form-control-sm" style="width:220px" placeholder="Buscar produto..." x-model.debounce.400="search" @input="page=1;load()">
                <select class="form-select form-select-sm" style="width:140px" x-model="groupId" @change="page=1;load()">
                    <option value="">Todos os grupos</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary" @click="openModal()"><i class="bi bi-plus-lg me-1"></i>Novo Produto</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Produto</th><th>Grupo</th><th>Tipo</th><th>Preço Mensal</th><th>Serviços</th><th>Status</th><th class="text-center">Ações</th></tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>
                        </template>
                        <template x-for="p in products" :key="p.id">
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-light text-dark border p-2 fs-5" x-text="p.module === 'cpanel' ? '🖥️' : (p.module === 'none' ? '📦' : '☁️')"></span>
                                        <div>
                                            <div class="fw-semibold" x-text="p.name"></div>
                                            <div class="text-muted small" x-text="p.tagline || ''"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="small text-muted" x-text="p.group?.name || '—'"></td>
                                <td><span class="badge bg-light text-dark" x-text="p.type === 'hosting' ? 'Hospedagem' : (p.type === 'vps' ? 'VPS' : (p.type === 'domain' ? 'Domínio' : p.type || 'Outro'))"></span></td>
                                <td class="fw-semibold" x-text="p.prices?.monthly ? `R$ ${fmt(p.prices.monthly)}` : '—'"></td>
                                <td x-text="p.services_count ?? '—'"></td>
                                <td>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" :checked="p.active" @change="toggleActive(p)" style="cursor:pointer">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" @click="openModal(p)"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-outline-danger" @click="deleteProduct(p)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && products.length === 0">
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum produto encontrado.</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between" x-show="meta">
            <span class="text-muted small" x-text="`${meta?.from??0}–${meta?.to??0} de ${meta?.total??0}`"></span>
            <nav><ul class="pagination pagination-sm mb-0">
                <li class="page-item" :class="{disabled:page===1}"><button class="page-link" @click="page--;load()">«</button></li>
                <li class="page-item active"><a class="page-link" x-text="page"></a></li>
                <li class="page-item" :class="{disabled:page>=meta?.last_page}"><button class="page-link" @click="page++;load()">»</button></li>
            </ul></nav>
        </div>
    </div>

    {{-- Modal Produto --}}
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title fw-semibold" x-text="form.id ? 'Editar Produto' : 'Novo Produto'"></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <form @submit.prevent="save">
                    <div class="modal-body">
                        <ul class="nav nav-tabs mb-4">
                            <li class="nav-item"><a class="nav-link" :class="{active:tab==='info'}" @click="tab='info'" href="#">Informações</a></li>
                            <li class="nav-item"><a class="nav-link" :class="{active:tab==='pricing'}" @click="tab='pricing'" href="#">Preços</a></li>
                            <li class="nav-item"><a class="nav-link" :class="{active:tab==='features'}" @click="tab='features'" href="#">Recursos</a></li>
                        </ul>

                        <div x-show="tab==='info'" class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">Nome *</label><input type="text" class="form-control" x-model="form.name" required></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Tagline</label><input type="text" class="form-control" x-model="form.tagline" placeholder="Ex: Ideal para sites pessoais"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Grupo *</label>
                                <select class="form-select" x-model="form.product_group_id" required>
                                    @foreach($groups as $g)
                                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Tipo</label>
                                <select class="form-select" x-model="form.type">
                                    <option value="hosting">Hospedagem</option>
                                    <option value="reseller">Revenda</option>
                                    <option value="vps">VPS</option>
                                    <option value="domain">Domínio</option>
                                    <option value="other">Outro</option>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Módulo</label>
                                <select class="form-select" x-model="form.module">
                                    <option value="whm">WHM/cPanel</option>
                                    <option value="cpanel">cPanel</option>
                                    <option value="whmsonic">WHMSonic</option>
                                    <option value="aapanel">AAPanel</option>
                                    <option value="btpanel">BT Panel</option>
                                    <option value="plesk">Plesk</option>
                                    <option value="directadmin">DirectAdmin</option>
                                    <option value="ispconfig">ISPConfig</option>
                                    <option value="blesta">Blesta</option>
                                    <option value="cyberpanel">CyberPanel</option>
                                    <option value="webuzo">Webuzo</option>
                                    <option value="hestia">HestiaCP</option>
                                    <option value="virtualmin">Virtualmin</option>
                                    <option value="none">Nenhum</option>
                                </select>
                            </div>
                            <div class="col-12"><label class="form-label fw-semibold">Descrição</label><textarea class="form-control" rows="3" x-model="form.description"></textarea></div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="featuredCheck" x-model="form.featured">
                                    <label class="form-check-label fw-semibold" for="featuredCheck">Produto em Destaque</label>
                                </div>
                            </div>
                        </div>

                        <div x-show="tab==='pricing'" class="row g-3">
                            <template x-for="cycle in billing_cycles" :key="cycle.key">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" x-text="cycle.label"></label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" class="form-control" :x-model="`form.prices.${cycle.key}`" :value="form.prices[cycle.key]" @input="form.prices[cycle.key] = $event.target.value">
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="tab==='features'">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Recursos (um por linha)</label>
                                <textarea class="form-control font-monospace" rows="8" x-model="featuresText" placeholder="Hospedagem ilimitada&#10;Subdomínios ilimitados&#10;SSL grátis&#10;..."></textarea>
                                <div class="form-text">Cada linha vira um item da lista de recursos do produto.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <span x-text="form.id ? 'Salvar Alterações' : 'Criar Produto'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function productsTable() {
    return {
        products: [], meta: null, loading: false, search: '', groupId: '', page: 1,
        tab: 'info', saving: false, featuresText: '',
        billing_cycles: [
            {key:'monthly',label:'Mensal'},{key:'quarterly',label:'Trimestral'},{key:'semiannually',label:'Semestral'},
            {key:'annually',label:'Anual'},{key:'biennially',label:'Bienal'},{key:'triennially',label:'Trienal'}
        ],
        form: { id:null, name:'', tagline:'', description:'', product_group_id:'', type:'hosting', module:'cpanel', featured:false, prices:{} },

        async load() {
            this.loading = true;
            const p = new URLSearchParams({ search: this.search, group_id: this.groupId, page: this.page });
            const d = await HostPanel.fetch(`/admin/produtos?${p}`);
            this.products = d.data || [];
            this.meta = d.meta || d;
            this.loading = false;
        },

        openModal(product = null) {
            this.tab = 'info';
            if (product) {
                this.form = { ...product, prices: product.prices || {} };
                this.featuresText = (product.features || []).join('\n');
            } else {
                this.form = { id:null, name:'', tagline:'', description:'', product_group_id:'{{ $groups->first()?->id ?? "" }}', type:'hosting', module:'cpanel', featured:false, prices:{} };
                this.featuresText = '';
            }
            new bootstrap.Modal(document.getElementById('productModal')).show();
        },

        async save() {
            this.saving = true;
            this.form.features = this.featuresText.split('\n').map(s => s.trim()).filter(Boolean);
            const url    = this.form.id ? `/admin/produtos/${this.form.id}` : '/admin/produtos';
            const method = this.form.id ? 'PUT' : 'POST';
            const d      = await HostPanel.fetch(url, { method, body: JSON.stringify(this.form) });
            this.saving  = false;
            if (d.product) {
                bootstrap.Modal.getInstance(document.getElementById('productModal'))?.hide();
                HostPanel.toast('Produto salvo com sucesso!');
                this.load();
            } else HostPanel.toast(d.message || 'Erro ao salvar.', 'danger');
        },

        async toggleActive(p) {
            const d = await HostPanel.fetch(`/admin/produtos/${p.id}/status`, { method:'POST' });
            if (d.active !== undefined) p.active = d.active;
        },

        async deleteProduct(p) {
            if (!(await HostPanel.confirm({ text: `Excluir "${p.name}"? Esta acao nao pode ser desfeita.`, confirmButtonText: 'Sim, excluir' }))) return;
            const d = await HostPanel.fetch(`/admin/produtos/${p.id}`, { method:'DELETE' });
            HostPanel.toast(d.message);
            this.load();
        },

        fmt(v) { return parseFloat(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2}); },
        init() { this.load(); }
    }
}
</script>
@endpush
