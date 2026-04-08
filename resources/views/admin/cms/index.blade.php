@extends('admin.layouts.app')
@section('title', 'Gerenciador de Conteudo')
@section('page-title', 'Gerenciador de Conteudo (CMS)')
@section('breadcrumb')
    <li class="breadcrumb-item active">CMS</li>
@endsection

@section('content')
<div x-data="cmsIndex()">
    <ul class="nav nav-tabs mb-4">
        @foreach([['pages','Paginas','bi-file-text'],['announcements','Avisos','bi-megaphone'],['faqs','FAQs','bi-question-circle'],['banners','Banners','bi-image']] as [$key,$label,$icon])
        <li class="nav-item">
            <button class="nav-link" :class="tab === '{{ $key }}' ? 'active' : ''" @click="tab='{{ $key }}'; load()">
                <i class="bi {{ $icon }} me-1"></i>{{ $label }}
            </button>
        </li>
        @endforeach
    </ul>

    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <input type="text" class="form-control form-control-sm" style="width:240px" placeholder="Buscar..." x-model.debounce.300="search">
        <button class="btn btn-primary btn-sm" @click="openCreate()">
            <i class="bi bi-plus-lg me-1"></i>Novo <span class="text-capitalize" x-text="tabLabel()"></span>
        </button>
    </div>

    <div class="card" x-show="tab === 'pages'">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr><th>Titulo</th><th>Slug</th><th>Status</th><th class="text-center">Acoes</th></tr>
                </thead>
                <tbody>
                    <template x-if="loading"><tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr></template>
                    <template x-for="item in filtered" :key="item.id">
                        <tr>
                            <td class="fw-semibold" x-text="item.title"></td>
                            <td><code x-text="item.slug"></code></td>
                            <td><span class="badge" :class="item.published ? 'bg-success' : 'bg-secondary'" x-text="item.published ? 'Ativo' : 'Inativo'"></span></td>
                            <td class="text-center d-flex justify-content-center gap-1">
                                <a :href="'/' + item.slug" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i></a>
                                <button class="btn btn-sm btn-outline-primary" @click="openEdit(item)"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" @click="deleteItem(item)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && filtered.length === 0"><tr><td colspan="4" class="text-center text-muted py-4">Nenhuma pagina encontrada.</td></tr></template>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" x-show="tab === 'announcements'">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr><th>Titulo</th><th>Tipo</th><th>Publicacao</th><th>Status</th><th class="text-center">Acoes</th></tr>
                </thead>
                <tbody>
                    <template x-if="loading"><tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr></template>
                    <template x-for="item in filtered" :key="item.id">
                        <tr>
                            <td class="fw-semibold" x-text="item.title"></td>
                            <td><span class="badge" :class="announcementBadge(item.type)" x-text="item.type"></span></td>
                            <td class="text-muted small" x-text="formatDate(item.published_at || item.created_at)"></td>
                            <td><span class="badge" :class="item.published ? 'bg-success' : 'bg-secondary'" x-text="item.published ? 'Ativo' : 'Inativo'"></span></td>
                            <td class="text-center d-flex justify-content-center gap-1">
                                <button class="btn btn-sm btn-outline-primary" @click="openEdit(item)"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" @click="deleteItem(item)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && filtered.length === 0"><tr><td colspan="5" class="text-center text-muted py-4">Nenhum aviso encontrado.</td></tr></template>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" x-show="tab === 'faqs'">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr><th>Pergunta</th><th>Categoria</th><th>Ordem</th><th class="text-center">Acoes</th></tr>
                </thead>
                <tbody>
                    <template x-if="loading"><tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr></template>
                    <template x-for="item in filtered" :key="item.id">
                        <tr>
                            <td class="fw-semibold" x-text="item.question"></td>
                            <td class="text-muted small" x-text="item.category ?? '-'"></td>
                            <td class="text-muted" x-text="item.sort_order ?? 0"></td>
                            <td class="text-center d-flex justify-content-center gap-1">
                                <button class="btn btn-sm btn-outline-primary" @click="openEdit(item)"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" @click="deleteItem(item)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && filtered.length === 0"><tr><td colspan="4" class="text-center text-muted py-4">Nenhuma FAQ encontrada.</td></tr></template>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" x-show="tab === 'banners'">
        <div class="card-body">
            <div class="row g-3">
                <template x-if="loading"><div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div></template>
                <template x-for="item in filtered" :key="item.id">
                    <div class="col-md-4">
                        <div class="card border h-100">
                            <img :src="item.image_url || item.image" class="card-img-top" style="height:160px;object-fit:cover" alt="Banner" onerror="this.src='/images/placeholder.png'">
                            <div class="card-body p-3">
                                <div class="fw-semibold small mb-1" x-text="item.title || item.name"></div>
                                <div class="small text-muted mb-3" x-text="item.link || '-'"></div>
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
                <template x-if="!loading && filtered.length === 0"><div class="col-12 text-center text-muted py-4">Nenhum banner encontrado.</div></template>
            </div>
        </div>
    </div>

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
                                <label class="form-label fw-semibold">Titulo <span class="text-danger">*</span></label>
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
                                <label class="form-label fw-semibold">Titulo do banner</label>
                                <input type="text" class="form-control" x-model="form.title" placeholder="Banner principal">

                                <label class="form-label fw-semibold mt-3">Imagem do banner <span class="text-danger">*</span></label>
                                <input type="file" class="d-none" x-ref="bannerImageInput" accept="image/*" @change="bannerUploader.onInputChange($event)">
                                <div class="hp-dropzone p-4" :class="{ 'is-dragging': bannerUploader.dragging, 'is-uploading': saving }"
                                    style="cursor:pointer"
                                    @click="bannerUploader.openPicker()"
                                    @dragover.prevent="bannerUploader.handleDragOver($event)"
                                    @dragleave="bannerUploader.handleDragLeave($event)"
                                    @drop="bannerUploader.handleDrop($event)">
                                    <div class="d-flex flex-column align-items-center text-center">
                                        <div class="rounded-4 bg-primary-subtle text-primary d-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;font-size:1.75rem">
                                            <i class="bi bi-image"></i>
                                        </div>
                                        <div class="fw-semibold text-dark">Arraste e solte a imagem do banner</div>
                                        <div class="small text-muted mt-1">ou clique para selecionar um arquivo JPG, PNG, WEBP ou GIF</div>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 mt-3" x-show="bannerUploader.files.length > 0">
                                    <template x-for="(file, index) in bannerUploader.files" :key="`${file.name}-${file.lastModified}`">
                                        <span class="hp-file-pill small">
                                            <i class="bi bi-image"></i>
                                            <span x-text="`${file.name} (${HostPanel.formatBytes(file.size)})`"></span>
                                            <button type="button" class="btn btn-link p-0 text-primary text-decoration-none" @click.stop="bannerUploader.removeFile(index)">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </span>
                                    </template>
                                </div>

                                <div class="rounded-4 border p-3 mt-3" x-show="form.image_url && !bannerUploader.hasFiles()">
                                    <div class="small text-muted mb-2">Imagem atual</div>
                                    <img :src="form.image_url" class="img-fluid rounded-3 border" alt="Banner atual" style="max-height:220px;object-fit:cover">
                                </div>

                                <div class="rounded-4 border border-success-subtle bg-success-subtle bg-opacity-25 p-3 mt-3" x-show="saving && bannerUploader.hasFiles()">
                                    <div class="d-flex justify-content-between align-items-center gap-3 small">
                                        <div class="fw-semibold text-success-emphasis">Enviando imagem</div>
                                        <div class="fw-semibold text-success-emphasis" x-text="bannerUploader.progressLabel()"></div>
                                    </div>
                                    <div class="hp-progress-track mt-3">
                                        <div class="hp-progress-bar" :style="`width:${bannerUploader.uploadProgress}%`"></div>
                                    </div>
                                    <div class="row g-3 mt-1 small text-success-emphasis">
                                        <div class="col-md-4">
                                            <div class="fw-semibold text-uppercase">Enviado</div>
                                            <div x-text="`${bannerUploader.uploadedLabel()} de ${bannerUploader.totalBytesLabel()}`"></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="fw-semibold text-uppercase">Velocidade</div>
                                            <div x-text="bannerUploader.speedLabel()"></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="fw-semibold text-uppercase">Tempo restante</div>
                                            <div x-text="bannerUploader.remainingLabel()"></div>
                                        </div>
                                    </div>
                                </div>

                                <label class="form-label fw-semibold mt-3">Link do banner</label>
                                <input type="text" class="form-control" x-model="form.link" placeholder="https://...">
                            </div>
                        </template>

                        <template x-if="tab === 'announcements'">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipo</label>
                                <select class="form-select" x-model="form.type">
                                    <option value="info">Informacao</option>
                                    <option value="warning">Aviso</option>
                                    <option value="danger">Alerta</option>
                                    <option value="success">Sucesso</option>
                                </select>
                            </div>
                        </template>

                        <template x-if="tab !== 'banners'">
                            <div class="col-12">
                                <label class="form-label fw-semibold" x-text="tab === 'faqs' ? 'Resposta *' : 'Conteudo *'"></label>
                                <textarea class="form-control" rows="8" x-model="contentField"></textarea>
                            </div>
                        </template>

                        <div class="col-auto">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" x-model="activeField">
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
    pages: { list: '{{ route("admin.cms.pages") }}', store: '{{ url("admin/cms/paginas") }}' },
    announcements: { list: '{{ route("admin.cms.announcements") }}', store: '{{ url("admin/cms/anuncios") }}' },
    faqs: { list: '{{ route("admin.cms.faqs") }}', store: '{{ url("admin/cms/faqs") }}' },
    banners: { list: '{{ route("admin.cms.banners") }}', store: '{{ url("admin/cms/banners") }}' },
};

