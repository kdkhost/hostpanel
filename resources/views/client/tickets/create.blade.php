@extends('client.layouts.app')
@section('title', 'Abrir Ticket')
@section('page-title', 'Abrir Ticket de Suporte')

@section('content')
<div class="max-w-2xl mx-auto" x-data="createTicket()">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Novo Ticket</h2>
            <p class="text-sm text-gray-500 mt-1">Nossa equipe respondera em ate 4 horas uteis.</p>
        </div>
        <form @submit.prevent="submit" class="px-6 py-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Departamento *</label>
                    <select class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="form.ticket_department_id" required>
                        <option value="">Selecione...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Prioridade</label>
                    <select class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="form.priority">
                        <option value="low">Baixa</option>
                        <option value="medium" selected>Media</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Servico relacionado (opcional)</label>
                <select class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="form.service_id">
                    <option value="">Nenhum</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->domain ?? $service->product?->name ?? "Servico #{$service->id}" }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Assunto *</label>
                <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="form.subject" required placeholder="Descreva brevemente o problema">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Mensagem *</label>
                <textarea class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" rows="6" x-model="form.message" required placeholder="Descreva o problema com o maximo de contexto possivel."></textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Anexos (opcional)</label>
                <input type="file" class="hidden" multiple accept="image/*,.pdf,.txt,.log" x-ref="fileInput" @change="uploader.onInputChange($event)">
                <div class="hp-dropzone p-5 cursor-pointer" :class="{ 'is-dragging': uploader.dragging, 'is-uploading': loading }"
                    @click="uploader.openPicker()"
                    @dragover.prevent="uploader.handleDragOver($event)"
                    @dragleave="uploader.handleDragLeave($event)"
                    @drop="uploader.handleDrop($event)">
                    <div class="flex flex-col items-center justify-center text-center">
                        <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-2xl mb-3">
                            <i class="bi bi-cloud-arrow-up"></i>
                        </div>
                        <div class="text-sm font-semibold text-gray-800">Arraste e solte os arquivos aqui</div>
                        <div class="text-xs text-gray-500 mt-1">ou clique para selecionar imagens, PDF, TXT e LOG</div>
                        <div class="text-xs text-gray-400 mt-3">Max. 5 arquivos, 10MB cada.</div>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-2" x-show="uploader.files.length > 0">
                    <template x-for="(file, index) in uploader.files" :key="`${file.name}-${file.lastModified}`">
                        <span class="hp-file-pill text-xs">
                            <i class="bi bi-paperclip"></i>
                            <span x-text="`${file.name} (${HostPanel.formatBytes(file.size)})`"></span>
                            <button type="button" class="text-blue-700 hover:text-blue-900" @click.stop="uploader.removeFile(index)">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </span>
                    </template>
                </div>

                <div class="mt-3 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4" x-show="loading && uploader.hasFiles()">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <div class="font-semibold text-emerald-900">Enviando anexos</div>
                        <div class="font-semibold text-emerald-700" x-text="uploader.progressLabel()"></div>
                    </div>
                    <div class="hp-progress-track mt-3">
                        <div class="hp-progress-bar" :style="`width:${uploader.uploadProgress}%`"></div>
                    </div>
                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs text-emerald-900">
                        <div>
                            <div class="font-semibold uppercase tracking-wide text-emerald-700">Enviado</div>
                            <div x-text="`${uploader.uploadedLabel()} de ${uploader.totalBytesLabel()}`"></div>
                        </div>
                        <div>
                            <div class="font-semibold uppercase tracking-wide text-emerald-700">Velocidade</div>
                            <div x-text="uploader.speedLabel()"></div>
                        </div>
                        <div>
                            <div class="font-semibold uppercase tracking-wide text-emerald-700">Tempo restante</div>
                            <div x-text="uploader.remainingLabel()"></div>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-red-600 mt-2" x-show="uploader.error" x-text="uploader.error"></p>
            </div>
            <div class="alert d-none" :class="error ? 'bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm' : ''" x-show="error" x-text="error"></div>
            <div class="flex gap-3 pt-2">
                <a href="{{ route('client.tickets.index') }}" class="flex-1 text-center border border-gray-200 text-gray-600 font-semibold py-2.5 rounded-lg hover:bg-gray-50 text-sm">Cancelar</a>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg text-sm" :disabled="loading">
                    <span x-show="loading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span>
                    <span x-text="loading ? 'Enviando...' : 'Abrir Ticket'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function createTicket() {
    return {
        loading: false,
        error: null,
        uploader: HostPanel.createUploadState({
            accept: 'image/*,.pdf,.txt,.log',
            maxFiles: 5,
            maxFileSize: 10 * 1024 * 1024,
        }),
        form: {
            ticket_department_id: '',
            priority: 'medium',
            subject: '',
            message: '',
            service_id: '',
        },

        async submit() {
            this.loading = true;
            this.error = null;

            const formData = new FormData();
            Object.entries(this.form).forEach(([key, value]) => {
                if (value) {
                    formData.append(key, value);
                }
            });
            this.uploader.files.forEach((file) => formData.append('attachments[]', file));

            this.uploader.startUpload();

            let data;
            try {
                data = await HostPanel.upload({
                    url: '{{ route("client.tickets.store") }}',
                    method: 'POST',
                    body: formData,
                    onProgress: (progress) => this.uploader.updateProgress(progress),
                });
                this.uploader.finishUpload();
            } catch (error) {
                this.uploader.failUpload();
                this.loading = false;
                this.error = 'Falha de rede ao abrir o ticket.';
                HostPanel.toast(this.error, 'danger');
                return;
            }

            this.loading = false;

            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }

            if (data.ticket) {
                window.location.href = `/cliente/tickets/${data.ticket.id}`;
                return;
            }

            this.error = typeof data.errors === 'object'
                ? Object.values(data.errors).flat().join(', ')
                : (data.message || 'Erro ao abrir ticket.');
        },

        init() {
            this.uploader.attachInput(this.$refs.fileInput);
        },
    };
}
</script>
@endpush
