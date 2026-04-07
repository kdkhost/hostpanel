@extends('client.layouts.app')
@section('title', 'Abrir Ticket')
@section('page-title', 'Abrir Ticket de Suporte')

@section('content')
<div class="max-w-2xl mx-auto" x-data="createTicket()">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Novo Ticket</h2>
            <p class="text-sm text-gray-500 mt-1">Nossa equipe responderá em até 4 horas úteis.</p>
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
                        <option value="medium" selected>Média</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Serviço Relacionado (opcional)</label>
                <select class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="form.service_id">
                    <option value="">Nenhum</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->domain ?? $service->product?->name ?? "Serviço #{$service->id}" }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Assunto *</label>
                <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="form.subject" required placeholder="Descreva brevemente o problema">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Mensagem *</label>
                <textarea class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" rows="6" x-model="form.message" required placeholder="Descreva detalhadamente o problema, com informações relevantes como domínio, usuário, mensagem de erro, etc."></textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Anexos (opcional)</label>
                <input type="file" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" multiple accept="image/*,.pdf,.txt,.log" x-ref="fileInput">
                <p class="text-xs text-gray-400 mt-1">Máx. 5 arquivos, 10MB cada. Aceitos: imagens, PDF, TXT, LOG.</p>
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
        loading: false, error: null,
        form: { ticket_department_id:'', priority:'medium', subject:'', message:'', service_id:'' },
        async submit() {
            this.loading = true; this.error = null;
            const formData = new FormData();
            Object.entries(this.form).forEach(([k,v]) => v && formData.append(k, v));
            Array.from(this.$refs.fileInput.files).forEach(f => formData.append('attachments[]', f));

            const res = await fetch('{{ route("client.tickets.store") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': HostPanel.csrfToken, 'Accept': 'application/json' },
                body: formData
            });
            const data = await res.json();
            this.loading = false;
            if (data.redirect) { window.location.href = data.redirect; return; }
            if (data.ticket) { window.location.href = `/cliente/tickets/${data.ticket.id}`; return; }
            this.error = typeof data.errors === 'object' ? Object.values(data.errors).flat().join(', ') : (data.message || 'Erro ao abrir ticket.');
        }
    }
}
</script>
@endpush