function cmsIndex() {
    return {
        tab: 'pages',
        items: [],
        loading: false,
        saving: false,
        search: '',
        form: {},
        bannerUploader: HostPanel.createUploadState({
            accept: 'image/*',
            multiple: false,
            maxFiles: 1,
            maxFileSize: 10 * 1024 * 1024,
        }),

        get filtered() {
            if (!this.search) {
                return this.items;
            }

            const term = this.search.toLowerCase();
            return this.items.filter((item) => (
                (item.title ?? item.name ?? item.question ?? '').toLowerCase().includes(term)
            ));
        },

        get contentField() {
            if (this.tab === 'faqs') {
                return this.form.answer ?? '';
            }

            return this.form.content ?? '';
        },

        set contentField(value) {
            if (this.tab === 'faqs') {
                this.form.answer = value;
                return;
            }

            this.form.content = value;
        },

        get activeField() {
            if (this.tab === 'pages' || this.tab === 'announcements') {
                return !!this.form.published;
            }

            return this.form.active !== false;
        },

        set activeField(value) {
            if (this.tab === 'pages' || this.tab === 'announcements') {
                this.form.published = value;
                return;
            }

            this.form.active = value;
        },

        announcementBadge(type) {
            return {
                info: 'bg-info',
                warning: 'bg-warning text-dark',
                danger: 'bg-danger',
                success: 'bg-success',
            }[type] || 'bg-secondary';
        },

        formatDate(value) {
            if (!value) {
                return '-';
            }

            return new Date(value).toLocaleDateString('pt-BR');
        },

        modal() {
            return bootstrap.Modal.getOrCreateInstance(document.getElementById('cmsModal'));
        },

        resetForm() {
            this.form = {
                id: null,
                title: '',
                content: '',
                question: '',
                answer: '',
                active: true,
                published: true,
                type: 'info',
                image: '',
                image_url: '',
                link: '',
                sort_order: 0,
            };
            this.bannerUploader.clearFiles();
        },

        async load() {
            this.loading = true;
            const endpoint = CMS_ENDPOINTS[this.tab];
            const data = await HostPanel.fetch(endpoint.list);
            this.items = Array.isArray(data) ? data : (data.data ?? []);
            this.loading = false;
        },

        openCreate() {
            this.resetForm();
            if (this.tab === 'banners') {
                this.$nextTick(() => this.bannerUploader.attachInput(this.$refs.bannerImageInput));
            }
            this.modal().show();
        },

        openEdit(item) {
            this.resetForm();
            this.form = {
                ...this.form,
                ...item,
                active: item.active ?? true,
                published: item.published ?? true,
            };
            if (this.tab === 'banners') {
                this.$nextTick(() => this.bannerUploader.attachInput(this.$refs.bannerImageInput));
            }
            this.modal().show();
        },

        async save() {
            this.saving = true;
            const endpoint = CMS_ENDPOINTS[this.tab];
            const url = this.form.id ? `${endpoint.store}/${this.form.id}` : endpoint.store;
            let data;

            try {
                if (this.tab === 'banners') {
                    const formData = new FormData();
                    formData.append('title', this.form.title || '');
                    formData.append('link', this.form.link || '');
                    formData.append('sort_order', this.form.sort_order || 0);
                    formData.append('active', this.form.active ? '1' : '0');

                    if (this.form.image && !this.bannerUploader.hasFiles()) {
                        formData.append('image', this.form.image);
                    }

                    if (this.bannerUploader.hasFiles()) {
                        formData.append('image_file', this.bannerUploader.files[0]);
                    }

                    this.bannerUploader.startUpload();
                    data = await HostPanel.upload({
                        url,
                        method: this.form.id ? 'POST' : 'POST',
                        body: this.appendMethodOverride(formData, this.form.id ? 'PUT' : 'POST'),
                        onProgress: (progress) => this.bannerUploader.updateProgress(progress),
                    });
                    this.bannerUploader.finishUpload();
                } else {
                    data = await HostPanel.fetch(url, {
                        method: this.form.id ? 'PUT' : 'POST',
                        body: JSON.stringify(this.form),
                    });
                }
            } catch (error) {
                this.bannerUploader.failUpload();
                data = { ok: false, message: 'Falha de rede ao salvar o registro.' };
            }

            this.saving = false;

            if (!data.ok) {
                const message = typeof data.errors === 'object'
                    ? Object.values(data.errors).flat().join(', ')
                    : (data.message || 'Nao foi possivel salvar.');
                HostPanel.toast(message, 'danger');
                return;
            }

            HostPanel.toast(data.message || 'Salvo!');
            this.modal().hide();
            this.load();
        },

        appendMethodOverride(formData, method) {
            if (method !== 'POST') {
                formData.append('_method', method);
            }

            return formData;
        },

        async deleteItem(item) {
            if (!(await HostPanel.confirm({ text: 'Excluir este item?', confirmButtonText: 'Sim, excluir' }))) {
                return;
            }

            const endpoint = CMS_ENDPOINTS[this.tab];
            const data = await HostPanel.fetch(`${endpoint.store}/${item.id}`, { method: 'DELETE' });
            HostPanel.toast(data.message || 'Registro excluido.');
            this.load();
        },

        tabLabel() {
            return {
                pages: 'pagina',
                announcements: 'aviso',
                faqs: 'FAQ',
                banners: 'banner',
            }[this.tab] ?? this.tab;
        },

        init() {
            this.resetForm();
            this.load();
        },
    };
}
</script>
@endpush
