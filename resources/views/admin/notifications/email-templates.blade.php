@extends('admin.layouts.app')
@section('title', 'Templates de E-mail')
@section('page-title', 'Templates de E-mail')
@section('breadcrumb')
    <li class="breadcrumb-item">Notificações</li>
    <li class="breadcrumb-item active">Templates de E-mail</li>
@endsection

@section('content')
<div x-data="emailTemplates()">

    <div class="row g-4">
        {{-- Lista de Templates --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Templates</span>
                    <input type="text" class="form-control form-control-sm" style="width:140px" placeholder="Buscar..."
                           x-model.debounce.300="search">
                </div>
                <div class="list-group list-group-flush">
                    <template x-if="loading">
                        <div class="list-group-item text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
                    </template>
                    <template x-for="t in filteredTemplates" :key="t.id">
                        <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-start py-3"
                                :class="selected?.id === t.id ? 'active' : ''"
                                @click="selectTemplate(t)">
                            <div class="text-start">
                                <div class="fw-semibold small" :class="selected?.id === t.id ? 'text-white' : 'text-dark'" x-text="t.name"></div>
                                <small :class="selected?.id === t.id ? 'text-white opacity-75' : 'text-muted'" x-text="t.slug"></small>
                            </div>
                            <span class="badge ms-2 flex-shrink-0" :class="t.active ? (selected?.id===t.id?'bg-white text-success':'bg-success') : 'bg-secondary'">
                                <span x-text="t.active ? '●' : '○'"></span>
                            </span>
                        </button>
                    </template>
                    <template x-if="!loading && filteredTemplates.length === 0">
                        <div class="list-group-item text-muted text-center py-3">Nenhum template encontrado.</div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Editor de Template --}}
        <div class="col-lg-8">
            <div class="card h-100" x-show="!selected">
                <div class="card-body d-flex align-items-center justify-content-center text-muted">
                    <div class="text-center">
                        <i class="bi bi-envelope-open fs-1 mb-3 d-block opacity-25"></i>
                        <p>Selecione um template para editar</p>
                    </div>
                </div>
            </div>

            <div class="card" x-show="selected">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0" x-text="selected?.name"></h6>
                        <small class="text-muted font-monospace" x-text="selected?.slug"></small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" x-model="form.active">
                            <label class="form-check-label small">Ativo</label>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" @click="previewTemplate()">
                            <i class="bi bi-eye me-1"></i>Preview
                        </button>
                        <button class="btn btn-sm btn-primary" @click="saveTemplate()" :disabled="saving">
                            <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>Salvar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assunto</label>
                        <input type="text" class="form-control" x-model="form.subject">
                    </div>

                    {{-- Variáveis Disponíveis --}}
                    <div class="mb-3 p-3 bg-light rounded-3">
                        <div class="fw-semibold small mb-2">Variáveis Disponíveis</div>
                        <div class="d-flex flex-wrap gap-1">
                            <template x-for="v in (selected?.variables ?? [])" :key="v">
                                <code class="small bg-white border rounded px-2 py-1 cursor-pointer"
                                      x-text="'{{' + v + '}}'"
                                      @click="insertVariable(v)"
                                      title="Clique para copiar"></code>
                            </template>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Corpo do E-mail (HTML)</label>
                        <textarea class="form-control font-monospace" rows="18" x-model="form.body" id="templateBody"
                                  style="font-size:.8rem;resize:vertical"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Preview --}}
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview: <span x-text="selected?.name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="previewFrame" style="width:100%;height:500px;border:none"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function emailTemplates() {
    return {
        templates: [], loading: false, selected: null, saving: false, search: '',
        form: { subject: '', body: '', active: true },

        get filteredTemplates() {
            if (!this.search) return this.templates;
            const s = this.search.toLowerCase();
            return this.templates.filter(t => t.name.toLowerCase().includes(s) || t.slug.includes(s));
        },

        async load() {
            this.loading = true;
            this.templates = await HostPanel.fetch('{{ route("admin.notifications.email.templates") }}', {
                headers: { 'Accept': 'application/json' }
            });
            this.loading = false;
        },

        selectTemplate(t) {
            this.selected = t;
            this.form = { subject: t.subject ?? '', body: t.body ?? '', active: !!t.active };
        },

        async saveTemplate() {
            this.saving = true;
            const d = await HostPanel.fetch(`{{ url('admin/notificacoes/templates-email') }}/${this.selected.id}`, {
                method: 'PUT', body: JSON.stringify(this.form)
            });
            this.saving = false;
            HostPanel.toast(d.message ?? 'Template salvo!');
            if (d.template) {
                const idx = this.templates.findIndex(t => t.id === this.selected.id);
                if (idx >= 0) this.templates[idx] = { ...this.templates[idx], ...d.template };
            }
        },

        insertVariable(v) {
            const ta = document.getElementById('templateBody');
            const start = ta.selectionStart, end = ta.selectionEnd;
            const before = this.form.body.substring(0, start);
            const after  = this.form.body.substring(end);
            this.form.body = before + '{{' + v + '}}' + after;
            this.$nextTick(() => { ta.selectionStart = ta.selectionEnd = start + v.length + 4; ta.focus(); });
        },

        previewTemplate() {
            const frame = document.getElementById('previewFrame');
            frame.srcdoc = this.form.body.replace(/\{\{(\w+)\}\}/g, (_, k) => `<mark>{{ ${k} }}</mark>`);
            new bootstrap.Modal(document.getElementById('previewModal')).show();
        },

        init() { this.load(); }
    }
}
</script>
@endpush
