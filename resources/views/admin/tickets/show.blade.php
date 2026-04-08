@extends('admin.layouts.app')
@section('title', "Ticket #{$ticket->number}")
@section('page-title', "Ticket #{$ticket->number}")
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">Tickets</a></li>
    <li class="breadcrumb-item active">#{{ $ticket->number }}</li>
@endsection

@section('content')
<div x-data="adminTicketShow()" class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-2">{{ $ticket->subject }}</h5>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-{{ ['urgent'=>'danger','high'=>'warning','medium'=>'primary','low'=>'secondary'][$ticket->priority] ?? 'secondary' }}">
                        {{ ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Media','low'=>'Baixa'][$ticket->priority] ?? $ticket->priority }}
                    </span>
                    <span class="badge bg-{{ ['open'=>'danger','in_progress'=>'primary','answered'=>'success','on_hold'=>'warning','customer_reply'=>'info','closed'=>'secondary'][$ticket->status] ?? 'secondary' }}" id="status-badge">
                        {{ ['open'=>'Aberto','in_progress'=>'Em andamento','answered'=>'Respondido','on_hold'=>'Em espera','customer_reply'=>'Resp. cliente','closed'=>'Fechado'][$ticket->status] ?? $ticket->status }}
                    </span>
                    <span class="badge bg-light text-dark border">{{ $ticket->department?->name }}</span>
                    @if($ticket->service)
                        <span class="badge bg-light text-dark border"><i class="bi bi-hdd me-1"></i>{{ $ticket->service->domain ?? "Servico #{$ticket->service->id}" }}</span>
                    @endif
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary" @click="changeStatus('in_progress')"><i class="bi bi-play-circle me-1"></i>Em andamento</button>
                    <button class="btn btn-sm btn-outline-success" @click="changeStatus('answered')"><i class="bi bi-check me-1"></i>Respondido</button>
                    <button class="btn btn-sm btn-outline-warning" @click="changeStatus('on_hold')"><i class="bi bi-pause-circle me-1"></i>Em espera</button>
                    <button class="btn btn-sm btn-outline-secondary" @click="changeStatus('closed')"><i class="bi bi-lock me-1"></i>Fechar</button>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column gap-3 mb-4">
            @foreach($ticket->replies as $reply)
            @php $isClient = !is_null($reply->client_id); @endphp
            <div class="d-flex gap-3 {{ !$isClient ? 'flex-row-reverse' : '' }}">
                <div class="avatar-initials rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                    style="width:40px;height:40px;background:{{ $isClient ? '#1a56db' : '#1e293b' }};font-size:.875rem">
                    {{ strtoupper(substr($isClient ? ($reply->client?->name ?? 'C') : ($reply->admin?->name ?? 'S'), 0, 1)) }}
                </div>
                <div style="max-width:75%" class="{{ !$isClient ? 'text-end' : '' }}">
                    <div class="rounded-3 p-3 {{ $isClient ? 'bg-white border' : 'text-white' }}" style="{{ !$isClient ? 'background:#1a56db' : '' }}">
                        <div class="fw-semibold mb-1 small {{ $isClient ? 'text-muted' : 'text-white-50' }}">
                            {{ $isClient ? ($reply->client?->name ?? 'Cliente') : ($reply->admin?->name ?? 'Suporte') }}
                            {{ !$isClient ? '(Admin)' : '' }}
                        </div>
                        <div class="small" style="white-space:pre-wrap">{{ $reply->message }}</div>
                        @if($reply->attachments && count($reply->attachments))
                        <div class="mt-2 d-flex flex-wrap gap-1">
                            @foreach($reply->attachments as $att)
                            <a href="{{ asset('storage/'.$att) }}" target="_blank" class="btn btn-sm btn-outline-{{ $isClient ? 'secondary' : 'light' }} py-0 px-2" style="font-size:.7rem">
                                <i class="bi bi-paperclip"></i> Anexo
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <div class="text-muted mt-1 px-1" style="font-size:.7rem">{{ \Carbon\Carbon::parse($reply->created_at)->format('d/m/Y H:i') }}</div>
                </div>
            </div>
            @endforeach
        </div>

        @if($ticket->status !== 'closed')
        <div class="card">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-reply me-2"></i>Responder como suporte</div>
            <div class="card-body">
                <form @submit.prevent="reply">
                    <div class="mb-3">
                        <textarea class="form-control" rows="5" x-model="message" placeholder="Digite sua resposta..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <input type="file" class="d-none" multiple x-ref="files" accept="image/*,.pdf,.txt,.log" @change="uploader.onInputChange($event)">
                        <div class="hp-dropzone p-4" :class="{ 'is-dragging': uploader.dragging, 'is-uploading': loading }"
                            @click="uploader.openPicker()"
                            @dragover.prevent="uploader.handleDragOver($event)"
                            @dragleave="uploader.handleDragLeave($event)"
                            @drop="uploader.handleDrop($event)"
                            style="cursor:pointer">
                            <div class="d-flex flex-column align-items-center text-center">
                                <div class="rounded-4 bg-primary-subtle text-primary d-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;font-size:1.6rem">
                                    <i class="bi bi-cloud-arrow-up"></i>
                                </div>
                                <div class="fw-semibold text-dark">Arraste e solte anexos da resposta</div>
                                <div class="small text-muted mt-1">ou clique para selecionar imagens, PDF, TXT e LOG</div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3" x-show="uploader.files.length > 0">
                            <template x-for="(file, index) in uploader.files" :key="`${file.name}-${file.lastModified}`">
                                <span class="hp-file-pill small">
                                    <i class="bi bi-paperclip"></i>
                                    <span x-text="`${file.name} (${HostPanel.formatBytes(file.size)})`"></span>
                                    <button type="button" class="btn btn-link p-0 text-primary text-decoration-none" @click.stop="uploader.removeFile(index)">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </span>
                            </template>
                        </div>

                        <div class="rounded-4 border border-success-subtle bg-success-subtle bg-opacity-25 p-3 mt-3" x-show="loading && uploader.hasFiles()">
                            <div class="d-flex justify-content-between align-items-center gap-3 small">
                                <div class="fw-semibold text-success-emphasis">Enviando anexos</div>
                                <div class="fw-semibold text-success-emphasis" x-text="uploader.progressLabel()"></div>
                            </div>
                            <div class="hp-progress-track mt-3">
                                <div class="hp-progress-bar" :style="`width:${uploader.uploadProgress}%`"></div>
                            </div>
                            <div class="row g-3 mt-1 small text-success-emphasis">
                                <div class="col-md-4">
                                    <div class="fw-semibold text-uppercase">Enviado</div>
                                    <div x-text="`${uploader.uploadedLabel()} de ${uploader.totalBytesLabel()}`"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="fw-semibold text-uppercase">Velocidade</div>
                                    <div x-text="uploader.speedLabel()"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="fw-semibold text-uppercase">Tempo restante</div>
                                    <div x-text="uploader.remainingLabel()"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" :disabled="loading">
                            <span x-show="loading" class="spinner-border spinner-border-sm me-1"></span>
                            Enviar resposta
                        </button>
                        <button type="button" class="btn btn-outline-secondary" @click="quickReply('Ola! Estamos analisando o seu chamado e retornaremos em breve. Obrigado pela paciencia.')">
                            <i class="bi bi-lightning me-1"></i>Resposta rapida
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person me-2"></i>Cliente</div>
            <div class="card-body">
                <h6 class="fw-bold">{{ $ticket->client?->name }}</h6>
                <p class="text-muted small mb-1"><i class="bi bi-envelope me-1"></i>{{ $ticket->client?->email }}</p>
                @if($ticket->client?->phone)
                    <p class="text-muted small mb-1"><i class="bi bi-telephone me-1"></i>{{ $ticket->client?->phone }}</p>
                @endif
                <div class="d-flex gap-2 mt-2">
                    <a href="{{ route('admin.clients.show', $ticket->client) }}" class="btn btn-sm btn-outline-primary flex-grow-1">Ver cliente</a>
                    <form method="POST" action="{{ route('admin.clients.impersonate', $ticket->client) }}" class="flex-grow-1">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning w-100"><i class="bi bi-person-fill-gear"></i></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-info-circle me-2"></i>Informacoes</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small">Criado em</span>
                        <span class="small">{{ \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y H:i') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small">Ultima resposta</span>
                        <span class="small">{{ $ticket->last_reply_at ? \Carbon\Carbon::parse($ticket->last_reply_at)->format('d/m/Y H:i') : '-' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small">Respostas</span>
                        <span class="small">{{ $ticket->replies->count() }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small">Atribuido a</span>
                        <span class="small">{{ $ticket->assignedAdmin?->name ?? '-' }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person-plus me-2"></i>Atribuir admin</div>
            <div class="card-body">
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" x-model="assignedId">
                        <option value="">Sem atribuicao</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}" {{ $ticket->assigned_admin_id == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-primary" @click="assign">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function adminTicketShow() {
    return {
        message: '',
        loading: false,
        assignedId: '{{ $ticket->assigned_admin_id ?? "" }}',
        uploader: HostPanel.createUploadState({
            accept: 'image/*,.pdf,.txt,.log',
            maxFiles: 5,
            maxFileSize: 10 * 1024 * 1024,
        }),

        async reply() {
            this.loading = true;

            const formData = new FormData();
            formData.append('message', this.message);
            this.uploader.files.forEach((file) => formData.append('attachments[]', file));

            this.uploader.startUpload();

            let data;
            try {
                data = await HostPanel.upload({
                    url: '{{ route("admin.tickets.reply", $ticket) }}',
                    method: 'POST',
                    body: formData,
                    onProgress: (progress) => this.uploader.updateProgress(progress),
                });
                this.uploader.finishUpload();
            } catch (error) {
                this.uploader.failUpload();
                this.loading = false;
                HostPanel.toast('Falha de rede ao enviar a resposta.', 'danger');
                return;
            }

            this.loading = false;

            if (data.reply) {
                window.location.reload();
                return;
            }

            HostPanel.toast(data.message || 'Erro ao enviar.', 'danger');
        },

        quickReply(text) {
            this.message = text;
        },

        async changeStatus(status) {
            const data = await HostPanel.fetch('{{ route("admin.tickets.status.post", $ticket) }}', {
                method: 'POST',
                body: JSON.stringify({ status }),
            });

            HostPanel.toast(data.message || 'Status atualizado.');
            if (data.status) {
                document.getElementById('status-badge').textContent = {
                    open: 'Aberto',
                    in_progress: 'Em andamento',
                    answered: 'Respondido',
                    on_hold: 'Em espera',
                    customer_reply: 'Resp. cliente',
                    closed: 'Fechado',
                }[data.status] || data.status;
            }
        },

        async assign() {
            const data = await HostPanel.fetch('{{ route("admin.tickets.assign.post", $ticket) }}', {
                method: 'POST',
                body: JSON.stringify({ admin_id: this.assignedId }),
            });

            HostPanel.toast(data.message || 'Atribuicao atualizada.');
        },

        init() {
            this.uploader.attachInput(this.$refs.files);
        },
    };
}
</script>
@endpush
