@extends('admin.layouts.app')
@section('title', 'Kanban — ' . ucfirst($type))
@section('page-title', 'Kanban: ' . ucfirst($type))
@section('breadcrumb')
    <li class="breadcrumb-item">Kanban</li>
    <li class="breadcrumb-item active text-capitalize">{{ $type }}</li>
@endsection

@push('styles')
<style>
.kanban-wrap  { display:flex; gap:1rem; overflow-x:auto; padding-bottom:1rem; min-height:calc(100vh - 220px); }
.kb-col       { flex:0 0 270px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; display:flex; flex-direction:column; max-height:calc(100vh - 220px); }
.kb-col-head  { padding:10px 14px; border-bottom:1px solid #e2e8f0; background:#f8fafc; border-radius:12px 12px 0 0; }
.kb-items     { overflow-y:auto; padding:8px; flex:1; }
.kb-card      { background:#fff; border-radius:8px; border:1px solid #e2e8f0; padding:10px 12px; margin-bottom:8px; cursor:grab; transition:.15s; user-select:none; }
.kb-card:hover{ border-color:#1a56db; box-shadow:0 2px 8px rgba(26,86,219,.12); }
.kb-card.dragging { opacity:.5; cursor:grabbing; }
.kb-col.drag-over { background:#eff6ff; border-color:#1a56db; }
</style>
@endpush

@section('content')
<div x-data="genericKanban('{{ $type }}')" class="pb-2">

    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-secondary" x-text="totalTasks + ' tarefas'"></span>
        </div>
        <button class="btn btn-primary btn-sm" @click="openCreate()">
            <i class="bi bi-plus-lg me-1"></i>Nova Tarefa
        </button>
    </div>

    {{-- Board --}}
    <div class="kanban-wrap" x-show="!loading">
        <template x-for="col in columns" :key="col.id">
            <div class="kb-col"
                 @dragover.prevent="dragOver(col)"
                 @dragleave="dragLeave(col)"
                 @drop="dropTask(col)"
                 :class="{ 'drag-over': col.dragOver }">

                <div class="kb-col-head">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold small" x-text="col.name"></span>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-white border text-dark" x-text="col.tasks?.length ?? 0"></span>
                            <button class="btn btn-sm btn-link p-0 text-muted" @click="openCreate(col.id)" title="Adicionar tarefa">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="kb-items">
                    <template x-for="task in col.tasks" :key="task.id">
                        <div class="kb-card"
                             draggable="true"
                             @dragstart="dragStart(task, col)"
                             @click="openTask(task)">

                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="badge" :class="{
                                    'bg-secondary': task.priority==='low',
                                    'bg-warning text-dark': task.priority==='medium',
                                    'bg-danger': task.priority==='high',
                                    'bg-purple text-white': task.priority==='urgent',
                                }" style="font-size:.65rem" x-text="task.priority?.toUpperCase()"></span>
                                <button class="btn btn-link btn-sm p-0 text-muted" @click.stop="deleteTask(task)">
                                    <i class="bi bi-x" style="font-size:.85rem"></i>
                                </button>
                            </div>

                            <div class="fw-semibold small mb-2 text-dark" x-text="task.title"></div>

                            <div class="text-muted small" x-show="task.description" x-text="task.description?.slice(0,60) + (task.description?.length>60?'…':'')"></div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="text-muted" style="font-size:.7rem" x-text="task.due_date ? '📅 '+new Date(task.due_date).toLocaleDateString('pt-BR') : ''"></span>
                                <div x-show="task.assignee" :title="task.assignee?.name">
                                    <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center fw-bold"
                                         style="width:20px;height:20px;font-size:.65rem"
                                         x-text="(task.assignee?.name ?? '').charAt(0).toUpperCase()"></div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-1 mt-2" x-show="task.tags?.length">
                                <template x-for="tag in (task.tags ?? [])" :key="tag">
                                    <span class="badge bg-light text-secondary border" style="font-size:.65rem" x-text="tag"></span>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="text-center text-muted py-3 small" x-show="!col.tasks?.length">Sem tarefas</div>
                </div>
            </div>
        </template>
    </div>

    <div class="text-center py-5" x-show="loading"><div class="spinner-border text-primary"></div></div>

    {{-- Modal Nova Tarefa --}}
    <div class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="taskForm.id ? 'Editar Tarefa' : 'Nova Tarefa'"></h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Coluna</label>
                            <select class="form-select" x-model="taskForm.kanban_column_id">
                                <template x-for="col in columns" :key="col.id">
                                    <option :value="col.id" x-text="col.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="taskForm.title">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrição</label>
                            <textarea class="form-control" rows="3" x-model="taskForm.description"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Prioridade</label>
                            <select class="form-select" x-model="taskForm.priority">
                                <option value="low">Baixa</option>
                                <option value="medium">Média</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Vencimento</label>
                            <input type="date" class="form-control" x-model="taskForm.due_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" @click="saveTask()" :disabled="saving">
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
function genericKanban(type) {
    return {
        columns: [], loading: false, saving: false,
        draggedTask: null, draggedFrom: null,
        taskForm: { id: null, kanban_column_id: null, title: '', description: '', priority: 'medium', due_date: '' },

        get totalTasks() { return this.columns.reduce((s, c) => s + (c.tasks?.length ?? 0), 0); },

        async load() {
            this.loading = true;
            const d = await HostPanel.fetch(`{{ url('admin/kanban') }}/${type}`);
            this.columns = (d.columns ?? []).map(c => ({ ...c, dragOver: false }));
            this.loading = false;
        },

        openCreate(colId = null) {
            this.taskForm = { id: null, kanban_column_id: colId ?? this.columns[0]?.id, title: '', description: '', priority: 'medium', due_date: '' };
            new bootstrap.Modal(document.getElementById('taskModal')).show();
        },

        openTask(task) {
            this.taskForm = { id: task.id, kanban_column_id: task.kanban_column_id, title: task.title, description: task.description ?? '', priority: task.priority, due_date: task.due_date ? task.due_date.slice(0,10) : '' };
            new bootstrap.Modal(document.getElementById('taskModal')).show();
        },

        async saveTask() {
            this.saving = true;
            const isEdit = !!this.taskForm.id;
            const url    = isEdit ? `{{ url('admin/kanban/tarefas') }}/${this.taskForm.id}` : '{{ route("admin.kanban.task.store") }}';
            const method = isEdit ? 'PUT' : 'POST';
            const d = await HostPanel.fetch(url, { method, body: JSON.stringify(this.taskForm) });
            this.saving = false;
            HostPanel.toast(d.message);
            bootstrap.Modal.getInstance(document.getElementById('taskModal'))?.hide();
            await this.load();
        },

        async deleteTask(task) {
            if (!confirm(`Excluir "${task.title}"?`)) return;
            const d = await HostPanel.fetch(`{{ url('admin/kanban/tarefas') }}/${task.id}`, { method: 'DELETE' });
            HostPanel.toast(d.message);
            await this.load();
        },

        dragStart(task, col) { this.draggedTask = task; this.draggedFrom = col; },
        dragOver(col)  { col.dragOver = true; },
        dragLeave(col) { col.dragOver = false; },

        async dropTask(toCol) {
            toCol.dragOver = false;
            if (!this.draggedTask || this.draggedFrom?.id === toCol.id) return;
            await HostPanel.fetch(`{{ url('admin/kanban/tarefas') }}/${this.draggedTask.id}/mover`, {
                method: 'PUT',
                body: JSON.stringify({ kanban_column_id: toCol.id })
            });
            this.draggedTask = null;
            this.draggedFrom = null;
            await this.load();
        },

        init() { this.load(); }
    }
}
</script>
@endpush
