@extends('client.layouts.app')
@section('title', 'Suporte')
@section('page-title', 'Tickets de Suporte')

@section('content')
<div x-data="ticketsList()">
    <div class="flex justify-between items-center mb-6">
        <div class="flex gap-3 flex-wrap">
            <input type="text" class="border border-gray-200 rounded-lg px-3 py-2 text-sm" placeholder="Buscar tickets..." x-model.debounce.400="search" @input="page=1;load()">
            <select class="border border-gray-200 rounded-lg px-3 py-2 text-sm" x-model="status" @change="page=1;load()">
                <option value="">Todos</option>
                <option value="open">Abertos</option>
                <option value="answered">Respondidos</option>
                <option value="customer_reply">Aguardando</option>
                <option value="closed">Fechados</option>
            </select>
        </div>
        <a href="{{ route('client.tickets.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            <i class="bi bi-plus-lg"></i> Novo Ticket
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div x-show="loading" class="py-12 text-center">
            <div class="inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
        </div>
        <div x-show="!loading">
            <template x-for="t in tickets" :key="t.id">
                <a :href="`/cliente/tickets/${t.id}`" class="flex items-center gap-4 px-5 py-4 border-b border-gray-50 hover:bg-gray-50 transition group">
                    <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center flex-shrink-0 group-hover:bg-purple-100 transition">
                        <i class="bi bi-chat-dots text-purple-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-900" x-text="`#${t.number}`"></span>
                            <span :class="`px-2 py-0.5 rounded-full text-xs font-semibold ${priorityClass(t.priority)}`" x-text="priorityLabel(t.priority)"></span>
                        </div>
                        <div class="text-sm text-gray-700 truncate mt-0.5" x-text="t.subject"></div>
                        <div class="text-xs text-gray-400 mt-0.5" x-text="`${t.department?.name} • ${timeAgo(t.last_reply_at)}`"></div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span :class="`inline-flex px-2.5 py-1 rounded-full text-xs font-semibold ${statusClass(t.status)}`" x-text="statusLabel(t.status)"></span>
                    </div>
                    <i class="bi bi-chevron-right text-gray-400 flex-shrink-0"></i>
                </a>
            </template>
            <template x-if="!loading && tickets.length === 0">
                <div class="py-12 text-center">
                    <i class="bi bi-headset text-5xl text-gray-200 block mb-3"></i>
                    <p class="text-gray-500">Você não possui tickets abertos.</p>
                    <a href="{{ route('client.tickets.create') }}" class="mt-3 inline-flex items-center gap-2 bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="bi bi-plus-lg"></i> Abrir Ticket
                    </a>
                </div>
            </template>
        </div>
    </div>

    <div class="flex justify-between items-center mt-4" x-show="meta && meta.last_page > 1">
        <span class="text-sm text-gray-500" x-text="`${meta?.from??0}–${meta?.to??0} de ${meta?.total??0}`"></span>
        <div class="flex gap-2">
            <button class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm disabled:opacity-40" :disabled="page===1" @click="page--;load()">← Anterior</button>
            <button class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm disabled:opacity-40" :disabled="page>=meta?.last_page" @click="page++;load()">Próxima →</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ticketsList() {
    return {
        tickets: [], meta: null, loading: false, search: '', status: '{{ request("status","") }}', page: 1,
        async load() {
            this.loading = true;
            const p = new URLSearchParams({ search: this.search, status: this.status, page: this.page });
            const d = await HostPanel.fetch(`/cliente/tickets?${p}`);
            this.tickets = d.data || [];
            this.meta = d.meta || d;
            this.loading = false;
        },
        statusClass(s) { return {open:'bg-red-100 text-red-700',answered:'bg-green-100 text-green-700',closed:'bg-gray-100 text-gray-600',in_progress:'bg-blue-100 text-blue-700',customer_reply:'bg-amber-100 text-amber-700',on_hold:'bg-purple-100 text-purple-700'}[s]||'bg-gray-100 text-gray-600'; },
        statusLabel(s) { return {open:'Aberto',answered:'Respondido',closed:'Fechado',in_progress:'Em Andamento',customer_reply:'Aguard. Equipe',on_hold:'Em Espera'}[s]||s; },
        priorityClass(p) { return {urgent:'bg-red-100 text-red-700',high:'bg-orange-100 text-orange-700',medium:'bg-blue-100 text-blue-700',low:'bg-gray-100 text-gray-500'}[p]||''; },
        priorityLabel(p) { return {urgent:'Urgente',high:'Alta',medium:'Média',low:'Baixa'}[p]||''; },
        timeAgo(d) {
            if (!d) return '—';
            const diff = Math.floor((Date.now() - new Date(d)) / 60000);
            if (diff < 1) return 'agora';
            if (diff < 60) return `${diff}min atrás`;
            if (diff < 1440) return `${Math.floor(diff/60)}h atrás`;
            return `${Math.floor(diff/1440)}d atrás`;
        },
        init() { this.load(); }
    }
}
</script>
@endpush
