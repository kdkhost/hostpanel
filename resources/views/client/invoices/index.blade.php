@extends('client.layouts.app')
@section('title', 'Faturas')
@section('page-title', 'Minhas Faturas')

@section('content')
<div x-data="invoicesList()">
    {{-- Filtros --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap gap-3 items-center">
            <div class="flex-1 min-w-48">
                <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="Buscar pelo número..." x-model.debounce.400="search" @input="page=1;load()">
            </div>
            <select class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" x-model="status" @change="page=1;load()">
                <option value="">Todos</option>
                <option value="pending">Pendente</option>
                <option value="paid">Pago</option>
                <option value="overdue">Em Atraso</option>
                <option value="cancelled">Cancelado</option>
            </select>
        </div>
    </div>

    {{-- Lista --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div x-show="loading" class="py-12 text-center">
            <div class="inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
        </div>
        <div x-show="!loading">
            <template x-for="inv in invoices" :key="inv.id">
                <a :href="`/cliente/faturas/${inv.id}`" class="flex items-center gap-4 px-5 py-4 border-b border-gray-50 hover:bg-gray-50 transition">
                    <div :class="`w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 ${statusBg(inv.status)}`">
                        <i class="bi bi-receipt text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900" x-text="`Fatura #${inv.number}`"></div>
                        <div class="text-sm text-gray-500 mt-0.5" x-text="`Venc. ${fmt_date(inv.date_due)}`"></div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="font-bold text-gray-900" x-text="`R$ ${fmt_money(inv.total)}`"></div>
                        <span :class="`inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusClass(inv.status)}`" x-text="statusLabel(inv.status)"></span>
                    </div>
                    <i class="bi bi-chevron-right text-gray-400 flex-shrink-0"></i>
                </a>
            </template>
            <template x-if="!loading && invoices.length === 0">
                <div class="py-12 text-center text-gray-500">
                    <i class="bi bi-receipt text-4xl text-gray-200 block mb-2"></i>
                    Nenhuma fatura encontrada.
                </div>
            </template>
        </div>
    </div>

    {{-- Paginação --}}
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
function invoicesList() {
    return {
        invoices: [], meta: null, loading: false, search: '', status: '{{ request("status","") }}', page: 1,
        async load() {
            this.loading = true;
            const p = new URLSearchParams({ search: this.search, status: this.status, page: this.page });
            const d = await HostPanel.fetch(`/cliente/faturas?${p}`);
            this.invoices = d.data || [];
            this.meta = d.meta || d;
            this.loading = false;
        },
        statusBg(s)    { return {pending:'bg-amber-50 text-amber-600',paid:'bg-green-50 text-green-600',overdue:'bg-red-50 text-red-600',cancelled:'bg-gray-50 text-gray-500'}[s]||'bg-gray-50 text-gray-500'; },
        statusClass(s) { return {pending:'bg-amber-100 text-amber-700',paid:'bg-green-100 text-green-700',overdue:'bg-red-100 text-red-700',cancelled:'bg-gray-100 text-gray-600'}[s]||'bg-gray-100 text-gray-600'; },
        statusLabel(s) { return {pending:'Pendente',paid:'Pago',overdue:'Atrasado',cancelled:'Cancelado'}[s]||s; },
        fmt_date(d)    { return d ? new Date(d+'T00:00:00').toLocaleDateString('pt-BR') : '—'; },
        fmt_money(v)   { return parseFloat(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2}); },
        init()         { this.load(); }
    }
}
</script>
@endpush
