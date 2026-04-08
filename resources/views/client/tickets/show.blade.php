@extends('client.layouts.app')
@section('title', "Ticket #{$ticket->number}")
@section('page-title', "Ticket #{$ticket->number}")

@section('content')
<div class="max-w-3xl mx-auto" x-data="ticketShow()">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-4">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div>
                <h2 class="font-bold text-gray-900 text-lg">{{ $ticket->subject }}</h2>
                <div class="flex items-center gap-3 mt-2 flex-wrap text-sm text-gray-500">
                    <span><i class="bi bi-folder me-1"></i>{{ $ticket->department?->name }}</span>
                    <span><i class="bi bi-calendar me-1"></i>{{ \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y H:i') }}</span>
                    @if($ticket->service)
                        <span><i class="bi bi-hdd me-1"></i>{{ $ticket->service->domain ?? "Servico #{$ticket->service->id}" }}</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ ['urgent'=>'bg-red-100 text-red-700','high'=>'bg-orange-100 text-orange-700','medium'=>'bg-blue-100 text-blue-700','low'=>'bg-gray-100 text-gray-600'][$ticket->priority] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Media','low'=>'Baixa'][$ticket->priority] ?? $ticket->priority }}
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ ['open'=>'bg-red-100 text-red-700','answered'=>'bg-green-100 text-green-700','closed'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','customer_reply'=>'bg-amber-100 text-amber-700'][$ticket->status] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ ['open'=>'Aberto','answered'=>'Respondido','closed'=>'Fechado','in_progress'=>'Em andamento','customer_reply'=>'Aguardando equipe'][$ticket->status] ?? $ticket->status }}
                </span>
            </div>
        </div>
    </div>

    <div class="space-y-4 mb-6">
        @foreach($ticket->replies as $reply)
        @php $isClient = !is_null($reply->client_id); @endphp
        <div class="flex gap-3 {{ $isClient ? 'flex-row-reverse' : '' }}">
            <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-sm {{ $isClient ? 'bg-blue-600 text-white' : 'bg-slate-700 text-white' }}">
                {{ strtoupper(substr($isClient ? $reply->client?->name : ($reply->admin?->name ?? 'S'), 0, 1)) }}
            </div>
            <div class="flex-1 max-w-lg {{ $isClient ? 'text-right' : '' }}">
                <div class="inline-block text-left rounded-2xl px-4 py-3 {{ $isClient ? 'bg-blue-600 text-white rounded-tr-sm' : 'bg-white border border-gray-100 shadow-sm text-gray-800 rounded-tl-sm' }}">
                    <div class="text-xs font-semibold mb-1 {{ $isClient ? 'text-blue-100' : 'text-gray-500' }}">
                        {{ $isClient ? ($reply->client?->name ?? 'Voce') : ($reply->admin?->name ?? 'Suporte') }}
                    </div>
                    <div class="text-sm whitespace-pre-wrap break-words">{{ $reply->message }}</div>
                    @if($reply->attachments && count($reply->attachments))
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach($reply->attachments as $att)
                        <a href="{{ asset('storage/' . $att) }}" target="_blank"
                           class="inline-flex items-center gap-1 text-xs {{ $isClient ? 'bg-blue-500 hover:bg-blue-400 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }} px-2 py-1 rounded">
                            <i class="bi bi-paperclip"></i>Anexo
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>
                <div class="text-xs text-gray-400 mt-1 px-1">{{ \Carbon\Carbon::parse($reply->created_at)->format('d/m/Y H:i') }}</div>
            </div>
        </div>
        @endforeach
    </div>

    @if($ticket->status !== 'closed')
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-4">Responder</h3>
        <form @submit.prevent="reply" class="space-y-4">
            <textarea class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 resize-none"
                rows="5" x-model="message" placeholder="Digite sua resposta..." required></textarea>
            <div>
                <label class="block text-xs text-gray-500 mb-2">Anexar arquivos (opcional)</label>
                <input type="file" class="hidden" multiple x-ref="files" accept="image/*,.pdf,.txt,.log" @change="uploader.onInputChange($event)">
                <div class="hp-dropzone p-5 cursor-pointer" :class="{ 'is-dragging': uploader.dragging, 'is-uploading': loading }"
                    @click="uploader.openPicker()"
                    @dragover.prevent="uploader.handleDragOver($event)"
                    @dragleave="uploader.handleDragLeave($event)"
                    @drop="uploader.handleDrop($event)">
                    <div class="flex flex-col items-center justify-center text-center">
                        <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-2xl mb-3">
                            <i class="bi bi-images"></i>
                        </div>
                        <div class="text-sm font-semibold text-gray-800">Solte os anexos para esta resposta</div>
                        <div class="text-xs text-gray-500 mt-1">ou clique para selecionar imagens, PDF, TXT e LOG</div>
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
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg text-sm" :disabled="loading">
                    <span x-show="loading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span>
                    <span x-text="loading ? 'Enviando...' : 'Enviar resposta'"></span>
                </button>
                <button type="button" class="border border-red-200 text-red-600 hover:bg-red-50 font-semibold py-2.5 px-4 rounded-lg text-sm" @click="closeTicket">
                    Fechar ticket
                </button>
            </div>
        </form>
    </div>
    @else
    <div class="bg-gray-50 rounded-xl border border-gray-200 p-5 text-center text-gray-500">
        <i class="bi bi-lock text-2xl text-gray-300 block mb-2"></i>
        Este ticket esta fechado.
        <div class="mt-3"><a href="{{ route('client.tickets.create') }}" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">Abrir novo ticket</a></div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function ticketShow() {
    return {
        message: '',
        loading: false,
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
                    url: '{{ route("client.tickets.reply", $ticket) }}',
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

            HostPanel.toast(data.message || 'Erro ao enviar resposta.', 'danger');
        },

        async closeTicket() {
            if (!(await HostPanel.confirm({ text: 'Deseja fechar este ticket?', confirmButtonText: 'Sim, fechar' }))) {
                return;
            }

            const data = await HostPanel.fetch('{{ route("client.tickets.close", $ticket) }}', { method: 'POST' });
            if (data.redirect || data.ticket || data.ok) {
                window.location.reload();
                return;
            }

            HostPanel.toast(data.message || 'Nao foi possivel fechar o ticket.', 'danger');
        },

        init() {
            this.uploader.attachInput(this.$refs.files);
        },
    };
}
</script>
@endpush
