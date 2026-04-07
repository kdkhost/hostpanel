@extends('admin.layouts.app')
@section('title', 'Gerenciador de Conteúdo')
@section('page-title', 'Gerenciador de Conteúdo (CMS)')
@section('breadcrumb')
    <li class="breadcrumb-item active">CMS</li>
@endsection

@section('content')
<div x-data="cmsIndex()">

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-4">
        @foreach([['pages','Páginas','bi-file-text'],['announcements','Avisos','bi-megaphone'],['faqs','FAQs','bi-question-circle'],['banners','Banners','bi-image']] as [$key,$lbl,$icon])
        <li class="nav-item">
            <button class="nav-link" :class="tab==='{{ $key }}' ? 'active' : ''" @click="tab='{{ $key }}'; load()">
                <i class="bi {{ $icon }} me-1"></i>{{ $lbl }}
            </button>
        </li>
        @endforeach
    </ul>

    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <input type="text" class="form-control form-control-sm" style="width:240px" placeholder="Buscar..."
               x-model.debounce.300="search">
        <button class="btn btn-primary btn-sm" @click="openCreate()">
            <i class="bi bi-plus-lg me-1"></i>Novo <span class="text-capitalize" x-text="tabLabel()"></span>
        </button>
    </div>

    {{-- Páginas --}}
    <div class="card" x-show="tab === 'pages'">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light"><tr><th>Título</th><th>Slug</th><th>Status</th><th class="text-center">Ações</th></tr></thead>
                <tbody>
                    <template x-if="loading"><tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr></template>
                    <template x-for="item in filtered" :key="item.id">
                        <tr>
                            <td class="fw-semibold" x-text="item.title"></td>
                            <td><code x-text="item.slug"></code></td>
                            <td><span class="badge" :class="item.active ? 'bg-success' : 'bg-secondary'" x-text="item.active ? 'Ativo' : 'Inativo'"></span></td>
                            <td class="text-center d-flex justify-content-center gap-1">
                                <a :href="'/'+item.slug" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i></a>
                                <button class="btn btn-sm btn-outline-primary" @click="openEdit(item)"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" @click="deleteItem(item)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && filtered.length===0"><tr><td colspan="4" class="text-center text-muted py-4">Nenhuma página encontrada.</td></tr></template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Avisos --}}
    <div class="card" x-show="tab === 'announcements'">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light"><tr><th>Título</th><th>Tipo</th><th>Expiração</th><th>Status</th><th class="text-center">Ações</th></tr></thead>
                <tbody>
                    <template x-if="loading"><tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr></template>
                    <template x-for="item in filtered" :key="item.id">
                        <tr>
                            <td class="fw-semibold" x-text="item.title"></td>
                            <td><span class="badge" :class="{'bg-info':'info','bg-warning text-dark':'warning','bg-danger':'danger','bg-success':'success'}[item.type]??'bg-secondary'" x-text="item.type"></span></td>
                            <td class="text-muted small" x-text="item.expires_at ? new Date(item.expires_at).toLocaleDateString('pt-BR') : '—'"></td>
                            <td><span class="badge" :class="item.active ? 'bg-success' : 'bg-secondary'" x-text="item.active ? 'Ativo' : 'Inativo'"></span></td>
                            <td class="text-center d-flex justify-content-center gap-1">
                                <button class="btn btn-sm btn-outline-primary" @click="openEdit(item)"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" @click="deleteItem(item)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && filtered.length===0"><tr><td colspan="5" class="text-center text-muted py-4">Nenhum aviso encontrado.</td></tr></template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- FAQs --}}
    <div class="card" x-show="tab === 'faqs'">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light"><tr><th>Pergunta</th><th>Categoria</th><th>Ordem</th><th class="text-center">Ações</th></tr></thead>
                <tbody>
                    <template x-if="loading"><tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr></template>
                    <template x-for="item in filtered" :key="item.id">
                        <tr>
                            <td class="fw-semibold" x-text="item.question"></td>
                            <td class="text-muted small" x-text="item.category ?? '—'"></td>
                            <td class="text-muted" x-text="item.sort_order ?? 0"></td>
                            <td class="text-center d-flex justify-content-center gap-1">
                                <button class="btn btn-sm btn-outline-primary" @click="openEdit(item)"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" @click="deleteItem(item)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && filtered.length===0"><tr><td colspan="4" class="text-center text-muted py-4">Nenhuma FAQ encontrada.</td></tr></template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Banners --}}
    <div class="card" x-show="tab === 'banners'">
        <div class="card-body">
            <div class="row g-3">
                <template x-if="loading"><div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div></template>
                <template x-for="item in filtered" :key="item.id">
                    <div class="col-md-4">
                        <div class="card border">
                            <img :src="item.image" class="card-img-top" style="height:140px;object-fit:cover" alt="" onerror="this.src='/images/placeholder.png'">
                            <div class="card-body p-3">
                                <div class="fw-semibold small mb-1" x-text="item.title"></div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge" :class="item.active ? 'bg-success' : 'bg-secondary'" x-text="item.active ? 'Ativo' : 'Inativo'"></span>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary" @click="openEdit(item)"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" @click="deleteItem(item)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="!loading && filtered.length===0"><div class="col-12 text-center text-muted py-4">Nenhum banner encontrado.</div></template>
            </div>
        </div>
    </div>

    {{-- Modal Editor --}}
    <div class="modal fade" id="cmsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="(form.id ? 'Editar' : 'Novo') + ' ' + tabLabel()"></h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <template x-if="tab === 'pages' || tab === 'announcements'">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.title">
                            </div>
                        </template>
                        <template x-if="tab === 'faqs'">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Pergunta <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.question">
                            </div>
                        </template>
                        <template x-if="tab === 'banners'">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Título</label>
                                <input type="text" class="form-control" x-model="form.title">
                                <label class="form-label fw-semibold mt-2">URL da Imagem <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.image" placeholder="https://...">
                                <label class="form-label fw-semibold mt-2">Link</label>
                                <input type="text" class="form-control" x-model="form.link" placeholder="https://...">
                            </div>
                        </template>
                        <template x-if="tab === 'announcements'">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipo</label>
                                <select class="form-select" x-model="form.type">
                                    <option value="info">Informação</option>
                                    <option value="warning">Aviso</option>
                                    <option value="danger">Alerta</option>
                                    <option value="success">Sucesso</option>
                                </select>
                            </div>
                        </template>
                        <template x-if="tab !== 'banners'">
                            <div class="col-12">
                                <label class="form-label fw-semibold" x-text="tab==='faqs' ? 'Resposta *' : 'Conteúdo *'"></label>
                                <textarea class="form-control" rows="8" x-model="form.content ?? form.answer ?? form.body"></textarea>
                            </div>
                        </template>
                        <div class="col-auto">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" x-model="form.active">
                                <label class="form-check-label fw-semibold">Ativo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" @click="save()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CMS_ENDPOINTS = {
    pages:         { list: '{{ route("admin.cms.pages") }}',         store: '{{ url("admin/cms/paginas") }}' },
    announcements: { list: '{{ route("admin.cms.announcements") }}', store: '{{ url("admin/cms/avisos") }}' },
    faqs:          { list: '{{ route("admin.cms.faqs") }}',          store: '{{ url("admin/cms/faqs") }}' },
    banners:       { list: '{{ route("admin.cms.banners") }}',       store: '{{ url("admin/cms/banners") }}' },
};

