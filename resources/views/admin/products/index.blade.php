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
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header border-0 bg-light pb-0">
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="modal-title fw-bold text-primary" x-text="form.id ? 'Editar Produto' : 'Novo Produto'"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <!-- Wizard Progress -->
                        <div class="d-flex justify-content-between position-relative mb-4 px-2">
                            <div class="position-absolute top-50 start-0 end-0 translate-middle-y bg-secondary bg-opacity-25" style="height: 2px; z-index: 0;"></div>
                            <div class="position-absolute top-50 start-0 translate-middle-y bg-primary transition-all" :style="'height: 2px; z-index: 0; width: ' + ((step-1)*50) + '%'"></div>
                            
                            <template x-for="i in 3">
                                <div class="position-relative" style="z-index: 1;">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm transition-all"
                                         :class="step >= i ? 'bg-primary text-white' : 'bg-white text-muted border'"
                                         style="width: 32px; height: 32px; font-weight: bold; font-size: 0.85rem;"
                                         x-text="i"></div>
                                    <div class="position-absolute start-50 translate-middle-x mt-1 text-nowrap" 
                                         style="font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;"
                                         :class="step >= i ? 'text-primary' : 'text-muted'"
                                         x-text="['Informações', 'Preços', 'Recursos'][i-1]"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <form @submit.prevent="save">
                    <div class="modal-body py-4" style="min-height: 400px; overflow-y: auto;">
                        <!-- Passo 1: Informações -->
                        <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4">
                            @if($groups->isEmpty())
                                <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center mb-4">
                                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold">Atenção: Nenhum grupo de produtos encontrado!</div>
                                        <div class="small">Você precisa criar ao menos um grupo antes de cadastrar produtos. <a href="{{ route('admin.products.groups') }}" class="alert-link">Clique aqui para criar agora.</a></div>
                                    </div>
                                </div>
                            @endif

                            <div class="row g-3">
                                <div class="col-12"><h6 class="fw-bold border-bottom pb-2 text-primary small ls-1 text-uppercase"><i class="bi bi-info-circle me-1"></i> 1. Informações Básicas</h6></div>
                                <div class="col-md-7">
                                    <label class="form-label fw-semibold">Nome do Produto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control shadow-sm" x-model="form.name" required placeholder="Ex: Hospedagem Start">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Tagline</label>
                                    <input type="text" class="form-control shadow-sm" x-model="form.tagline" placeholder="Ex: Ideal para sites pequenos">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Grupo de Produtos <span class="text-danger">*</span></label>
                                    <select class="form-select shadow-sm @if($groups->isEmpty()) is-invalid @endif" x-model="form.product_group_id" required>
                                        <option value="">Selecione um grupo...</option>
                                        @foreach($groups as $g)
                                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Módulo de Automação</label>
                                    <select class="form-select shadow-sm" x-model="form.module">
                                        <option value="none">Nenhum (Manual)</option>
                                        <option value="cpanel">cPanel / WHM</option>
                                        <option value="plesk">Plesk</option>
                                        <option value="directadmin">DirectAdmin</option>
                                        <option value="none">Outros...</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Descrição</label>
                                    <textarea class="form-control shadow-sm" rows="3" x-model="form.description" placeholder="Descreva as vantagens deste plano..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Passo 2: Preços -->
                        <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" style="display:none">
                            <div class="row g-3">
                                <div class="col-12"><h6 class="fw-bold border-bottom pb-2 text-primary small ls-1 text-uppercase"><i class="bi bi-currency-dollar me-1"></i> 2. Precificação (R$)</h6></div>
                                <template x-for="cycle in billing_cycles" :key="cycle.key">
                                    <div class="col-sm-6 col-md-4">
                                        <label class="form-label fw-semibold small" x-text="cycle.label"></label>
                                        <div class="input-group input-group-sm shadow-sm">
                                            <span class="input-group-text bg-light text-muted">R$</span>
                                            <input type="number" step="0.01" class="form-control fw-bold" 
                                                   :x-model="`form.prices.${cycle.key}`" 
                                                   :value="form.prices[cycle.key]" 
                                                   @input="form.prices[cycle.key] = $event.target.value"
                                                   placeholder="0,00">
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Passo 3: Recursos -->
                        <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" style="display:none">
                            <div class="row g-3">
                                <div class="col-12"><h6 class="fw-bold border-bottom pb-2 text-primary small ls-1 text-uppercase"><i class="bi bi-list-check me-1"></i> 3. Recursos e Destaque</h6></div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Recursos (um por linha)</label>
                                    <textarea class="form-control shadow-sm font-monospace" rows="6" x-model="featuresText" placeholder="Ex: 10GB de Disco&#10;Tráfego Ilimitado&#10;SSL Grátis"></textarea>
                                </div>
                                <div class="col-12 pt-2">
                                    <div class="card bg-light border-0">
                                        <div class="card-body p-3">
                                            <div class="form-check form-switch p-0 d-flex align-items-center">
                                                <input class="form-check-input" type="checkbox" id="featuredWizard" x-model="form.featured" style="margin-left: 0; width: 3em; height: 1.5em; cursor: pointer;">
                                                <label class="form-check-label fw-bold ms-2" for="featuredWizard" style="cursor: pointer;">Destacar este produto na página inicial</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <div class="d-flex justify-content-between w-100">
                            <div>
                                <button type="button" class="btn btn-outline-secondary px-4 me-2" data-bs-dismiss="modal" x-show="step === 1">Fechar</button>
                                <button type="button" class="btn btn-outline-secondary px-4 me-2" @click="step--" x-show="step > 1">Voltar</button>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary px-5 shadow-sm" @click="step++" x-show="step < 3">Próximo <i class="bi bi-arrow-right ms-1"></i></button>
                                <button type="submit" class="btn btn-primary px-5 shadow-sm" x-show="step === 3" :disabled="saving">
                                    <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                                    <span x-text="form.id ? 'Salvar Alterações' : 'Criar Produto'"></span>
                                </button>
                            </div>
                        </div>
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
        step: 1, saving: false, featuresText: '',
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
            this.step = 1;
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
