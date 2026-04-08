@extends('client.layouts.app')
@section('title', "Ticket #{$ticket->number}")
@section('page-title', "Ticket #{$ticket->number}")

@section('content')
<div class="max-w-3xl mx-auto" x-data="ticketShow()">
    {{-- Header do Ticket --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-4">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div>
                <h2 class="font-bold text-gray-900 text-lg">{{ $ticket->subject }}</h2>
                <div class="flex items-center gap-3 mt-2 flex-wrap text-sm text-gray-500">
                    <span><i class="bi bi-folder me-1"></i>{{ $ticket->department?->name }}</span>
                    <span><i class="bi bi-calendar me-1"></i>{{ \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y H:i') }}</span>
                    @if($ticket->service)
                        <span><i class="bi bi-hdd me-1"></i>{{ $ticket->service->domain ?? "Serviço #{$ticket->service->id}" }}</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ ['urgent'=>'bg-red-100 text-red-700','high'=>'bg-orange-100 text-orange-700','medium'=>'bg-blue-100 text-blue-700','low'=>'bg-gray-100 text-gray-600'][$ticket->priority] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Média','low'=>'Baixa'][$ticket->priority] ?? $ticket->priority }}
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ ['open'=>'bg-red-100 text-red-700','answered'=>'bg-green-100 text-green-700','closed'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','customer_reply'=>'bg-amber-100 text-amber-700'][$ticket->status] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ ['open'=>'Aberto','answered'=>'Respondido','closed'=>'Fechado','in_progress'=>'Em Andamento','customer_reply'=>'Aguardando Equipe'][$ticket->status] ?? $ticket->status }}
                </span>
            </div>
        </div>
    </div>

    {{-- Mensagens --}}
    <div class="space-y-4 mb-6">
        @foreach($ticket->replies as $reply)
        @php $isClient = !is_null($reply->client_id); @endphp
        <div class="flex gap-3 {{ $isClient ? 'flex-row-reverse' : '' }}">
            <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-sm
                {{ $isClient ? 'bg-blue-600 text-white' : 'bg-slate-700 text-white' }}">
                {{ strtoupper(substr($isClient ? $reply->client?->name : ($reply->admin?->name ?? 'S'), 0, 1)) }}
            </div>
            <div class="flex-1 max-w-lg {{ $isClient ? 'text-right' : '' }}">
                <div class="inline-block text-left rounded-2xl px-4 py-3
                    {{ $isClient ? 'bg-blue-600 text-white rounded-tr-sm' : 'bg-white border border-gray-100 shadow-sm text-gray-800 rounded-tl-sm' }}">
                    <div class="text-xs font-semibold mb-1 {{ $isClient ? 'text-blue-100' : 'text-gray-500' }}">
                        {{ $isClient ? ($reply->client?->name ?? 'Você') : ($reply->admin?->name ?? 'Suporte') }}
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

    {{-- Área de Resposta --}}
    @if($ticket->status !== 'closed')
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-4">Responder</h3>
        <form @submit.prevent="reply" class="space-y-4">
            <textarea class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 resize-none"
                rows="5" x-model="message" placeholder="Digite sua resposta..." required></textarea>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Anexar arquivos (opcional)</label>
                <input type="file" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 w-full" multiple x-ref="files" accept="image/*,.pdf,.txt,.log">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg text-sm" :disabled="loading">
                    <span x-show="loading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span>
                    <span x-text="loading ? 'Enviando...' : 'Enviar Resposta'"></span>
                </button>
                <button type="button" class="border border-red-200 text-red-600 hover:bg-red-50 font-semibold py-2.5 px-4 rounded-lg text-sm" @click="closeTicket">
                    Fechar Ticket
                </button>
            </div>
        </form>
    </div>
    @else
    <div class="bg-gray-50 rounded-xl border border-gray-200 p-5 text-center text-gray-500">
        <i class="bi bi-lock text-2xl text-gray-300 block mb-2"></i>
        Este ticket está fechado.
        <div class="mt-3"><a href="{{ route('client.tickets.store') }}" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">Abrir novo ticket</a></div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function ticketShow() {
    return {
        message: '', loading: false,
        async reply() {
            this.loading = true;
            const fd = new FormData();
            fd.append('message', this.message);
            Array.from(this.$refs.files.files).forEach(f => fd.append('attachments[]', f));
            const res = await fetch('{{ route("client.tickets.reply", $ticket) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': HostPanel.csrfToken, 'Accept': 'application/json' },
                body: fd
            });
            const data = await res.json();
            this.loading = false;
            if (data.reply) { window.location.reload(); }
            else HostPanel.toast(data.message || 'Erro ao enviar resposta.', 'danger');
        },
        async closeTicket() {
            if (!(await HostPanel.confirm({ text: 'Deseja fechar este ticket?', confirmButtonText: 'Sim, fechar' }))) return;
            const d = await HostPanel.fetch('{{ route("client.tickets.close", $ticket) }}', { method: 'POST' });
            if (d.redirect || d.ticket) window.location.reload();
            else HostPanel.toast(d.message, 'danger');
        }
    }
}
</script>
@endpush