function cmsIndex() {
    return {
        tab: 'pages', items: [], loading: false, saving: false, search: '',
        form: { id: null, title: '', content: '', active: true },

        get filtered() {
            if (!this.search) return this.items;
            const s = this.search.toLowerCase();
            return this.items.filter(i => (i.title ?? i.question ?? '').toLowerCase().includes(s));
        },

        async load() {
            this.loading = true;
            const ep = CMS_ENDPOINTS[this.tab];
            const d  = await HostPanel.fetch(ep.list);
            this.items   = Array.isArray(d) ? d : (d.data ?? []);
            this.loading = false;
        },

        openCreate() { this.form = { id:null, title:'', content:'', question:'', answer:'', active:true, type:'info', image:'', link:'', sort_order:0 }; new bootstrap.Modal(document.getElementById('cmsModal')).show(); },
        openEdit(i) { this.form = { ...i }; new bootstrap.Modal(document.getElementById('cmsModal')).show(); },

        async save() {
            this.saving = true;
            const ep  = CMS_ENDPOINTS[this.tab];
            const url = this.form.id ? `${ep.store}/${this.form.id}` : ep.store;
            const d   = await HostPanel.fetch(url, { method: this.form.id ? 'PUT' : 'POST', body: JSON.stringify(this.form) });
            this.saving = false;
            HostPanel.toast(d.message ?? 'Salvo!');
            if (!d.message?.includes('Erro')) { bootstrap.Modal.getInstance(document.getElementById('cmsModal'))?.hide(); this.load(); }
        },

        async deleteItem(i) {
            if (!confirm('Excluir este item?')) return;
            const ep = CMS_ENDPOINTS[this.tab];
            const d  = await HostPanel.fetch(`${ep.store}/${i.id}`, { method:'DELETE' });
            HostPanel.toast(d.message);
            this.load();
        },

        tabLabel() { return { pages:'Página', announcements:'Aviso', faqs:'FAQ', banners:'Banner' }[this.tab] ?? this.tab; },
        init() { this.load(); }
    }
}
</script>
@endpush
